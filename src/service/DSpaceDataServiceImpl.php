<?php

require_once __DIR__ . "/../Configuration.php";
require __DIR__ . "/DSpaceDataService.php";
require __DIR__ . "/DataObjects.php";
require_once __DIR__ . "/../Utils.php";

/**
 * PHP service class implementation for retrieving Community, Collection, Item, and Bitstream
 * information from the DSpace REST API.
 */
class DSpaceDataServiceImpl implements DSpaceDataService
{

    private const ITEM = "ITEM";
    private const COMMUNITY = "COMMUNITY";
    private const COLLECTION = "COLLECTION";
    private const DISCOVERY = "DISCOVERY";
    private const BUNDLE = "BUNDLE";
    private const BITSTREAM = "BITSTREAM";
    private const LOGO = "LOGO";
    private const PARAMS = "REQUEST PARAMETER";

    private array $config;

    private Utils $utils;

    private DataObjects $dataObjects;

    public function __construct()
    {
        $settings = new Configuration();
        $this->config = $settings->getConfig();
        $this->dataObjects = new DataObjects();
        $this->utils = new Utils();

    }

    public function getTopLevelSections(array $params = []) : array {
        $sectionsMap = array();
        $query = array (
            "page" => 0,
            "size" => $this->config["defaultPageSize"],
            "embed" => "logo,collections,subcommunities/logo",
            "sort" => "dc.title,ASC"
        );
        if ($this->utils->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->utils->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $url = $this->config["base"] . "/core/communities/search/top";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $response = $this->utils->getRestApiResponse($url);

        // DSpace response path
        $embeddedSectionsPath = array("_embedded", "communities");

        $sections = $this->getObjectFromResponse($embeddedSectionsPath, $response, self::COMMUNITY);
        foreach ($sections as $section) {
            $sectionsMap[$section["name"]] = $this->getSectionInfo($section);
        }
        $objectsModel = $this->dataObjects->getObjectsList();
        $objectsModel->setCount($this->getTotal($response));
        $objectsModel->setPagination($this->getPagination($response));
        $objectsModel->setObjects($sectionsMap);
        return $objectsModel->getData();
    }

    public function getSubSections(string $uuid, array $params = []): array
    {
        $this->utils->checkUUID($uuid);
        $subcommitteeMap = array();
        $query = array (
            "page" => 0,
            "size" => $this->config["defaultPageSize"],
            "embed" => "logo,subcommunities,collections/logo"
        );
        if ($this->utils->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->utils->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $url = $this->config["base"] . "/core/communities/" . $uuid . "/subcommunities";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $result = $this->dataObjects->getObjectsList();
        $subCommunities = $this->utils->getRestApiResponse($url);
        if ($this->utils->checkKey("subcommunities", $subCommunities["_embedded"], self::COMMUNITY)) {
            foreach ($subCommunities["_embedded"]["subcommunities"] as $section) {
                $subcommitteeMap[$section["name"]] = $this->getSectionInfo($section);
            }
            $result->setCount($this->getTotal($subCommunities));
            $result->setPagination($this->getPagination($subCommunities));
            $result->setObjects($subcommitteeMap);
        }
        return $result->getData();
    }

    public function getSection(string $uuid): array {
        $this->utils->checkUUID($uuid);
        $query = array (
            "embed" => "subcommunities,collections,logo"
        );
        $url = $this->config["base"] . "/core/communities/" . $uuid;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $section = $this->utils->getRestApiResponse($url);
        $sectionCount = $this->getSubSectionCountForSection($section);
        $collectionCount = $this->getCollectionCountForSection($section);
        $logoHref = $this->getLogoFromResponse($section);
        $model = $this->dataObjects->getSectionModel();
        $model->setCollectionCount($collectionCount);
        $model->setSubSectionCount($sectionCount);
        $model->setCollectionCount($collectionCount);
        $model->setName($section["name"]);
        $model->setUUID($section["uuid"]);
        $model->setLogo($logoHref);
        return $model->getData();
    }

    public function getCollection(string $uuid): array
    {
        $query = array (
            "embed" => "logo"
        );
        $this->utils->checkUUID($uuid);
        $url = $this->config["base"] . "/core/collections/" . $uuid;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $collection = $this->utils->getRestApiResponse($url);

        // DSpace response paths
        $abstractPath = array("metadata","dc.description.abstract");
        $descPath = array("metadata","dc.description");

        $logoHref = $this->getLogoFromResponse($collection);
        // Always make extra call to get the item count.
        $itemCount = $this->getItemCount($collection["uuid"]);

        $collectionModel = $this->dataObjects->getCollectionModel();
        $collectionModel->setItemCount($itemCount);
        $collectionModel->setName($collection["name"]);
        $collectionModel->setUUID($collection["uuid"]);
        $shortDescription = $this->getMetadataFirstValue($abstractPath, $collection, self::COLLECTION);
        $description = $this->getMetadataFirstValue($descPath, $collection, self::COLLECTION);
        $collectionModel->setDescription($description);
        $collectionModel->setShortDescription($shortDescription);
        $collectionModel->setLogo($logoHref);
        return $collectionModel->getData();
    }

    public function getCollectionItems(string $uuid, array $params = []): array
    {
        $this->utils->checkUUID($uuid);
        $query = array (
            "scope" => $uuid,
            "embed" => "thumbnail",
            "dsoType" => "ITEM",
            "page" => 0,
            "size" => $this->config["defaultPageSize"],
        );
        if ($this->utils->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->utils->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $url = $this->config["base"] . "/discover/search/objects";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $restResponse = $this->utils->getRestApiResponse($url);

        // DSpace response paths.
        $titlePath = array("metadata", "dc.title");
        $creatorPath = array("metadata", "dc.contributor.author");
        $datePath = array("metadata", "dc.date.issued");
        $descriptionPath = array("metadata", "dc.description.abstract");
        $owningCollectionPath = array("_links", "owningCollection", "href");
        $thumbPath = array("_embedded","thumbnail","_links","content","href");
        $parentObject = array("_embedded","searchResult");
        $resultPath = array("_embedded", "searchResult","_embedded");
        $embeddedObject = array("_embedded", "indexableObject");

        $itemsArr = array();
        $objectsListModel = $this->dataObjects->getObjectsList();
        $embeddedResult = $this->getObjectFromResponse($resultPath, $restResponse, self::DISCOVERY);
        $parent = $this->getObjectFromResponse($parentObject, $restResponse, self::DISCOVERY);
        foreach ($embeddedResult as &$restElement) {
            foreach ($restElement as &$item) {
                $model = $this->dataObjects->getItemModel();
                $object = $this->getObjectFromResponse($embeddedObject, $item, self::ITEM);
                $model->setName($object["name"]);
                $model->setUUID($object["uuid"]);
                $model->setTitle($this->getMetadataFirstValue($titlePath, $object, self::ITEM));
                $model->setAuthor($this->getMetadataFirstValue($creatorPath, $object, self::ITEM));
                $model->setDate($this->getMetadataFirstValue($datePath, $object, self::ITEM));
                $model->setDescription($this->getMetadataFirstValue($descriptionPath, $object, self::ITEM));
                $model->setOwningCollectionHref($this->getObjectFromResponse($owningCollectionPath, $object, self::ITEM));
                $model->setThumbnail($this->getObjectFromResponse($thumbPath, $object, self::ITEM));
                $itemsArr[] = $model->getData();
                $objectsListModel->setCount($this->getTotal($parent));
                $objectsListModel->setPagination($this->getPagination($parent));
            }
        }
        $objectsListModel->setObjects($itemsArr);
        return $objectsListModel->getData();
    }

    public function getCollectionsForSection(string $uuid, array $params = [], bool $reverseOrder = true): array
    {
        $this->utils->checkUUID($uuid);
        $query = array (
            "page" => 0,
            "size" => $this->config["defaultPageSize"],
            "embed" => "logo"
        );
        if ($this->utils->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->utils->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $url = $this->config["base"] . "/core/communities/" . $uuid . "/collections";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $communityCollections = $this->utils->getRestApiResponse($url);
        $collections = $this->getCollections($communityCollections, $reverseOrder);
        $objectsModel = $this->dataObjects->getObjectsList();
        $objectsModel->setCount($this->getTotal($communityCollections));
        $objectsModel->setPagination($this->getPagination($communityCollections));
        $objectsModel->setObjects($collections);
        return $objectsModel->getData();
    }

    public function getItem(string $uuid, bool $formatDescription = false): array
    {
        $this->utils->checkUUID($uuid);
        $query = array (
            "embed" => "thumbnail,owningCollection"
        );
        $url = $this->config["base"] . "/core/items/" . $uuid;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $item = $this->utils->getRestApiResponse($url);

        // DSpace response paths
        $titlePath = array("metadata","dc.title");
        $creatorPath = array("metadata","dc.contributor.author");
        $datePath = array("metadata","dc.date.issued");
        $rightsPath = array("metadata","dc.rights");
        $rightsPathUri = array("metadata","dc.rights.uri");
        $descriptionPath = array("metadata","dc.description.abstract");
        $thumbPath = array("_embedded","thumbnail","_links","self","href");

        $itemModel = $this->dataObjects->getItemModel();
        $itemModel->setName($item["name"]);
        $itemModel->setUUID($item["uuid"]);
        $itemModel->setTitle($this->getMetadataFirstValue($titlePath, $item, self::ITEM));
        $itemModel->setAuthor($this->getMetadataFirstValue($creatorPath, $item, self::ITEM));
        $itemModel->setDate($this->getMetadataFirstValue($datePath, $item, self::ITEM));
        $itemModel->setRights($this->getMetadataFirstValue($rightsPath, $item, self::ITEM));
        $itemModel->setRightsLink($this->getMetadataFirstValue($rightsPathUri, $item, self::ITEM));
        $desc = $this->getMetadataFirstValue($descriptionPath, $item, self::ITEM);
        if ($formatDescription) {
            $desc = $this->formatDescription($desc);
        }
        $itemModel->setDescription($desc);
        $itemModel->setThumbnail($this->getObjectFromResponse($thumbPath, $item, self::ITEM));
        $owningCollection = $this->getOwningCollectionFromResponse($item);
        $itemModel->setOwningCollectionName($owningCollection["name"]);
        $itemModel->setOwningCollectionUuid($owningCollection["uuid"]);
        $itemModel->setOwningCollectionHref($owningCollection["href"]);

        return $itemModel->getData();
    }

    public function getItemFiles(string $uuid, string $bundleName = "ORIGINAL"): array
    {
        $this->utils->checkUUID($uuid);
        $query = array (
            "size" => "9999",
            "embed.size" > "bitstreams=" . $this->config["defaultEmbeddedBitstreamParam"],
            "embed" => "bitstreams/format"
        );
        $url = $this->config["base"] . "/core/items/" . $uuid . "/bundles";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $bundles = $this->utils->getRestApiResponse($url);
        $bundle = $this->getBundle($bundles, $bundleName);
        if (count($bundle) > 0) {
            try {
                $result = $this->getBitstreams($bundle);
                return $result->getData();
            } catch (Exception $err) {
                error_log($err, 0);
                return array();
            }
        } else {
            error_log("ERROR: The requested bundle was not found: " . $bundleName);
            return array();
        }
    }

    public function getBitstreamData(string $uuid): array
    {
        $this->utils->checkUUID($uuid);
        $url = $this->config["base"] . "/core/bitstreams/" . $uuid;
        $file = $this->utils->getRestApiResponse($url);

        // DSpace response paths
        $thumbnailPath = array("_links","thumbnail","href");
        $filePath = array("_links", "content", "href");
        
        $objectsModel = $this->dataObjects->getBitstreamModel();
        $objectsModel->setName($file["name"]);
        $objectsModel->setUuid($file["uuid"]);
        $objectsModel->setHref($this->getObjectFromResponse($filePath, $file, self::BITSTREAM));
        $objectsModel->setMimetype($this->getBitstreamFormat($uuid));
        $thumbnail = $this->getThumbnailLink($this->getObjectFromResponse($thumbnailPath, $file, self::BITSTREAM));
        $objectsModel->setThumbnail($thumbnail);
        $this->getBitstreamMetadata($file, $objectsModel);
        return $objectsModel->getData();
    }

    public function search(array $params = []): array
    {
        $query = array (
            "scope" => $this->config["scope"],
            "page" => "0",
            "size" => $this->config["defaultPageSize"],
            "embed" => "thumbnail,item/thumbnail"
        );
        if ($this->utils->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->utils->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        if ($this->utils->checkKey("scope", $params, self::PARAMS)) {
            $query["scope"] = $params["scope"];
        }
        if ($this->utils->checkKey("query", $params, self::PARAMS)) {
            $query["query"] = $params["query"];
        }
        $url = $this->config["base"] . "/discover/search/objects";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $response = $this->utils->getRestApiResponse($url);

        // DSpace response paths
        $embeddedResultPath = array("_embedded", "searchResult");
        $searchResultsPath = array("_embedded", "searchResult", "_embedded", "objects");
        $embeddedObject = array("_embedded", "indexableObject");

        $searchResult = $this->getObjectFromResponse($embeddedResultPath, $response, self::DISCOVERY);
        $objectsModel = $this->dataObjects->getObjectsList();
        $objectsModel->setCount($this->getTotal($searchResult));
        $objectsModel->setPagination($this->getPagination($searchResult));
        $respObjects = $this->getObjectFromResponse($searchResultsPath,$response, self::DISCOVERY);
        $objects = array();
        foreach ($respObjects as $obj) {
            if ($this->utils->checkPath($embeddedObject, $obj, self::DISCOVERY)) {
                $objects[] = $this->getSearchResultObj($obj["_embedded"]["indexableObject"]);
            }
        }
        $objectsModel->setObjects($objects);
        return $objectsModel->getData();
    }

    /**
     * Returns the count of items for the collection with the provided uuid.
     * @param string $uuid collection uuid
     * @return string the number of items
     */
    public function getItemCountForCollection(string $uuid): string {
        $this->utils->checkUUID($uuid);
        return $this->getItemCount($uuid);
    }

    private function getSectionInfo(array $section): array
    {
        $sectionModel = $this->dataObjects->getSectionModel();
        $sectionModel->setName($section["name"]);
        $sectionModel->setUUID($section["uuid"]);
        $sectionModel->setLogo($this->getLogoFromResponse($section));
        $sectionCount = $this->getSubSectionCountForSection($section);
        $sectionModel->setSubSectionCount($sectionCount);
        $collectionCount = $this->getCollectionCountForSection($section);
        $sectionModel->setCollectionCount($collectionCount);
        return $sectionModel->getData();
    }
    /**
     * Sets metadata on the <code>Bitstream</code> model.
     * @param array $object the array with the metadata key
     * @param Bitstream $model the model to update
     * @return void
     */
    private function getBitstreamMetadata(array $object, Bitstream & $model): void
    {
        // DSpace response paths
        $titlePath = array("metadata","dc.title");
        $labelPath = array("metadata","iiif.label");
        $descriptionPath = array("metadata","dc.description");
        $mediumPath = array("metadata","dc.format.medium");
        $dimensionsPath = array("metadata","dc.format.extent");
        $subjectPath = array("metadata","dc.subject.other");
        $typePath = array("metadata","dc.type");
        $rightsPath = array("metadata","dc.rights");
        $rightsUriPath = array("metadata","dc.rights.uri");

        $model->setTitle($this->getMetadataFirstValue($titlePath, $object, self::BITSTREAM));
        $model->setLabel($this->getMetadataFirstValue($labelPath, $object, self::BITSTREAM));
        $model->setDescription($this->getMetadataFirstValue($descriptionPath, $object, self::BITSTREAM));
        $model->setMedium($this->getMetadataFirstValue($mediumPath, $object, self::BITSTREAM));
        $model->setDimensions($this->getMetadataFirstValue($dimensionsPath, $object, self::BITSTREAM));
        $model->setSubject($this->getMetadataFirstValue($subjectPath, $object, self::BITSTREAM));
        $model->setType($this->getMetadataFirstValue($typePath, $object, self::BITSTREAM));
        $model->setRights($this->getMetadataFirstValue($rightsPath, $object, self::BITSTREAM));
        $model->setRightsLink($this->getMetadataFirstValue($rightsUriPath, $object, self::BITSTREAM));

    }

    /**
     * DSpace currently does not return the item count with the collection responses.
     * This method makes a title browse request for the collection and returns the
     * number of items. It is not efficient and can be slow.
     * @param string $uuid
     * @return string
     */
    private function getItemCount(string $uuid): string
    {
        $query = array (
            "page" => 0,
            "size" => 1,
            "scope" => $uuid,
            "dsoType" => "ITEM"
        );
        $url = $this->config["base"] . "/discover/browses/title/items";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $item = $this->utils->getRestApiResponse($url);

        // DSpace response path
        $totalElementsPath = array("page","totalElements");
        return $this->getObjectFromResponse($totalElementsPath, $item, self::DISCOVERY);
    }

    private function getSubSectionCountForSection(array $section): string
    {
        $sectionCountPath = array("_embedded", "subcommunities", "page", "totalElements");
        return $this->getObjectFromResponse($sectionCountPath,$section, self::COMMUNITY);
    }

    private function getCollectionCountForSection(array $section): string
    {
        $collectionCountPath = array("_embedded", "collections", "page", "totalElements");
        return $this->getObjectFromResponse($collectionCountPath, $section, self::COMMUNITY);
    }
    private function getLogoFromResponse(?array $response) : string {
        if (!$response) {
            error_log("INFO: DSpace response did not include a logo");
            return "";
        }
        $logoLinkPath = array("_embedded", "logo", "_links", "content", "href");
        return $this->getObjectFromResponse($logoLinkPath, $response, self::LOGO);

    }

    /**
     * Gets information about the owning collection from the DSpace response. It
     * always returns the "href" if the owning collection is found. Also returns
     * the collection name an uuid when available.
     * @param array|null $response the parent element into which the collection information is embedded.
     * @return array
     */
    private function getOwningCollectionFromResponse(?array $response) : array
    {
        $owner = array();

        if (!$response) {
            error_log("WARNING: DSpace response did not include an owning collection.");
            return $owner;
        }

        // DSpace response paths
        $ownerNamePath = array("_embedded", "owningCollection", "name");
        $ownerUuidPath = array("_embedded", "owningCollection", "uuid");
        $ownerHrefPath = array("_embedded", "owningCollection", "_links", "self", "href");

        $owner["name"] = $this->getObjectFromResponse($ownerNamePath, $response, self::ITEM);
        $owner["uuid"] = $this->getObjectFromResponse($ownerUuidPath, $response, self::ITEM);
        $owner["href"] = $this->getObjectFromResponse($ownerHrefPath, $response, self::ITEM);

        return $owner;

    }

    /**
     * Gets the <code>SearchResult</code> object.
     * @param $data array the DSpace object
     * @return array
     */
    private function getSearchResultObj(array $data): array
    {
        $objectModel = $this->dataObjects->getSearchObject();
        $objectModel->setName($data["name"]);
        $objectModel->setUuid($data["uuid"]);
        // this is the dspace object type (community, collection, or item)
        $objectModel->setType($data["type"]);

        // DSpace response paths
        $titlePath = array("metadata", "dc.title");
        $descriptionPath = array("metadata", "dc.description");
        $abstractPath = array("metadata", "dc.description.abstract");
        $datePath = array("metadata", "dc.date.issued");
        $creatorPath = array("metadata", "dc.contributor.author");
        $thumbNamePath = array("_embedded","thumbnail","name");
        $thumbLinkPath = array("_embedded", "thumbnail","_links","content","href");

        $objectModel->setTitle($this->getMetadataFirstValue($titlePath, $data, self::DISCOVERY));
        $objectModel->setCreator($this->getMetadataFirstValue($creatorPath, $data, self::DISCOVERY));
        $objectModel->setDate($this->getMetadataFirstValue($datePath, $data, self::DISCOVERY));
        $desc = $this->getMetadataFirstValue($descriptionPath, $data, self::DISCOVERY);
        if (strlen($desc) == 0) {
            $objectModel->setDescription($this->getMetadataFirstValue($abstractPath, $data, self::DISCOVERY));
        } else {
            $objectModel->setDescription($desc);
        }
        $objectModel->setThumbnailHref($this->getObjectFromResponse($thumbLinkPath, $data, self::DISCOVERY));
        $objectModel->setThumbnailName($this->getObjectFromResponse($thumbNamePath, $data, self::DISCOVERY));

        return $objectModel->getData();
    }

    /**
     * Gets the file format. It appears that the file format can't
     * be embedded in the DSpace API response. So it must be retrieved
     * using the format linked entity.
     * @param $uuid string the file uuid
     * @return string
     */
    private function getBitstreamFormat(string $uuid) : string {
        $url = $this->config["base"] . "/core/bitstreams/" . $uuid . "/format";
        $format = $this->utils->getRestApiResponse($url);
        if ($this->utils->checkKey("mimetype", $format)) {
            return $format["mimetype"];
        }
        return "";
    }

    private function getThumbnailLink(string $href): string
    {
        $images = $this->utils->getRestApiResponse($href);
        return $this->getImageUrl($images);
    }

    /**
     * Adds html paragraph tags when a description contains double line breaks.
     * @param $desc string the DSpace item description
     * @return string
     */
    private function formatDescription(string $desc): string
    {
        if (preg_match("/[\\n]+/", $desc)) {
            $desc = preg_replace("/\\n\\n/", '</p><p>', $desc);
            return "<p>" . $desc . "</p>";
        }
        else {
            return $desc;
        }
    }

    /**
     * Returns DSpace bundle information for a specific bundle.
     * @param $bundles array the list bundles from the DSpace item
     * @param $bundleName string the name of the bundle to return
     * @return array
     */
    private function getBundle(array $bundles, string $bundleName): array
    {
        $bundle = array();

        $bundlesPath = array("_embedded","bundles");
        
        if ($this->utils->checkPath($bundlesPath, $bundles, self::BUNDLE)) {
            foreach($bundles["_embedded"]["bundles"] as &$currentBundle) {
                $b = $currentBundle["name"];
                if ($b == $bundleName) {
                    $bundle = $currentBundle;
                }
            }
        }
        return $bundle;
    }

    /**
     * Returns collection information from DSpace community metadata with embedded collections.
     * @param $communityCollections array DSpace community metadata
     * @param $reverseOrder boolean optional value that reverses order of the array (defaults to true)
     * @return array the array of collection objects
     * <code>
     * ['name']             string The name of the collection
     * ['uuid']             string The uuid of the collection
     * ['itemCount']        string (optional) The number of items in the collection (see Configuration)
     * ['shortDescription'] string The short description of the collection
     * ['description']      string (optional) The description of the collection
     * ['logo']             string The href of the collection logo (if available)
     * </code>
     */
    private function getCollections(array $communityCollections, bool $reverseOrder = true): array
    {
        $embeddedCollectionsPath = array("_embedded", "collections");
        $descriptionPath = array("metadata","dc.description");
        $shortDescriptionPath = array("metadata","dc.description.abstract");

        $collectionMap = array();
        $communities = $this->getObjectFromResponse($embeddedCollectionsPath, $communityCollections, self::COLLECTION);
        foreach ($communities as $collection) {
            $model = $this->dataObjects->getCollectionModel();
            if ($this->config["retrieveItemCounts"]) {
                $itemCount = $this->getItemCount($collection["uuid"]);
                $model->setItemCount($itemCount);
            }
            $model->setLogo($this->getLogoFromResponse($collection));
            $model->setName($collection["name"]);
            $model->setUUID($collection["uuid"]);
            $model->setDescription($this->getMetadataFirstValue($descriptionPath, $collection, self::COLLECTION));
            $model->setShortDescription($this->getMetadataFirstValue($shortDescriptionPath, $collection, self::COLLECTION));
            $collectionMap[] = $model->getData();
        }
        if ($reverseOrder) {
            return array_reverse($collectionMap, false);
        }
        return $collectionMap;
    }

    /**
     * Gets information about bitstreams (e.g. image files) in the DSpace bundle.
     * @param array $bundle
     * @return ObjectsList
     */
    private function getBitstreams(array $bundle): ObjectsList
    {
        // DSpace response paths
        $bitstreamObjects = array("_embedded","bitstreams","_embedded","bitstreams");
        $totalsPath = array("_embedded","bitstreams","page","totalElements");
        $thumbnailPath = array("_links", "thumbnail", "href");
        $fileLinkPath = array("_links", "content", "href");
        $mimetypePath = array("_embedded", "format", "mimetype");
        
        $imageArr = array();
        $bitstreams = $this->getObjectFromResponse($bitstreamObjects, $bundle, self::BUNDLE);
        foreach ($bitstreams as $file) {
            $thumbnailInfo = $this->getObjectFromResponse($thumbnailPath, $file, self::BITSTREAM);
            $thumbnail = $this->getThumbnailLink($thumbnailInfo);
            $mainImage = $this->getObjectFromResponse($fileLinkPath, $file, self::BITSTREAM);
            $mimeType = $this->getObjectFromResponse($mimetypePath, $file, self::BITSTREAM);
            $bitstreamModel = $this->dataObjects->getBitstreamModel();
            $bitstreamModel->setName($file["name"]);
            $bitstreamModel->setUuid($file["uuid"]);
            $bitstreamModel->setHref($mainImage);
            $bitstreamModel->setMimetype($mimeType);
            $bitstreamModel->setThumbnail($thumbnail);
            $this->getBitstreamMetadata($file, $bitstreamModel);
            $imageArr[] = $bitstreamModel->getData();
        }
        $objectsModel = $this->dataObjects->getObjectsList();
        $objectsModel->setObjects($imageArr);
        $objectsModel->setCount($this->getObjectFromResponse($totalsPath,$bundle, self::BUNDLE));
        return $objectsModel;
    }

    /**
     * Takes as input the DSpace metadata for the image and returns the URL
     * for retrieving the image content (or default image if not found).
     * @param array|null $linkData array DSpace metadata for the image
     * @return string the content URL
     */
    private function getImageUrl(?array $linkData) : string
    {
        if ($linkData) {
            if ($this->utils->checkKey("_links", $linkData, self::BITSTREAM)) {
                $imageLinks = $linkData["_links"];
                if ($imageLinks) {
                    if ($this->utils->checkKey("content", $imageLinks, self::BITSTREAM)) {
                        return ($linkData["_links"]["content"]["href"]);
                    }
                }
            }
        }
        return $this->config["defaultThumbnail"];
    }

    /**
     * Gets the pagination attributes from a DSpace response. The associative arrays
     * for next and previous pagination will be empty if the DSpace response doesn't
     * include the pagination links.
     * @param array $object the parent object that contains "_links" information for pagination.
     * @return array
     */
    private function getPagination(array $object): array {
        $paginationModel = $this->dataObjects->getPaginationModel();
        if ($this->utils->checkKey("_links", $object, self::COMMUNITY)) {
            if ($this->utils->checkKey("next", $object["_links"], self::COMMUNITY)) {
                $parts = parse_url($object["_links"]["next"]["href"]);
                parse_str($parts['query'], $query);
                $paginationModel->setNext($query['page'],$query['size']);
            }
            if ($this->utils->checkKey("prev", $object["_links"], self::COMMUNITY)) {
                $parts = parse_url($object["_links"]["prev"]["href"]);
                parse_str($parts['query'], $query);
                $paginationModel->setPrev($query['page'],$query['size']);
            }
        }

        return $paginationModel->getData();
    }

    /**
     * Get the total number of elements returned in a DSpace list response. The
     * total is the actual number of results, not just the current set of paginated
     * results.
     * @param array $object the parent element that contains the "page" element used by pagination.
     * @return string
     */
    private function getTotal(array $object): string {
        $totalPath = array("page", "totalElements");
        if ($this->utils->checkPath($totalPath, $object, self::COMMUNITY)) {
            return $object["page"]["totalElements"];
        }
        return "0";
    }

    /**
     * DSpace metadata fields are repeatable. This function always returns
     * the first field, which should be all we need.
     * @param array $path
     * @param array $source
     * @param $type
     * @return string
     */
    function getMetadataFirstValue(array $path, array $source, $type) : string {
        if ($this->utils->checkPath($path, $source, $type)) {
            $query = array();
            foreach ($path as $elem) {
                $query[] = $elem;
            }
            $value = array_reduce($query, function($result, $index) {
                if ($result && !is_null($index)) {
                    return (array_key_exists($index, $result)) ? $result[$index] : null;
                }
            }, $source);
            return $value[0]["value"];
        }
        return "";
    }

    /**
     * Returns the object from the DSpace response. This can be either
     * a string value or an array. Returns empty string if the path is incorrect.
     * @param array $path
     * @param array $source
     * @param $type
     * @return mixed
     */
    function getObjectFromResponse(array $path, array $source, $type) : mixed
    {
        if ($this->utils->checkPath($path, $source, $type)) {
            $query = array();
            foreach ($path as $elem) {
                $query[] = $elem;
            }
            return array_reduce($query, function($result, $index) {
                if ($result && !is_null($index)) {
                    return (array_key_exists($index, $result)) ? $result[$index] : "";
                }
            }, $source);
        }
        return "";
    }

}
