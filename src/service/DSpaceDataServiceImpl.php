<?php

require __DIR__ . "/../Configuration.php";
require __DIR__ . "/DSpaceDataService.php";
require __DIR__ . "/DataObjects.php";
require_once __DIR__ . "/../Utils.php";

/**
 * PHP service class for retrieving Community, Collection, Item, and Bitstream
 * information from the DSpace REST API.
 */
class DSpaceDataServiceImpl implements DSpaceDataService
{

    private array $config;

    private Utils $utils;

    private DataObjects $dataObjects;

    private string $defaultScope;

    private const ITEM = "ITEM";
    private const COMMUNITY = "COMMUNITY";
    private const COLLECTION = "COLLECTION";
    private const DISCOVERY = "DISCOVERY";
    private const BUNDLE = "BUNDLE";
    private const BITSTREAM = "BITSTREAM";
    private const PARAMS = "REQUEST PARAMETER";

    private const REQUEST_FAILED = "DSPACE_REQUEST_ERROR";

    public function __construct()
    {
        $settings = new Configuration();
        $this->config = $settings->getConfig();
        $this->dataObjects = new DataObjects();
        $this->utils = new Utils();

        $this->defaultScope = $this->config["scope"];
    }

    public function getSection(string $uuid): array {
        $this->checkUUID($uuid);
        $query = array (
            "embed" => "logo"
        );
        $url = $this->config["base"] . "/core/communities/" . $uuid;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $model = $this->dataObjects->getCommunityModel();
        $community = $this->getRestApiResponse($url);
        $logoHref = $this->getCommunityLogo($community["uuid"]);
        $model->setName($community["name"]);
        $model->setUUID($community["uuid"]);
        $model->setLogo($logoHref);
        return $model->getData();
    }

    public function getTopLevelSections($params = []) : array {
        $sectionsMap = array();
        $query = array (
            "page" => 0,
            "size" => $this->config["defaultPageSize"],
            "embed" => "logo,subcommunities/logo",
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
        $model = $this->dataObjects->getCommunityModel();
        if ($this->checkKey("communities", $sections["_embedded"], self::COMMUNITY)) {
            foreach ($sections["_embedded"]["communities"] as $section) {
                $model->setName($section["name"]);
                $model->setUUID($section["uuid"]);
                $model->setLogo($this->getLogoFromResponse($section));
                if ($this->checkKey("_embedded", $section, self::COMMUNITY)) {
                    if ($this->checkKey("subcommunities", $section["_embedded"], self::COMMUNITY)) {
                        if ($this->checkKey("_embedded", $section["_embedded"]["subcommunities"], self::COMMUNITY)) {
                            if ($this->checkKey("subcommunities", $section["_embedded"]["subcommunities"]["_embedded"], self::COMMUNITY)) {
                                $model->setSubsectionCount(count($section["_embedded"]["subcommunities"]["_embedded"]["subcommunities"]));
                            }
                        }
                    }
                }
                $sectionsMap[$section["name"]] = $model->getData();
            }
            if ($this->checkKey("page", $sections)) {
                if ($this->checkKey("totalElements", $sections["page"])) {
                    $result->setCount($sections["page"]["totalElements"]);
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
            "embed" => "logo,collections/logo"
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
        $model = $this->dataObjects->getCommunityModel();
        $subCommunities = $this->getRestApiResponse($url);
        if ($this->checkKey("subcommunities", $subCommunities["_embedded"], self::COMMUNITY)) {
            foreach ($subCommunities["_embedded"]["subcommunities"] as $subComm) {
                $model->setName($subComm["name"]);
                $model->setUUID($subComm["uuid"]);
                $model->setLogo($this->getLogoFromResponse($subComm));
                if ($this->checkKey("_embedded", $subComm, self::COMMUNITY)) {
                    if ($this->checkKey("collections", $subComm["_embedded"], self::COMMUNITY)) {
                        if ($this->checkKey("_embedded", $subComm["_embedded"]["collections"], self::COMMUNITY)) {
                            if ($this->checkKey("collections", $subComm["_embedded"]["collections"]["_embedded"], self::COMMUNITY)) {
                                $model->setSubsectionCount(count($subComm["_embedded"]["collections"]["_embedded"]["collections"]));
                            }
                        }
                    }
                }
                $subcommitteeMap[$subComm["name"]] = $model->getData();
            }
            $pagination = $this->getPagination($subCommunities);

            $result->setPagination($pagination);
            $result->setObjects($subcommitteeMap);
        }
        return $result->getData();
    }


    public function getOwningCollectionByHref(string $href): array
    {
        $collection = $this->getRestApiResponse($href);
        return array(
            "name" => $collection["name"],
            "href" => $href
        );
    }

    public function getOwningCollection(string $uuid): array
    {
        $uri = $this->config["base"] . "/core/items/" . $uuid . "/owningCollection";
        $collection = $this->getRestApiResponse($uri);
        return array(
            "name" => $collection["name"],
            "uuid" => $collection["uuid"],
            "href" => $collection["_links"]["self"]["href"]
        );
    }

    public function getCollection(string $uuid): array
    {
        $this->checkUUID($uuid);
        $url = $this->config["base"] . "/core/collections/" . $uuid;
        $collection = $this->getRestApiResponse($url);
        $logoHref = $this->getLogoFromResponse($collection);
        $itemCount = $this->getItemCount($collection["uuid"]);
        $description = "";
        $shortDescription = "";
        if ($this->checkKey("metadata", $collection, self::COLLECTION)) {
            if ($this->checkKey("dc.description.abstract", $collection["metadata"], self::COLLECTION)) {
                $shortDescription = $collection["metadata"]["dc.description.abstract"][0]["value"];
            }
            if ($this->checkKey("dc.description", $collection["metadata"], self::COLLECTION)) {
                $description = $collection["metadata"]["dc.description"][0]["value"];
            }
        }
        $model = $this->dataObjects->getCollectionModel();
        $model->setName($collection["name"]);
        $model->setUUID($collection["uuid"]);
        $model->setDescription($description);
        $model->setShortDescription($shortDescription);
        $model->setLogo($logoHref);
        $model->setCount($itemCount);
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
            "size" => $this->config["defaultPageSize"]
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
        if ($this->checkKey("searchResult", $restResponse["_embedded"], self::DISCOVERY)) {
            if ($this->checkKey("_embedded", $restResponse["_embedded"]["searchResult"], self::DISCOVERY)) {
                foreach ($restResponse["_embedded"]["searchResult"]["_embedded"] as &$restElement) {
                    foreach ($restElement as &$item) {
                        $model = $this->dataObjects->getItemModel();
                        if ($this->checkKey("indexableObject", $item["_embedded"], self::ITEM)) {
                            $object = ($item["_embedded"]["indexableObject"]);
                            $model->setName($object["name"]);
                            $model->setUUID($object["uuid"]);
                            $metadata = $object["metadata"];
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
                                $model->setOwningCollection($object["_links"]["owningCollection"]["href"]);
                            }
                            if ($this->checkKey('thumbnail', $object["_embedded"], self::ITEM)) {
                                if ($this->checkKey('_links', $object["_embedded"]["thumbnail"],
                                    self::ITEM)) {
                                    if ($this->checkKey('content', $object["_embedded"]["thumbnail"]["_links"],
                                        self::ITEM)) {
                                        if (
                                            $this->checkKey('href', $object["_embedded"]["thumbnail"]["_links"]["content"],
                                                self::ITEM)) {
                                            $model->setLogo($object["_embedded"]["thumbnail"]["_links"]["content"]["href"]);
                                        }
                                    }
                                }
                            }
                            $itemsArr[] = $model->getData();
                        }
                    }
                }
                $pagination = $this->getPagination($restResponse["_embedded"]["searchResult"]);
                $result->setPagination($pagination);
            }
        }
        $result->setObjects($itemsArr);
        return $result->getData();

    }

    private function getLogoFromResponse(?array $response) : string {
        if ($response) {
            if ($this->checkKey("_embedded", $response)) {
                if ($this->checkKey("logo", $response["_embedded"])) {
                    if ($this->checkKey("_links", $response["_embedded"]["logo"])) {
                        if ($this->checkKey("content", $response["_embedded"]["logo"]["_links"])) {
                            if ($this->checkKey("href", $response["_embedded"]["logo"]["_links"]["content"])) {
                                return $response["_embedded"]["logo"]["_links"]["content"]["href"];
                            }
                        }
                    }
                }
            }
        } else {
            error_log("DSpace response did not include a logo");
        }
        return "";
    }
    public function getCommunityLogo(string $uuid): string
    {
        $this->checkUUID($uuid);
        $url = $this->config["base"] . "/core/communities/" . $uuid . "/logo";
        $logoMetadata = $this->getRestApiResponse($url);
        return $this->getImageUrl($logoMetadata);
    }

    public function getCollectionLogo(string $uuid): string
    {
        $url = $this->config["base"] . "/core/collections/" . $uuid . "/logo";
        $logoMetadata = $this->getRestApiResponse($url);
        return $this->getImageUrl($logoMetadata);
    }

    public function getItemThumbnail(string $uuid): string
    {
        $url = $this->config["base"] . "/core/items/" . $uuid . "/thumbnail";
        $thumbnailMetadata = $this->getRestApiResponse($url);
        return $this->getImageUrl($thumbnailMetadata);
    }

    private function getCollectionCountForCommunity(?array $collections) {
        return count($collections);
    }

    /**
     * @param string $communityUuid
     * @param array $params
     * @return string
     */
    public function getCommunityCollectionCount(string $communityUuid, array $params = []): string
    {
        $query = array (
            "page" => 0,
            "size" => $this->config["defaultPageSize"]
        );
        if ($this->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $url = $this->config["base"] . "/core/communities/" . $communityUuid . "/collections";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $count = $this->getRestApiResponse($url);
        if ($count == self::REQUEST_FAILED) {
            return $count;
        }
        return $count["page"]["totalElements"];
    }

    /**
     * DSpace currently does not return the item count with the collection responses.
     * This method makes a title browse request for the collection and returns the
     * number of items. It is not efficient and can be slow.
     * @param string $uuid
     * @param array $params
     * @return string
     */
    public function getItemCount(string $uuid): string
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
        return "unknown";
    }

    public function getCollectionsForCommunity(string $uuid, array $params = [], bool $reverseOrder = true): array
    {
        $query = array (
            "page" => 0,
            "size" => $this->config["defaultPageSize"]
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
        if ($this->checkKey("page", $communityCollections)) {
            if ($this->checkKey("totalElements", $communityCollections["page"])) {
                $result->setCount($communityCollections["page"]["totalElements"]);
            }
        }
        $result->setPagination($pagination);
        $result->setObjects($collections);
        return $result->getData();
    }

    public function getItem(string $uuid, bool $formatDescription = false): array
    {
        $url = $this->config["base"] . "/core/items/" . $uuid;
        $item = $this->getRestApiResponse($url);
        $model = $this->dataObjects->getItemModel();
        $metadata = $item["metadata"];
        $model->setName($item["name"]);
        $model->setUUID($item["uuid"]);
        if ($this->checkKey("dc.contributor.author", $metadata, self::ITEM)) {
            $model->setAuthor($metadata["dc.contributor.author"][0]["value"]);
        }
        if ($this->checkKey("dc.description.abstract", $metadata, self::ITEM)) {
            $desc = $metadata["dc.description.abstract"][0]["value"];
            if ($formatDescription) {
                $desc = $this->formatDescription($desc);
            }
            $model->setDescription($desc);
        }
        if ($this->checkKey("owningCollection", $item["_links"], self::ITEM)) {
            $model->setOwningCollection($item["_links"]["owningCollection"]["href"]);
        }
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
                return $this->getBitstreams($bundle);
            } catch (Exception $err) {
                error_log($err, 0);
                return array();
            }
        } else {
            error_log("ERROR: The requested bundle was not found: " . $bundleName);
            return array();
        }

    }

    public function getFileLink(string $uuid): string
    {
        return $this->config["base"] . "/core/bitstreams/" . $uuid . "/content";
    }

    public function getBitstreamData(string $uuid): array
    {
        $query = array (
            "embed" => "bitstreams/format"
        );
        $url = $this->config["base"] . "/core/bitstreams/" . $uuid;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $thumbnail = "";
        $mainImage = "";
        $file = $this->getRestApiResponse($url);
        $model = $this->dataObjects->getBitstreamModel();
        if ($this->checkKey("self", $file["_links"], self::BITSTREAM)) {
            $thumbnail = $this->getThumbnail($file["_links"]["self"]["href"]);
            $mainImage = $file["_links"]["content"]["href"];
        }
        if ($this->checkKey("dc.title", $file["metadata"], self::BITSTREAM)) {
            $model->setName($file["metadata"]["dc.title"][0]["value"]);
        }
        if ($this->checkKey("iiif.label", $file["metadata"], self::BITSTREAM)) {
            $model->setLabel($file["metadata"]["iiif.label"][0]["value"]);
        }
        if ($this->checkKey("dc.description", $file["metadata"], self::BITSTREAM)) {
            $model->setDescription($file["metadata"]["dc.description"][0]["value"]);
        }
        if ($this->checkKey("dc.format.medium", $file["metadata"], self::BITSTREAM)) {
            $model->setMedium($file["metadata"]["dc.format.medium"][0]["value"]);
        }
        if ($this->checkKey("dc.format.extent", $file["metadata"], self::BITSTREAM)) {
            $model->setDimensions($file["metadata"]["dc.format.extent"][0]["value"]);
        }
        if ($this->checkKey("dc.subject.other", $file["metadata"], self::BITSTREAM)) {
            $model->setSubject($file["metadata"]["dc.subject.other"][0]["value"]);
        }
        if ($this->checkKey("dc.type", $file["metadata"], self::BITSTREAM)) {
            $model->setType($file["metadata"]["dc.type"][0]["value"]);;
        }
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
        if ($this->checkKey("_embedded", $response, self::DISCOVERY)) {
            if ($this->checkKey("searchResult", $response["_embedded"], self::DISCOVERY)) {
                $result->setCount($response["_embedded"]["searchResult"]["page"]["totalElements"]);
                $result->setPagination($this->getPagination($response["_embedded"]["searchResult"]));
                if ($this->checkKey("_embedded", $response["_embedded"]["searchResult"], self::DISCOVERY)) {
                    if ($this->checkKey("objects", $response["_embedded"]["searchResult"]["_embedded"], self::DISCOVERY)) {
                        $respObjects = $response["_embedded"]["searchResult"]["_embedded"]["objects"];
                        foreach ($respObjects as $obj) {
                            $object = $this->getSearchResultObj($obj["_embedded"]["indexableObject"]);
                            $objects[] = $object;
                        }
                    }
                }
            }
        }
        $result->setObjects($objects);
        return $result->getData();
    }

    private function getSearchResultObj($data): array
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
            if ($this->checkKey("dc.date.issued", $data["metadata"], self::DISCOVERY)) {
                $object->setDate($data["metadata"]["dc.date.issued"][0]["value"]);
            }
            if ($this->checkKey("dc.contributor.author", $data["metadata"], self::DISCOVERY)) {
                $object->setCreator($data["metadata"]["dc.contributor.author"][0]["value"]);
            }
        }
        if ($this->checkKey("thumbnail", $data, self::DISCOVERY)) {
            if ($this->checkKey("name", $data["thumbnail"], self::DISCOVERY)) {
                $object->setThumbnailName($data["thumbnail"]["name"]);
            }
            if ($this->checkKey("href", $data["thumbnail"], self::DISCOVERY)) {
                $object->setThumbnailHref($data["thumbnail"]["href"]);
            }
        }
        return $object->getData();
    }

    private function getBitstreamFormat($uuid) {
        $url = $this->config["base"] . "/core/bitstreams/" . $uuid . "/format";
        $format = $this->getRestApiResponse($url);
        return $format["mimetype"];

    }
    private function getThumbnail(string $href): string
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
        $bundle = array();
        foreach($bundles["_embedded"]["bundles"] as &$currentBundle) {
            if ($currentBundle["name"] == $bundleName) {
                $bundle = $currentBundle;
            }
        }
        return $bundle;
    }

    /**
     * Returns collection information from DSpace community metadata with embedded collections.
     * @param $communityCollections array DSpace community metadata
     * @param $reverseOrder boolean optional value that reverses order of the array (defaults to true)
     * @return array
     * <code>
     * array(
     *    array(
     *      "name" => string,
     *      "href" => string,
     *      "thumbnail" => string,
     *      "uuid" => string,
     *      "mimetype" => string
     *    )
     * )
     * </code>
     */
    private function getCollections(array $communityCollections, bool $reverseOrder = true): array
    {
        $collectionMap = array();
        if ($this->checkKey("_embedded", $communityCollections)) {
            if ($this->checkKey("collections", $communityCollections["_embedded"])) {
                foreach ($communityCollections["_embedded"]["collections"] as $collection) {
                    $logoHref = $this->getCollectionLogo($collection["uuid"]);
                    $current = array(
                        "name" => $collection["name"],
                        "uuid" => $collection["uuid"],
                        "logo" => $logoHref
                    );
                    $collectionMap[] = $current;
                }

                if ($reverseOrder) {
                    return array_reverse($collectionMap, false);
                }
            }
        }

        return $collectionMap;
    }

    /**
     * Gets information about bitstreams (e.g. images) in the DSpace bundle.
     * @param $bundle array the DSpace bundle metadata
     * <code>
     *      array (
     *        "name" => string,
     *        "href" => string,
     *        "thumbnail" => string,
     *        "uuid" => string,
     *        "mimetype" => string
     *  )
     *  </code>
     * @throws Exception (see log file)
     */
    private function getBitstreams(array $bundle): array
    {
        $bitstreams = array();
        if ($this->checkKey("bitstreams", $bundle["_embedded"], self::BUNDLE)) {
            if ($this->checkKey("_embedded", $bundle["_embedded"]["bitstreams"], self::BUNDLE)) {
                if ($this->checkKey("bitstreams", $bundle["_embedded"]["bitstreams"]["_embedded"],
                    self::BUNDLE)) {
                    $bitstreams = $bundle["_embedded"]["bitstreams"]["_embedded"]["bitstreams"];
                }
            }
        }
        $imageArr = array();
        foreach ($bitstreams as $image) {
            $model = $this->dataObjects->getBitstreamModel();
            $thumbnail = "";
            $mainImage = "";
            $mimeType = "";
            if ($this->checkKey("_links", $image, self::BITSTREAM)) {
                if ($this->checkKey("self", $image["_links"], self::BITSTREAM)) {
                    $thumbnail = $this->getThumbnail($image["_links"]["self"]["href"]);
                    $mainImage = $image["_links"]["content"]["href"];
                }
                if ($this->checkKey("_embedded", $image, self::BITSTREAM)) {
                    if ($this->checkKey("format", $image["_embedded"], self::BITSTREAM)) {
                        $mimeType = $image["_embedded"]["format"]["mimetype"];
                    }
                }

                if ($this->checkKey("iiif.label", $image["metadata"], self::BITSTREAM)) {
                    $model->setLabel($image["metadata"]["iiif.label"][0]["value"]);
                }
                if ($this->checkKey("dc.description", $image["metadata"], self::BITSTREAM)) {
                    $model->setDescription($image["metadata"]["dc.description"][0]["value"]);
                }
                if ($this->checkKey("dc.format.medium", $image["metadata"], self::BITSTREAM)) {
                    $model->setMedium($image["metadata"]["dc.format.medium"][0]["value"]);
                }
                if ($this->checkKey("dc.format.extent", $image["metadata"], self::BITSTREAM)) {
                    $model->setDimensions($image["metadata"]["dc.format.extent"][0]["value"]);
                }
                if ($this->checkKey("dc.subject.other", $image["metadata"], self::BITSTREAM)) {
                    $model->setSubject($image["metadata"]["dc.subject.other"][0]["value"]);
                }
                if ($this->checkKey("dc.type", $image["metadata"], self::BITSTREAM)) {
                    $model->setType($image["metadata"]["dc.type"][0]["value"]);;
                }
            }

            $model->setName($image["name"]);
            $model->setUuid($image["uuid"]);
            $model->setHref($mainImage);
            $model->setMimetype($mimeType);
            $model->setThumbnail($thumbnail);
            $imageArr[] = $model->getData();
        }
        return $imageArr;
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
            error_log("ERROR: A null array was provided to the checkKey function. This should not
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
