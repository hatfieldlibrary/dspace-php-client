<?php

require __DIR__ . "/../Configuration.php";
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
    private const REQUEST_FAILED = "DSPACE_REQUEST_ERROR";

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

    public function getSection(string $uuid): array {
        $this->checkUUID($uuid);
        $query = array (
            "embed" => "subcommunities,collections,logo"
        );
        $url = $this->config["base"] . "/core/communities/" . $uuid;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $model = $this->dataObjects->getSectionModel();
        $section = $this->getRestApiResponse($url);
        $sectionCount = $this->getSubSectionCountForSection($section);
        $model->setSubSectionCount($sectionCount);
        $collectionCount = $this->getCollectionCountForSection($section);
        $model->setCollectionCount($collectionCount);
        $logoHref = $this->getLogoFromResponse($section);
        $model->setName($section["name"]);
        $model->setUUID($section["uuid"]);
        $model->setLogo($logoHref);
        return $model->getData();
    }

    public function getTopLevelSections(array $params = []) : array {
        $sectionsMap = array();
        $query = array (
            "page" => 0,
            "size" => $this->config["defaultPageSize"],
            "embed" => "logo,collections,subcommunities/logo",
            "sort" => "dc.title,ASC"
        );
        if ($this->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $url = $this->config["base"] . "/core/communities/search/top";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $sections = $this->getRestApiResponse($url);
        $result = $this->dataObjects->getObjectsList();
        $model = $this->dataObjects->getSectionModel();
        if ($this->checkKey("communities", $sections["_embedded"], self::COMMUNITY)) {
            foreach ($sections["_embedded"]["communities"] as $section) {
                $model->setName($section["name"]);
                $model->setUUID($section["uuid"]);
                $model->setLogo($this->getLogoFromResponse($section));
                $sectionCount = $this->getSubSectionCountForSection($section);
                $model->setSubSectionCount($sectionCount);
                $collectionCount = $this->getCollectionCountForSection($section);
                $model->setCollectionCount($collectionCount);
                $sectionsMap[$section["name"]] = $model->getData();
            }
            if ($this->checkKey("page", $sections)) {
                if ($this->checkKey("totalElements", $sections["page"])) {
                    $result->setCount($this->getTotal($sections));
                }
            }
            $pagination = $this->getPagination($sections);
            $result->setPagination($pagination);
            $result->setObjects($sectionsMap);
        }
        return $result->getData();
    }

    public function getSubSections(string $uuid, array $params = []): array
    {
        $this->checkUUID($uuid);
        $subcommitteeMap = array();
        $query = array (
            "page" => 0,
            "size" => $this->config["defaultPageSize"],
            "embed" => "logo,subcommunities,collections/logo"
        );
        if ($this->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $url = $this->config["base"] . "/core/communities/" . $uuid . "/subcommunities";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $result = $this->dataObjects->getObjectsList();
        $model = $this->dataObjects->getSectionModel();
        $subCommunities = $this->getRestApiResponse($url);
        if ($this->checkKey("subcommunities", $subCommunities["_embedded"], self::COMMUNITY)) {
            foreach ($subCommunities["_embedded"]["subcommunities"] as $section) {
                $model->setName($section["name"]);
                $model->setUUID($section["uuid"]);
                $model->setLogo($this->getLogoFromResponse($section));
                $sectionCount = $this->getSubSectionCountForSection($section);
                $model->setSubSectionCount($sectionCount);
                $collectionCount = $this->getCollectionCountForSection($section);
                $model->setCollectionCount($collectionCount);
                $subcommitteeMap[$section["name"]] = $model->getData();
            }
            $pagination = $this->getPagination($subCommunities);
            $result->setCount($this->getTotal($subCommunities));
            $result->setPagination($pagination);
            $result->setObjects($subcommitteeMap);
        }
        return $result->getData();
    }

    public function getCollection(string $uuid): array
    {
        $query = array (
            "embed" => "logo"
        );
        $this->checkUUID($uuid);
        $url = $this->config["base"] . "/core/collections/" . $uuid;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $collection = $this->getRestApiResponse($url);
        $model = $this->dataObjects->getCollectionModel();
        $logoHref = $this->getLogoFromResponse($collection);
        // Makes extra call to get the item count.
        $itemCount = $this->getItemCount($collection["uuid"]);
        $model->setItemCount($itemCount);
        $description = "";
        $shortDescription = "";

        $abstract = array("metadata","dc.description.abstract");
        $desc = array("metadata","dc.description");

        if ($this->checkPath($abstract, $collection,self::COLLECTION )) {
            $shortDescription = $collection["metadata"]["dc.description.abstract"][0]["value"];
        }
        if ($this->checkPath($desc, $collection,self::COLLECTION )) {
            $description = $collection["metadata"]["dc.description"][0]["value"];
        }

        $model->setName($collection["name"]);
        $model->setUUID($collection["uuid"]);
        $model->setDescription($description);
        $model->setShortDescription($shortDescription);
        $model->setLogo($logoHref);
        return $model->getData();
    }

    public function getCollectionItems(string $uuid, array $params = []): array
    {
        $this->checkUUID($uuid);
        $query = array (
            "scope" => $uuid,
            "embed" => "thumbnail",
            "dsoType" => "ITEM",
            "page" => 0,
            "size" => $this->config["defaultPageSize"],
        );
        if ($this->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $url = $this->config["base"] . "/discover/search/objects";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $restResponse = $this->getRestApiResponse($url);
        $itemsArr = array();
        $result = $this->dataObjects->getObjectsList();

        $embeddedResult = array();
        $resultPath = array("_embedded", "searchResult","_embedded");
        if ($this->checkPath($resultPath, $restResponse, self::DISCOVERY)) {
            $embeddedResult = $restResponse["_embedded"]["searchResult"]["_embedded"];
        }
        foreach ($embeddedResult as &$restElement) {
            foreach ($restElement as &$item) {
                $model = $this->dataObjects->getItemModel();
                if ($this->checkKey("indexableObject", $item["_embedded"], self::ITEM)) {
                    $object = ($item["_embedded"]["indexableObject"]);
                    $model->setName($object["name"]);
                    $model->setUUID($object["uuid"]);
                    $metadata = $object["metadata"];
                    if ($this->checkKey('dc.title', $metadata, self::ITEM)) {
                        $model->setTitle($metadata["dc.title"][0]["value"]);
                    }
                    if ($this->checkKey('dc.contributor.author', $metadata, self::ITEM)) {
                        $model->setAuthor($metadata["dc.contributor.author"][0]["value"]);
                    }
                    if ($this->checkKey('dc.date.issued', $metadata, self::ITEM)) {
                        $model->setDate($metadata["dc.date.issued"][0]["value"]);
                    }
                    if ($this->checkKey('dc.description.abstract', $metadata, self::ITEM)) {
                        $model->setDescription($metadata["dc.description.abstract"][0]["value"]);
                    }
                    if ($this->checkKey('owningCollection', $object["_links"],
                        self::ITEM)) {
                        $model->setOwningCollectionHref($object["_links"]["owningCollection"]["href"]);
                    }

                    $thumbPath = array("_embedded","thumbnail","_links","content","href");
                    if ($this->checkPath($thumbPath , $object,self::COLLECTION )) {
                        $model->setThumbnail($object["_embedded"]["thumbnail"]["_links"]["content"]["href"]);
                    }

                    $itemsArr[] = $model->getData();
                }

                $totalPath = array("_embedded","searchResult","page","totalElements");
                if ($this->checkPath($totalPath, $restResponse, self::ITEM)) {
                    $result->setCount($restResponse["_embedded"]["searchResult"]["page"]["totalElements"]);
                }

                $pagination = $this->getPagination($restResponse["_embedded"]["searchResult"]);
                $result->setPagination($pagination);
            }
        }
        $result->setObjects($itemsArr);
        return $result->getData();
    }

    public function getCollectionsForSection(string $uuid, array $params = [], bool $reverseOrder = true): array
    {
        $query = array (
            "page" => 0,
            "size" => $this->config["defaultPageSize"],
            "embed" => "logo"
        );
        if ($this->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $url = $this->config["base"] . "/core/communities/" . $uuid . "/collections";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $communityCollections = $this->getRestApiResponse($url);
        if ($communityCollections == self::REQUEST_FAILED) {
            return array();
        }

        $pagination = $this->getPagination($communityCollections);
        $collections = $this->getCollections($communityCollections, $reverseOrder);
        $result = $this->dataObjects->getObjectsList();
        $result->setCount($this->getTotal($communityCollections));
        $result->setPagination($pagination);
        $result->setObjects($collections);
        return $result->getData();
    }

    public function getItem(string $uuid, bool $formatDescription = false): array
    {
        $query = array (
            "embed" => "thumbnail,owningCollection"
        );
        $url = $this->config["base"] . "/core/items/" . $uuid;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $item = $this->getRestApiResponse($url);
        $model = $this->dataObjects->getItemModel();
        $metadata = $item["metadata"];
        $model->setName($item["name"]);
        $model->setUUID($item["uuid"]);
        if ($this->checkKey("dc.title", $metadata, self::ITEM)) {
            $model->setTitle($metadata["dc.title"][0]["value"]);
        }
        if ($this->checkKey("dc.contributor.author", $metadata, self::ITEM)) {
            $model->setAuthor($metadata["dc.contributor.author"][0]["value"]);
        }
        if ($this->checkKey("dc.date.issued", $metadata, self::ITEM)) {
            $model->setDate($metadata["dc.date.issued"][0]["value"]);
        }
        if ($this->checkKey("dc.rights", $metadata, self::ITEM)) {
            $model->setRights($metadata["dc.rights"][0]["value"]);
        }
        if ($this->checkKey("dc.rights.uri", $metadata, self::ITEM)) {
            $model->setRightsLink($metadata["dc.rights.uri"][0]["value"]);
        }
        if ($this->checkKey("dc.date.issued", $metadata, self::ITEM)) {
            $model->setDate($metadata["dc.date.issued"][0]["value"]);
        }
        if ($this->checkKey("dc.description.abstract", $metadata, self::ITEM)) {
            $desc = $metadata["dc.description.abstract"][0]["value"];
            if ($formatDescription) {
                $desc = $this->formatDescription($desc);
            }
            $model->setDescription($desc);
        }

        $thumbPath = array("_embedded","thumbnail","_links","self","href");
        if ($this->checkPath($thumbPath, $item, self::ITEM)) {
            $model->setThumbnail($item["_embedded"]["thumbnail"]["_links"]["self"]["href"]);
        }

        $owningCollection = $this->getOwningCollectionFromResponse($item);
        $model->setOwningCollectionName($owningCollection["name"]);
        $model->setOwningCollectionUuid($owningCollection["uuid"]);
        $model->setOwningCollectionHref($owningCollection["href"]);

        return $model->getData();
    }

    public function getItemFiles(string $uuid, string $bundleName = "ORIGINAL"): array
    {
        $query = array (
            "size" => "9999",
            "embed.size" > "bitstreams=" . $this->config["defaultEmbeddedBitstreamParam"],
            "embed" => "bitstreams/format"
        );
        $url = $this->config["base"] . "/core/items/" . $uuid . "/bundles";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $bundles = $this->getRestApiResponse($url);
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
        $url = $this->config["base"] . "/core/bitstreams/" . $uuid;
        $thumbnail = "";
        $mainImage = "";
        $file = $this->getRestApiResponse($url);
        $model = $this->dataObjects->getBitstreamModel();
        if ($this->checkKey("self", $file["_links"], self::BITSTREAM)) {
            $thumbnail = $this->getThumbnailLink($file["_links"]["self"]["href"]);
            $mainImage = $file["_links"]["content"]["href"];
        }
        $this->getBitstreamMetadata($file, $model);
        $model->setName($file["name"]);
        $model->setUuid($file["uuid"]);
        $model->setHref($mainImage);
        $model->setMimetype($this->getBitstreamFormat($uuid));
        $model->setThumbnail($thumbnail);
        return $model->getData();
    }

    public function search(array $params = []): array
    {
        $query = array (
            "scope" => $this->config["scope"],
            "page" => "0",
            "size" => $this->config["defaultPageSize"],
            "embed" => "thumbnail,item/thumbnail"
        );
        if ($this->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        if ($this->checkKey("scope", $params, self::PARAMS)) {
            $query["scope"] = $params["scope"];
        }
        if ($this->checkKey("query", $params, self::PARAMS)) {
            $query["query"] = $params["query"];
        }
        $url = $this->config["base"] . "/discover/search/objects";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $response = $this->getRestApiResponse($url);

        $result = $this->dataObjects->getObjectsList();
        $objects = array();

        $embeddedResultPath = array("_embedded", "searchResult");
        $searchResultsPath = array("_embedded", "searchResult", "_embedded", "objects");
        $embeddedObject = array("_embedded", "indexableObject");

        if ($this->checkPath($embeddedResultPath, $response, self::DISCOVERY)) {
            $result->setCount($this->getTotal($response["_embedded"]["searchResult"]));
            $result->setPagination($this->getPagination($response["_embedded"]["searchResult"]));
        }
        if ($this->checkPath($searchResultsPath, $response, self::DISCOVERY)) {
            $respObjects = $response["_embedded"]["searchResult"]["_embedded"]["objects"];
            foreach ($respObjects as $obj) {
                if ($this->checkPath($embeddedObject, $obj, self::DISCOVERY)) {
                    $objects[] = $this->getSearchResultObj($obj["_embedded"]["indexableObject"]);
                }
            }
        }
        $result->setObjects($objects);
        return $result->getData();
    }

    /**
     * Returns the count of items for the collection with the provided uuid.
     * @param string $uuid collection uuid
     * @return string the number of items
     */
    function getItemCountForCollection(string $uuid): string {
        return $this->getItemCount($uuid);
    }

    /**
     * Sets metadata on the <code>Bitstream</code> model.
     * @param array $object the array with the metadata key
     * @param Bitstream $model the model to update
     * @return void
     */
    private function getBitstreamMetadata(array $object, Bitstream & $model): void
    {
        if ($this->checkKey("dc.title", $object["metadata"], self::BITSTREAM)) {
            $model->setTitle($object["metadata"]["dc.title"][0]["value"]);
        }
        if ($this->checkKey("iiif.label", $object["metadata"], self::BITSTREAM)) {
            $model->setLabel($object["metadata"]["iiif.label"][0]["value"]);
        }
        if ($this->checkKey("dc.description", $object["metadata"], self::BITSTREAM)) {
            $model->setDescription($object["metadata"]["dc.description"][0]["value"]);
        }
        if ($this->checkKey("dc.format.medium", $object["metadata"], self::BITSTREAM)) {
            $model->setMedium($object["metadata"]["dc.format.medium"][0]["value"]);
        }
        if ($this->checkKey("dc.format.extent", $object["metadata"], self::BITSTREAM)) {
            $model->setDimensions($object["metadata"]["dc.format.extent"][0]["value"]);
        }
        if ($this->checkKey("dc.subject.other", $object["metadata"], self::BITSTREAM)) {
            $model->setSubject($object["metadata"]["dc.subject.other"][0]["value"]);
        }
        if ($this->checkKey("dc.type", $object["metadata"], self::BITSTREAM)) {
            $model->setType($object["metadata"]["dc.type"][0]["value"]);;
        }
        if ($this->checkKey("dc.rights", $object["metadata"], self::BITSTREAM)) {
            $model->setRights($object["metadata"]["dc.rights"][0]["value"]);;
        }
        if ($this->checkKey("dc.rights.uri", $object["metadata"], self::BITSTREAM)) {
            $model->setRightsLink($object["metadata"]["dc.rights.uri"][0]["value"]);;
        }
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
        $item = $this->getRestApiResponse($url);

        if ($this->checkKey("page", $item, self::DISCOVERY)) {
            return $item["page"]["totalElements"];
        }
        return "0";
    }

    private function getSubSectionCountForSection(array $section): string
    {
        $sectionCountPath = array("_embedded", "subcommunities", "page", "totalElements");
        if ($this->checkPath($sectionCountPath, $section, self::COMMUNITY)) {
            return $section["_embedded"]["subcommunities"]["page"]["totalElements"];
        }
        return "0";
    }

    private function getCollectionCountForSection(array $section): string
    {
        $collectionCountPath = array("_embedded", "collections", "page", "totalElements");
        if ($this->checkPath($collectionCountPath, $section, self::COLLECTION)) {
            return $section["_embedded"]["collections"]["page"]["totalElements"];
        }
        return "0";
    }
    private function getLogoFromResponse(?array $response) : string {
        if ($response) {

            $logoPath = array("_embedded", "logo");
            $logoLinkPath = array("_links","content", "href");

            if ($this->checkPath($logoPath, $response, self::LOGO)) {
                // response can be NULL
                if (!is_null($response["_embedded"]["logo"])) {
                    if ($this->checkPath($logoLinkPath, $response["_embedded"]["logo"], self::LOGO)) {
                        return $response["_embedded"]["logo"]["_links"]["content"]["href"];
                    }
                }
            }
        } else {
            error_log("WARNING: DSpace response did not include a logo");
        }
        return "";
    }

    private function getOwningCollectionFromResponse(?array $response) : array {
        $owner = array();
        if ($response) {

            $embedPath = array("_embedded", "owningCollection");
            $owingLinkPath = array("_links", "self", "href");

            if ($this->checkPath($embedPath, $response, self::COLLECTION)) {
                $owner["name"] = $response["_embedded"]["owningCollection"]["name"];
                $owner["uuid"] = $response["_embedded"]["owningCollection"]["uuid"];
                if ($this->checkPath($owingLinkPath, $response["_embedded"]["owningCollection"], self::COLLECTION)) {
                    $owner["href"] = $response["_embedded"]["owningCollection"]["_links"]["self"]["href"];
                }
            }
        } else {
            error_log("DSpace response did not include a logo");
        }
        return $owner;
    }

    /**
     * Gets the <code>SearchResult</code> object.
     * @param $data array the DSpace object
     * @return array
     */
    private function getSearchResultObj(array $data): array
    {
        $object = $this->dataObjects->getSearchObject();
        if ($this->checkKey("name", $data, self::DISCOVERY)) {
            $object->setName($data["name"]);
        }
        if ($this->checkKey("uuid", $data, self::DISCOVERY)) {
            $object->setUuid($data["uuid"]);
        }
        if ($this->checkKey("type", $data, self::DISCOVERY)) {
            $object->setType($data["type"]);
        }
        if ($this->checkKey("metadata", $data, self::DISCOVERY)) {
            if ($this->checkKey("dc.title", $data["metadata"], self::DISCOVERY)) {
                $object->setTitle($data["metadata"]["dc.title"][0]["value"]);
            }
            if ($this->checkKey("dc.description.abstract", $data["metadata"], self::DISCOVERY)) {
                $object->setDescription($data["metadata"]["dc.description.abstract"][0]["value"]);
            }
            if ($this->checkKey("dc.description", $data["metadata"], self::DISCOVERY)) {
                $object->setDescription($data["metadata"]["dc.description"][0]["value"]);
            }
            if ($this->checkKey("dc.date.issued", $data["metadata"], self::DISCOVERY)) {
                $object->setDate($data["metadata"]["dc.date.issued"][0]["value"]);
            }
            if ($this->checkKey("dc.contributor.author", $data["metadata"], self::DISCOVERY)) {
                $object->setCreator($data["metadata"]["dc.contributor.author"][0]["value"]);
            }
        }
        $thumbNamePath = array("_embedded","thumbnail","name");
        $thumbLinkPath = array("_embedded", "thumbnail","_links","content","href");

        if ($this->checkPath($thumbNamePath, $data, self::DISCOVERY)) {
            $object->setThumbnailName($data["_embedded"]["thumbnail"]["name"]);
        }
        if ($this->checkPath($thumbLinkPath, $data, self::DISCOVERY)) {
            $object->setThumbnailHref($data["_embedded"]["thumbnail"]["_links"]["content"]["href"]);
        }

        return $object->getData();
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
        $format = $this->getRestApiResponse($url);
        if ($this->checkKey("mimetype", $format)) {
            return $format["mimetype"];
        }
        return "";
    }

    private function getThumbnailLink(string $href): string
    {
        $images = $this->getRestApiResponse($href);
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
        $bundlesPath = array("_embedded","bundles");
        $bundle = array();
        if ($this->checkPath($bundlesPath, $bundles, self::BUNDLE)) {
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
        $collectionMap = array();
        if ($this->checkPath($embeddedCollectionsPath, $communityCollections, self::COLLECTION)) {
            foreach ($communityCollections["_embedded"]["collections"] as $collection) {
                $model = $this->dataObjects->getCollectionModel();
                if ($this->config["retrieveItemCounts"]) {
                    $itemCount = $this->getItemCount($collection["uuid"]);
                    $model->setItemCount($itemCount);
                }
                $model->setLogo($this->getLogoFromResponse($collection));
                $model->setName($collection["name"]);
                $model->setUUID($collection["uuid"]);
                if ($this->checkKey("metadata", $collection)) {
                    if ($this->checkKey("dc.description", $collection["metadata"])) {
                        $model->setDescription($collection["metadata"]["dc.description"][0]["value"]);
                    }
                    if ($this->checkKey("dc.description.abstract", $collection["metadata"])) {
                        $model->setShortDescription($collection["metadata"]["dc.description.abstract"][0]["value"]);
                    }
                }
                $collectionMap[] = $model->getData();
            }

            if ($reverseOrder) {
                return array_reverse($collectionMap, false);
            }
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
        $bitstreamsTotal = array("_embedded","bitstreams","page","totalElements");
        $bitstreamObjects = array("_embedded","bitstreams","_embedded","bitstreams");

        $result = $this->dataObjects->getObjectsList();
        $bitstreams = array();
        if ($this->checkPath($bitstreamsTotal, $bundle, self::BUNDLE)) {
            $result->setCount($bundle["_embedded"]["bitstreams"]["page"]["totalElements"]);
        }
        if ($this->checkPath($bitstreamObjects, $bundle, self::BUNDLE)) {
            $bitstreams = $bundle["_embedded"]["bitstreams"]["_embedded"]["bitstreams"];
        }
        $imageArr = array();

        foreach ($bitstreams as $file) {;
            $model = $this->dataObjects->getBitstreamModel();
            $thumbnail = "";
            $mainImage = "";
            $mimeType = "";
            if ($this->checkKey("_links", $file, self::BITSTREAM)) {
                if ($this->checkKey("thumbnail", $file["_links"], self::BITSTREAM)) {
                    $thumbnail = $this->getThumbnailLink($file["_links"]["thumbnail"]["href"]);
                }
                if ($this->checkKey("content", $file["_links"], self::BITSTREAM)) {
                    $mainImage = $file["_links"]["content"]["href"];
                }
                if ($this->checkKey("_embedded", $file, self::BITSTREAM)) {
                    if ($this->checkKey("format", $file["_embedded"], self::BITSTREAM)) {
                        $mimeType = $file["_embedded"]["format"]["mimetype"];
                    }
                }
                $this->getBitstreamMetadata($file, $model);
            }
            $model->setName($file["name"]);
            $model->setUuid($file["uuid"]);
            $model->setHref($mainImage);
            $model->setMimetype($mimeType);
            $model->setThumbnail($thumbnail);
            $imageArr[] = $model->getData();
        }
        $result->setObjects($imageArr);
        return $result;
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
            if ($this->checkKey("_links", $linkData, self::BITSTREAM)) {
                $imageLinks = $linkData["_links"];
                if ($imageLinks) {
                    if ($this->checkKey("content", $imageLinks, self::BITSTREAM)) {
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
     * @param array $object
     * @return array
     */
    private function getPagination(array $object): array {
        $paginationModel = $this->dataObjects->getPaginationModel();
        if ($this->checkKey("_links", $object, self::COMMUNITY)) {
            if ($this->checkKey("next", $object["_links"], self::COMMUNITY)) {
                $parts = parse_url($object["_links"]["next"]["href"]);
                parse_str($parts['query'], $query);
                $paginationModel->setNext($query['page'],$query['size']);
            }
            if ($this->checkKey("prev", $object["_links"], self::COMMUNITY)) {
                $parts = parse_url($object["_links"]["prev"]["href"]);
                parse_str($parts['query'], $query);
                $paginationModel->setPrev($query['page'],$query['size']);
            }
        }

        return $paginationModel->getData();
    }

    private function getTotal(array $object): string {
        $totalPath = array("page", "totalElements");
        if ($this->checkPath($totalPath, $object, self::COMMUNITY)) {
            return $object["page"]["totalElements"];
        }
        return "0";
    }

    private function checkPath(array $path, array $array, string $type = "") : bool {
        if (!$path || count($path) == 0) {
            error_log("ERROR: Invalid path array.");
            return false;
        }
        $keyList = $path[0];
        foreach($path as $key) {
            if (!$this->checkKey($keyList, $array, $type)) {
                $keyList .= $key;
                return false;
            }
        }
        return true;
    }

    /**
     * Utility method for checking whether a key exists in an array.
     * @param $key string the key to look for
     * @param $array mixed the array that contains the key or null
     * @param $type string optional DSO type for logging
     * @return bool
     */
    private function checkKey(string $key, mixed $array, string $type = ""): bool
    {
        if(!is_null($array)) {
            $found = array_key_exists($key, $array);
            if (!$found && $this->config["debug"]) {
                error_log("WARNING: Failed to find the key '" . $key . "' in the DSpace " . $type . " response.");
            }
            return $found;
        } else {
            // NOTE: It might be a good idea to throw an exception here.
            error_log("ERROR: Checking key: " . $key . ". A null array was provided to the checkKey function. This should not
                happen. There was likely a problem parsing the Dspace API response.");
        }
        return false;
    }


    /**
     * Utility method for DSpace API requests. Uses curl.
     * @param $url string the fully qualified URL
     * @return mixed the DSpace API response
     */
    private function getRestApiResponse(string $url): mixed
    {
        try {

            if ($this->config["debug"]) {
                error_log("DEBUG: DSpace API request: " . $url);
            }
            set_error_handler(
                function ($err_severity, $err_msg, $err_file, $err_line)
                { throw new ErrorException( $err_msg, 0, $err_severity, $err_file, $err_line ); },
                E_WARNING
            );

            if ( ! function_exists( 'curl_init' ) ) {
                die( 'The cURL library is not installed.' );
            }
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            $response = curl_exec( $ch );
            curl_close( $ch );
            restore_error_handler();
            if ($this->config["debug"]) {
                error_log("DEBUG: DSpace REST API response: " . $response);
            }

        } catch (Exception $err) {
            error_log("ERROR: DSpace API request did not return data.");
            error_log($err);
            if (!headers_sent()) {
                $this->utils->outputJSON(self::REQUEST_FAILED,
                    array("HTTP/1.1 400 Invalid Request"));
            }
            return self::REQUEST_FAILED;
        }

        return json_decode($response, true);
    }


    private function checkUUID($uuid): void
    {
        if (!$uuid) {
            $this->utils->outputJSON("Required DSpace UUID is missing from the request.",
                array("HTTP/1.1 400 Invalid Request"));
            exit;
        }

    }

}
