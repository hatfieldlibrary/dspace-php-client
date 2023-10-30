<?php


require __DIR__ . "/../Configuration.php";
require __DIR__ . "/DSpaceDataService.php";
require __DIR__ . "/DataObjects.php";
require_once __DIR__ . "/../Utils.php";

/**
 * PHP service class for retrieving Community, Collection, Item, and Bitstream
 * information from the DSpace REST API.
 */
class DSpaceDataServiceImpl implements DSpaceDataService {

    private array $config;

    private Utils $utils;

    private DataObjects $dataObjects;

    private const ITEM = "ITEM";
    private const COMMUNITY = "COMMUNITY";
    private const COLLECTION = "COLLECTION";
    private const DISCOVERY = "DISCOVERY";
    private const BUNDLE = "BUNDLE";
    private const BITSTREAM = "BITSTREAM";
    private const PARAMS = "REQUEST PARAMETER";

    private const REQUEST_FAILED = "DSPACE_REQUEST_ERROR";

    public function __construct() {
        $settings = new Configuration();
        $this->config = $settings->getConfig();
        $this->dataObjects = new DataObjects();
        $this->utils = new Utils();
    }

    public function getSubCommunities(string $uuid, array $params = []): array
    {
        $this->checkUUID($uuid);
        $subcommitteeMap = array();
        $query = array (
            "page" => 0,
            "pageSize" => $this->config["defaultPageSize"]
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
        $model = $this->dataObjects->getCommunityModel();
        $SubCommunities = $this->getRestApiResponse($url);
        if ($this->checkKey("subcommunities", $SubCommunities["_embedded"], self::COMMUNITY)) {
            foreach ($SubCommunities["_embedded"]["subcommunities"] as $subComm) {
                $logoHref = $this->getCommunityLogo($subComm["uuid"]);
                $count = $this->getCollectionCount($subComm["uuid"]);
                $model->setName($subComm["name"]);
                $model->setUUID($subComm["uuid"]);
                $model->setLogo($logoHref);
                $model->setCount($count);
                $subcommitteeMap[$subComm["name"]] = $model->getData();
            }
        }
        return $subcommitteeMap;
    }


    public function getOwningCollection(string $href): array
    {
        $collection = $this->getRestApiResponse($href);
        return array(
            "name" => $collection["name"],
            "href" => $href
        );
    }

    public function getCollection(string $uuid): array
    {
        $this->checkUUID($uuid);
        $url = $this->config["base"] . "/core/collections/" . $uuid;
        $collection = $this->getRestApiResponse($url);
        $logoHref = $this->getCollectionLogo($collection["uuid"]);
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
            "dsoType" => "ITEM",
            "page" => 0,
            "pageSize" => $this->config["defaultPageSize"]
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
                            if ($this->checkKey('thumbnail', $object["_links"], self::ITEM)) {
                                $model->setLogo($this->getItemThumbnail($object["uuid"]));
                            }
                            $itemsArr[] = $model->getData();
                        }
                    }
                }
            }
        }
        return $itemsArr;

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

    public function getCollectionCount(string $uuid, array $params = []): string
    {
        $query = array (
            "page" => 0,
            "pageSize" => $this->config["defaultPageSize"]
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
        $count = $this->getRestApiResponse($url);
        if ($count == self::REQUEST_FAILED) {
            return $count;
        }
        return $count["page"]["totalElements"];
    }

    private function getItemCount(string $uuid, array $params = []): string
    {
        $query = array (
            "page" => 0,
            "pageSize" => $this->config["defaultPageSize"],
            "embed" => "thumbnail",
            "dsoType" => "ITEM"
        );
        if ($this->checkKey("page", $params, self::PARAMS)) {
            $query["page"] = $params["page"];
        }
        if ($this->checkKey("pageSize", $params, self::PARAMS)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $query["scope"] = $uuid;
        $url = $this->config["base"] . "/discover/search/objects?" . $uuid;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $item = $this->getRestApiResponse($url);
        if ($this->checkKey("totalElements", $item["_embedded"]["searchResult"]["page"], self::DISCOVERY)) {
            return $item["_embedded"]["searchResult"]["page"]["totalElements"];
        }
        return "unknown";
    }

    public function getCollectionsForCommunity(string $uuid, array $params = [], bool $reverseOrder = true): array
    {
        $query = array (
            "page" => 0,
            "pageSize" => $this->config["defaultPageSize"]
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
        return $this->getCollections($communityCollections, $reverseOrder);
    }

    public function getItem(string $uuid, bool $formatDescription = false, string $bundleName = "ORIGINAL"): array
    {
        $url = $this->config["base"] . "/core/items/" . $uuid;
        $item = $this->getRestApiResponse($url);
        $files = $this->getItemFiles($uuid, $bundleName);
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
        $model->setBitstreams($files);
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
        $url = $this->config["base"] . "/core/bitstreams/" . $uuid;
        $image = $this->getRestApiResponse($url);
        $model = $this->dataObjects->getBitstreamModel();
        if ($this->checkKey("dc.title", $image["metadata"], self::BITSTREAM)) {
            $model->setTitle($image["metadata"]["dc.title"][0]["value"]);
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

        return $model->getData();
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
        foreach ($communityCollections["_embedded"]["collections"] as $collection) {
            $logoHref = $this->getCollectionLogo($collection["uuid"]);
            $count = $this->getItemCount($collection["uuid"]);
            $current = array(
                "name" => $collection["name"],
                "uuid" => $collection["uuid"],
                "logo" => $logoHref,
                "count" => $count
            );
            $collectionMap[] = $current;
        }
        if ($reverseOrder) {
            return array_reverse($collectionMap, false);
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
            }
            $current = array (
                "name" => $image["name"],
                "href" => $mainImage,
                "thumbnail" => $thumbnail,
                "uuid" => $image["uuid"],
                "mimetype" => $mimeType
            );
            $imageArr[] = $current;
        }
        return $imageArr;
    }

    /**
     * Takes as input the DSpace metadata for the image and returns the URL
     * for retrieving the image content (or default image if not found).
     * @param $linkData array DSpace metadata for the image
     * @return string the content URL
     */
    private function getImageUrl(array $linkData) : string
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
            // Set debug to true in configuration to see missing metadata fields in the log.
            if (!$found && $this->config["debug"]) {
                error_log("DEBUG: Could not find the key '" . $key . "' in the DSpace " . $type . " data.");
            }
            return $found;
        } else {
                // NOTE: It might be a good idea to throw an exception here.
                error_log("WARNING: A null array was provided to the checkKey function. This should not
                happen. There was likely a problem parsing the Dspace API response.");
        }
        return false;
    }

    /**
     * Utility method for DSpace API requests
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
            $response = file_get_contents($url);
            restore_error_handler();
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
