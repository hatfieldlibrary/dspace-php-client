<?php

require "../service/Configuration.php";

/**
 * PHP service class for retrieving Community, Collection, Item, and Bitstream
 * information from the DSpace REST API.
 */
class DSpaceDataService {

    private array $config;

    private const ITEM = "ITEM";
    private const COMMUNITY = "COMMUNITY";
    private const COLLECTION = "COLLECTION";

    private const DISCOVERY = "DISCOVERY";
    private const BUNDLE = "BUNDLE";
    private const BITSTREAM = "BITSTREAM";

    private const PARAMS = "REQUEST PARAMETER";

    public function __construct() {
        $settings = new Configuration();
        $this->config = $settings->getConfig();
    }

    /**
     * Gets information about DSpace sub-communities.
     * @param $uuid string the parent community id.
     * @param $params array optional DSpace request parameters
     * @return array
     * <code>
     *     array (
     *       "name" => string
     *       "uuid" => string,
     *       "logo" => string
     *       "count" => string
     *     )
     * </code>
     */
    public function getSubCommunities(string $uuid, array $params = []): array
    {
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
        $Subcommittees = $this->getRestApiResponse($url);
        if ($this->checkKey("subcommunities", $Subcommittees["_embedded"], self::COMMUNITY)) {
            foreach ($Subcommittees["_embedded"]["subcommunities"] as $subComm) {
                $logoHref = $this->getCommunityLogo($subComm["uuid"]);
                $count = $this->getCollectionCount($subComm["uuid"]);
                $current = array(
                    "name" => $subComm["name"],
                    "uuid" => $subComm["uuid"],
                    "logo" => $logoHref,
                    "count" => $count
                );
                $subcommitteeMap[$subComm["name"]] = $current;
            }
        }
        return $subcommitteeMap;
    }

    /**
     * Gets the name and href of the owning collection for an item.
     * @param $href string the href of the owning collection endpoint
     * @return array
     * <code>
     *     array(
     *     "name" => string,
     *     "href" => string
     * )
     * </code>
     */
    public function getOwningCollection(string $href): array
    {
        $collection = $this->getRestApiResponse($href);
        return array(
            "name" => $collection["name"],
            "href" => $href
        );
    }

    /**
     * Gets information about a specific DSpace collection.
     * @param $uuid string DSpace collection uuid
     * @return array  an array of collection information
     * <code>
     *  array(
     *     "name" => string,
     *     "uuid" => string,
     *     "description" => string,
     *     "shortDescription" => string,
     *     "logo" => string,
     *     "count" => int
     * )
     * </code>
     */
    public function getCollection(string $uuid): array
    {
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
        return array(
            "name" => $collection["name"],
            "uuid" => $collection["uuid"],
            "description" => $description,
            "shortDescription" => $shortDescription,
            "logo" => $logoHref,
            "count" => $itemCount
        );
    }

    /**
     * Gets information about the items in a DSpace collection
     * @param $uuid string the DSpace collection uuid
     * @param $params array optional DSpace query parameters (e.g. pageSize)
     * @return array array of associative arrays containing DSpace item information
     * <code>
     *     array("name" => string,
     *           "uuid" => string,
     *           "author" => string,
     *           "date" => string,
     *           "description" => string,
     *           "owningCollection" => string,
     *           "logo" => string
     * )
     * </code>
     */
    public function getCollectionItems(string $uuid, array $params = []): array
    {
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
        echo $url;
        $restResponse = $this->getRestApiResponse($url);
        $itemsArr = array();
        if ($this->checkKey("searchResult", $restResponse["_embedded"], self::DISCOVERY)) {
            if ($this->checkKey("_embedded", $restResponse["_embedded"]["searchResult"], self::DISCOVERY)) {
                foreach ($restResponse["_embedded"]["searchResult"]["_embedded"] as &$restElement) {
                    foreach ($restElement as &$item) {
                        $itemAssocArray = array();
                        if ($this->checkKey("indexableObject", $item["_embedded"], self::ITEM)) {
                            $object = ($item["_embedded"]["indexableObject"]);
                            $itemAssocArray["name"] = $object["name"];
                            $itemAssocArray["uuid"] = $object["uuid"];
                            $metadata = $object["metadata"];
                            if ($this->checkKey('dc.contributor.author', $metadata, self::ITEM)) {
                                $itemAssocArray["author"] = $metadata["dc.contributor.author"][0]["value"];
                            }
                            if ($this->checkKey('dc.date.issued', $metadata, self::ITEM)) {
                                $itemAssocArray["date"] = $metadata["dc.date.issued"][0]["value"];
                            }
                            if ($this->checkKey('dc.description.abstract', $metadata, self::ITEM)) {
                                $itemAssocArray["description"] = $metadata["dc.description.abstract"][0]["value"];
                            }
                            if ($this->checkKey('owningCollection', $object["_links"]["owningCollection"],
                                self::ITEM)) {
                                $itemAssocArray["owningCollection"] = $object["_links"]["owningCollection"];
                            }
                            if ($this->checkKey('thumbnail', $object["_links"], self::ITEM)) {
                                $logo = $this->getItemThumbnail($object["uuid"]);
                                $itemAssocArray["logo"] = $logo;
                            }
                            $itemsArr[] = $itemAssocArray;
                        }
                    }
                }
            }
        }
        return $itemsArr;

    }

    /**
     * Gets href for the DSpace community logo
     * @param $uuid string the DSpace community uuid
     * @return string
     */
    public function getCommunityLogo(string $uuid): string
    {
        $url = $this->config["base"] . "/core/communities/" . $uuid . "/logo";
        $logoMetadata = $this->getRestApiResponse($url);
        return $this->getImageUrl($logoMetadata);
    }

    /**
     * Gets href for the DSpace collection logo
     * @param $uuid string the DSpace collection uuid
     * @return string
     */
    public function getCollectionLogo(string $uuid): string
    {
        $url = $this->config["base"] . "/core/collections/" . $uuid . "/logo";
        $logoMetadata = $this->getRestApiResponse($url);
        return $this->getImageUrl($logoMetadata);
    }

    /**
     * Gets href for the DSpace item thumbnail image
     * @param $uuid string the DSpace item uuid
     * @return string
     */
    public function getItemThumbnail(string $uuid): string
    {
        $url = $this->config["base"] . "/core/items/" . $uuid . "/thumbnail";
        $thumbnailMetadata = $this->getRestApiResponse($url);
        return $this->getImageUrl($thumbnailMetadata);
    }

    /**
     * @param $uuid string the DSpace community uuid
     * @param $params array optional DSpace request parameters
     * @return string
     */
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
        $subComm = $this->getRestApiResponse($url);
        return $subComm["page"]["totalElements"];
    }

    /**
     * Returns the item count for a DSpace collection
     * @param $uuid string uuid of the collection
     * @param $params array optional dspace request parameters
     * @return string
     */
    public function getItemCount(string $uuid, array $params = []): string
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

    /**
     * Extracts and returns collection information from an embedded DSpace API response element.
     * @param $uuid string uuid of the DSpace community
     * @param $params array optional query parameters
     * @param $reverseOrder boolean optional value that reverses order of the collection array (defaults to true)
     * @return array [
     *   "name" => string,
     *   "href" => string,
     *   "thumbnail" => string,
     *   "uuid" => string,
     *   "mimetype" => string
     * ]
     */
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
        return $this->getCollections($communityCollections, $reverseOrder);
    }

    /**
     * Gets information about a DSpace item
     * @param string $uuid the DSpace item uuid
     * @param $bundleName string the (optional) bundle name
     * @return array[
     *   "name" => string,
     *   "uuid" => string,
     *   "description" => string,
     *   "creator" => string,
     *   "owningCollection" => string,
     *   "images" => array[
     *    "name" => string,
     *    "href" => string,
     *    "thumbnail" => string,
     *    "uuid" => string,
     *    "mimetype" => string]
     * ]
     */
    public function getItem(string $uuid, string $bundleName = "ORIGINAL"): array
    {
        $url = $this->config["base"] . "/core/items/" . $uuid;
        $item = $this->getRestApiResponse($url);
        $images = $this->getImages($uuid, $bundleName);
        $metadata = $item["metadata"];
        $description = "";
        $author = "";
        $owningCollection = "";
        if ($this->checkKey("dc.description.abstract", $metadata, self::ITEM)) {
            $author = $metadata["dc.contributor.author"][0]["value"];
        }
        if ($this->checkKey("dc.description.abstract", $metadata, self::ITEM)) {
            $description = $this->formatDescription($metadata["dc.description.abstract"][0]["value"]);
        }
        if ($this->checkKey("owningCollection", $item["_links"], self::ITEM)) {
            $owningCollection = $item["_links"]["owningCollection"]["href"];
        }
        return array (
            "name" => $item["name"],
            "uuid" => $item["uuid"],
            "description" => $description,
            "creator" => $author,
            "owningCollection" => $owningCollection,
            "images" => $images
        );
    }

    /**
     * Gets the bitstreams (e.g. image files) for a DSpace item.
     * @param $uuid string the uuid of the DSpace item
     * @param $bundleName string the (optional) bundle name. Default is the ORIGINAL bundle.
     * @return array [
     *   "name" => string,
     *   "href" => string,
     *   "thumbnail" => string,
     *   "uuid" => string,
     *   "mimetype" => string
     * ]
     */
    public function getImages(string $uuid, string $bundleName = "ORIGINAL"): array
    {
        $query = array (
            "size" => "9999",
            "embed.size" > $this->config["defaultEmbeddedBitstreamParam"],
            "embed" => "bitstreams/format"
        );
        $url = $this->config["base"] . "/core/items/" . $uuid . "/bundles";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $bundles = $this->getRestApiResponse($url);
        $bundle = $this->getBundle($bundles, $bundleName);
        try {
            return $this->getBitstreams($bundle);
        } catch (Exception $err) {
            error_log($err, 0);
            return array();
        }

    }

    /**
     * Creates the full URL for bitstream content in DSpace based on the uuid
     * @param $uuid string the DSpace uuid for the image
     * @return string image url
     */
    public function getImageLink(string $uuid): string
    {
        return $this->config["base"] . "/core/bitstreams/" . $uuid . "/content";
    }

    /**
     * Gets metadata for a DSpace bitstream
     * @param $uuid string the DSpace bitstream uuid
     * @return array
     * <code>
     *     array (
     *       "title" => string,
     *       "label" => string,
     *       "medium" => string,
     *       "dimensions" => string,
     *       "subject" => string,
     *       "description" => string,
     *       "type" => string
     * )
     * </code>
     */
    public function getImageData(string $uuid): array
    {
        $url = $this->config["base"] . "/core/bitstreams/" . $uuid;
        $image = $this->getRestApiResponse($url);
        $title = "";
        $medium = "";
        $dimensions = "";
        $subject = "";
        $type = "";
        $description = "";
        $label = "";

        if ($this->checkKey("dc.title", $image["metadata"], self::BITSTREAM)) {
            $title = $image["metadata"]["dc.title"][0]["value"];
        }
        if ($this->checkKey("iiif.label", $image["metadata"], self::BITSTREAM)) {
            $label = $image["metadata"]["iiif.label"][0]["value"];
        }
        if ($this->checkKey("dc.description", $image["metadata"], self::BITSTREAM)) {
            $description = $image["metadata"]["dc.description"][0]["value"];
        }
        if ($this->checkKey("dc.format.medium", $image["metadata"], self::BITSTREAM)) {
            $medium = $image["metadata"]["dc.format.medium"][0]["value"];
        }
        if ($this->checkKey("dc.format.extent", $image["metadata"], self::BITSTREAM)) {
            $dimensions = $image["metadata"]["dc.format.extent"][0]["value"];
        }
        if ($this->checkKey("dc.subject.other", $image["metadata"], self::BITSTREAM)) {
            $subject = $image["metadata"]["dc.subject.other"][0]["value"];
        }
        if ($this->checkKey("dc.type", $image["metadata"], self::BITSTREAM)) {
            $type = $image["metadata"]["dc.type"][0]["value"];
        }

        return array (
            "title" => $title,
            "label" => $label,
            "medium" => $medium,
            "dimensions" => $dimensions,
            "subject" => $subject,
            "description" => $description,
            "type" => $type
        );
    }

    /**
     * Gets the URL for a thumbnail image
     * @param $href string the link for requesting DSpace thumbnail metadata
     * @return string the content URL for the thumbnail image
     */
    public function getThumbnail(string $href): string
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
        if($array) {
            $found = array_key_exists($key, $array);

            // Exclude missing bitstream metadata from log unless in debug mode
            if (!$found && (strcmp($type, self::BITSTREAM) !== 0 || $this->config["debug"])) {
                error_log("INFO: Could not find the key '" . $key . "' in the DSpace " . $type . " data.");
            }
            return $found;
        } else {
            // It might be a good idea to throw an exception here.
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
        $response = file_get_contents($url);
        return json_decode($response, true);
    }
}
