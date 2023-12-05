<?php

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
        $this->utils = new Utils();
        $this->config = $this->utils->getConfig();
        $this->dataObjects = new DataObjects();
    }

    public function getTopLevelSections(array $params = []) : array {

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
        $dspaceResponse = $this->utils->getRestApiResponse($url);

        // DSpace response path
        $embeddedSectionsPath = array("_embedded", "communities");
        // End response paths

        $sectionsMap = array();
        $sections = $this->getObjectFromResponse($embeddedSectionsPath, $dspaceResponse, self::COMMUNITY);
        foreach ($sections as $section) {
            $sectionsMap[$section["name"]] = $this->getSectionInfo($section);
        }
        $objectsModel = $this->dataObjects->getObjectsList();
        $objectsModel->setCount($this->getTotal($dspaceResponse));
        $objectsModel->setPagination($this->getPagination($dspaceResponse));
        $objectsModel->setObjects($sectionsMap);
        return $objectsModel->getData();
    }

    public function getSubSections(string $uuid, array $params = []): array
    {
        $this->utils->checkUUID($uuid);
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
        $dspaceResponse = $this->utils->getRestApiResponse($url);

        // DSpace response path
        $subsectionsPath = array("_embedded", "subcommunities");
        // End response paths

        $subcommitteeMap = array();
        $subsections = $this->getObjectFromResponse($subsectionsPath, $dspaceResponse, self::COMMUNITY);
        foreach($subsections as $section) {
            $subcommitteeMap[$section["name"]] = $this->getSectionInfo($section);
        }
        $objectsModel = $this->dataObjects->getObjectsList();
        $objectsModel->setCount($this->getTotal($dspaceResponse));
        $objectsModel->setPagination($this->getPagination($dspaceResponse));
        $objectsModel->setObjects($subcommitteeMap);

        return $objectsModel->getData();
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
        $dspaceResponse = $this->utils->getRestApiResponse($url);
        return $this->getSectionInfo($dspaceResponse);
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
        $dspaceResponse = $this->utils->getRestApiResponse($url);
        $collectionModel = $this->dataObjects->getCollectionModel();
        // Always make the extra call to get the item count for a single collection.
        $itemCount = $this->getItemCount($dspaceResponse["uuid"]);
        $collectionModel->setItemCount($itemCount);
        $this->setCollectionMetadata($dspaceResponse, $collectionModel);
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
        $dspaceResponse = $this->utils->getRestApiResponse($url);

        // DSpace response paths.
        $owningCollectionPath = array("_links", "owningCollection", "href");
        $thumbPath = array("_embedded","thumbnail","_links","content","href");
        $parentObject = array("_embedded","searchResult");
        // -- the search result
        $embeddedObjects = array("_embedded", "searchResult","_embedded");
        // -- the items embedded in the search result
        $embeddedItem = array("_embedded", "indexableObject");
        // End response paths

        $objectsListModel = $this->dataObjects->getObjectsList();
        $embeddedResult = $this->getObjectFromResponse($embeddedObjects, $dspaceResponse, self::DISCOVERY);
        $parent = $this->getObjectFromResponse($parentObject, $dspaceResponse, self::DISCOVERY);
        $itemsArr = array();
        foreach ($embeddedResult as &$restElement) {
            foreach ($restElement as &$item) {
                $object = $this->getObjectFromResponse($embeddedItem, $item, self::ITEM);
                $model = $this->dataObjects->getItemModel();
                $model->setOwningCollectionHref($this->getObjectFromResponse($owningCollectionPath, $object, self::ITEM));
                $model->setThumbnail($this->getObjectFromResponse($thumbPath, $object, self::ITEM));
                $this->setItemMetadata($object, $model);
                $itemsArr[] = $model->getData();
            }
        }
        $objectsListModel->setCount($this->getTotal($parent));
        $objectsListModel->setPagination($this->getPagination($parent));
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
        $dspaceResponse = $this->utils->getRestApiResponse($url);
        $collections = $this->getCollections($dspaceResponse, $reverseOrder);
        $objectsModel = $this->dataObjects->getObjectsList();
        $objectsModel->setCount($this->getTotal($dspaceResponse));
        $objectsModel->setPagination($this->getPagination($dspaceResponse));
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
        $dspaceResponse = $this->utils->getRestApiResponse($url);

        // DSpace response path
        $thumbPath = array("_embedded","thumbnail","_links","self","href");
        // End response paths

        $itemModel = $this->dataObjects->getItemModel();
        $itemModel->setThumbnail($this->getObjectFromResponse($thumbPath, $dspaceResponse, self::ITEM));
        $owningCollection = $this->getOwningCollectionFromResponse($dspaceResponse);
        $itemModel->setOwningCollectionName($owningCollection["name"]);
        $itemModel->setOwningCollectionUuid($owningCollection["uuid"]);
        $itemModel->setOwningCollectionHref($owningCollection["href"]);
        $this->setItemMetadata($dspaceResponse,$itemModel, $formatDescription);
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
        $dspaceResponse = $this->utils->getRestApiResponse($url);
        // Files live in a DSpace "bundle." The default bundle in DSpace is named "ORIGINAL".
        // The bundle name can be overridden.
        $bundle = $this->getBundle($dspaceResponse, $bundleName);
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
        $dspaceResponse = $this->utils->getRestApiResponse($url);

        // DSpace response paths
        $thumbnailPath = array("_links","thumbnail","href");
        $filePath = array("_links", "content", "href");
        // End response paths

        $objectsModel = $this->dataObjects->getBitstreamModel();
        $objectsModel->setName($dspaceResponse["name"]);
        $objectsModel->setUuid($dspaceResponse["uuid"]);
        $objectsModel->setHref($this->getObjectFromResponse($filePath, $dspaceResponse, self::BITSTREAM));
        $objectsModel->setMimetype($this->getBitstreamFormat($uuid));
        $thumbnail = $this->getThumbnailLink($this->getObjectFromResponse($thumbnailPath, $dspaceResponse, self::BITSTREAM));
        $objectsModel->setThumbnail($thumbnail);
        $this->setBitstreamMetadata($dspaceResponse, $objectsModel);
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
        $dspaceResponse = $this->utils->getRestApiResponse($url);

        // DSpace response paths
        $embeddedResultPath = array("_embedded", "searchResult");
        $searchResultsPath = array("_embedded", "searchResult", "_embedded", "objects");
        $embeddedObject = array("_embedded", "indexableObject");
        // End response paths

        $searchResult = $this->getObjectFromResponse($embeddedResultPath, $dspaceResponse, self::DISCOVERY);
        $objectsModel = $this->dataObjects->getObjectsList();
        $objectsModel->setCount($this->getTotal($searchResult));
        $objectsModel->setPagination($this->getPagination($searchResult));
        $respObjects = $this->getObjectFromResponse($searchResultsPath,$dspaceResponse, self::DISCOVERY);
        $objects = array();
        foreach ($respObjects as $obj) {
            if ($this->utils->checkPath($embeddedObject, $obj, self::DISCOVERY)) {
                $objects[] = $this->getSingleSearchResult($obj["_embedded"]["indexableObject"]);
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

    /**
     * Gets the information from the DSpace response for a community (section)
     * @param array $section the community
     * @return array
     *   <code>
     *   ['name']             string The name of the object
     *   ['uuid']             string The uuid of the object
     *   ['logo']             string The dspace object type (community, collection, item)
     *   ['subsectionCount']  string The number of subsection in this section
     *   ['collectionCount']  string The number of collections in this section
     *   </code>
     */
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
     * @param array $object the metadata
     * @param Bitstream $model the model to update
     * @return void
     */
    private function setBitstreamMetadata(array $object, Bitstream & $model): void
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
        // End response paths

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
        // End response paths

        return $this->getObjectFromResponse($totalElementsPath, $item, self::DISCOVERY);
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
        // End response paths

        $owner["name"] = $this->getObjectFromResponse($ownerNamePath, $response, self::ITEM);
        $owner["uuid"] = $this->getObjectFromResponse($ownerUuidPath, $response, self::ITEM);
        $owner["href"] = $this->getObjectFromResponse($ownerHrefPath, $response, self::ITEM);

        return $owner;

    }

    /**
     * Gets the information from a single search result.
     * @param $data array the DSpace response object
     * @return array
     *  <code>
     *  ['name']             string The name of the object
     *  ['uuid']             string The uuid of the object
     *  ['type']             string The dspace object type (community, collection, item)
     *  ['title']            string The object title
     *  ['creator']          string The creator
     *  ['description']      string The description of the object
     *  ['date']             string The date issued
     *  ['thumbnail']        string The thumbnail name and href
     *  </code>
      */
    private function getSingleSearchResult(array $data): array
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
        // End response paths

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

    /**
     * Retrieves the thumbnail link from DSpace.
     * @param string $href the endpoint for the thumbnail information.
     * @return string the thumbnail url
     */
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
     * Returns DSpace bundle information for a specific bundle. This is required
     * because DSpace items contain multiple bundles.
     * @param $bundles array the list bundles in the DSpace item
     * @param $bundleName string the name of the bundle to return
     * @return array the selected bundle
     */
    private function getBundle(array $bundles, string $bundleName): array
    {
        // DSpace response path
        $bundlesPath = array("_embedded","bundles");
        // End response paths

        $bundles = $this->getObjectFromResponse($bundlesPath, $bundles, self::BUNDLE);
        $bundle = array();
        foreach($bundles as &$currentBundle) {
            $b = $currentBundle["name"];
            if ($b == $bundleName) {
                $bundle = $currentBundle;
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
        // DSpace response path
        $embeddedCollectionsPath = array("_embedded", "collections");
        // End response paths

        $collectionMap = array();
        $communities = $this->getObjectFromResponse($embeddedCollectionsPath, $communityCollections, self::COLLECTION);
        foreach ($communities as $collection) {
            $model = $this->dataObjects->getCollectionModel();
            if ($this->config["retrieveItemCounts"]) {
                $itemCount = $this->getItemCount($collection["uuid"]);
                $model->setItemCount($itemCount);
            }
            $this->setCollectionMetadata($collection, $model);
            $collectionMap[] = $model->getData();
        }
        if ($reverseOrder) {
            return array_reverse($collectionMap, false);
        }
        return $collectionMap;
    }

    private function setCollectionMetadata(array $collection, & $model) : void
    {
        // DSpace response path
        $descriptionPath = array("metadata","dc.description");
        $shortDescriptionPath = array("metadata","dc.description.abstract");
        // End response paths

        $model->setLogo($this->getLogoFromResponse($collection));
        $model->setName($collection["name"]);
        $model->setUUID($collection["uuid"]);
        $model->setDescription($this->getMetadataFirstValue($descriptionPath, $collection, self::COLLECTION));
        $model->setShortDescription($this->getMetadataFirstValue($shortDescriptionPath, $collection,
            self::COLLECTION));
    }

    private function setItemMetadata(array $item, & $model,  bool $formatDescription = false): void {

        // DSpace response paths
        $titlePath = array("metadata","dc.title");
        $creatorPath = array("metadata","dc.contributor.author");
        $datePath = array("metadata","dc.date.issued");
        $rightsPath = array("metadata","dc.rights");
        $rightsPathUri = array("metadata","dc.rights.uri");
        // DSpace records can include dc.description, dc.description.abstract, none, or both.
        // Honor all here.
        $descriptionPath = array("metadata","dc.description");
        $abstractPath = array("metadata","dc.description.abstract");
        // End response paths

        $model->setName($item["name"]);
        $model->setUUID($item["uuid"]);
        $model->setTitle($this->getMetadataFirstValue($titlePath, $item, self::ITEM));
        $model->setAuthor($this->getMetadataFirstValue($creatorPath, $item, self::ITEM));
        $model->setDate($this->getMetadataFirstValue($datePath, $item, self::ITEM));
        $model->setRights($this->getMetadataFirstValue($rightsPath, $item, self::ITEM));
        $model->setRightsLink($this->getMetadataFirstValue($rightsPathUri, $item, self::ITEM));
        $desc = $this->getMetadataFirstValue($descriptionPath, $item, self::ITEM);
        $abs = $this->getMetadataFirstValue($abstractPath, $item, self::ITEM);
        if ($formatDescription) {
            $desc = $this->formatDescription($desc);
        }
        if ($formatDescription) {
            $abs = $this->formatDescription($abs);
        }
        $model->setDescription($desc);
        $model->setAbstract($abs);
    }

    /**
     * Gets information about bitstreams (e.g. image files) in the provided DSpace bundle.
     * @param array $bundle contains bundle with embedded bitstreams
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
        // End response paths

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
            $this->setBitstreamMetadata($file, $bitstreamModel);
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
        // DSpace response path
        $logoLinkPath = array("_embedded", "logo", "_links", "content", "href");
        // End response paths

        return $this->getObjectFromResponse($logoLinkPath, $response, self::LOGO);

    }

    private function getSubSectionCountForSection(array $section): string
    {
        // DSpace response path
        $sectionCountPath = array("_embedded", "subcommunities", "page", "totalElements");
        // End response paths

        return $this->getObjectFromResponse($sectionCountPath,$section, self::COMMUNITY);
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
