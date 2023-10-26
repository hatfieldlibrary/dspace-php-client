<?php

require "../service/Configuration.php";

/**
 * PHP service class for retrieving Community, Collection, Item, and Bitstream
 * information from the DSpace REST API.
 */
class DSpaceDataService {

    private array $config;

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
        if (array_key_exists("page", $params)) {
            $query["page"] = $params["page"];
        }
        if (array_key_exists("pageSize", $params)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $url = $this->config["base"] . "/core/communities/" . $uuid . "/subcommunities";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $Subcommittees = $this->getRestApiResponse($url);
        if ($this->checkKey("subcommunities", $Subcommittees["_embedded"])) {
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
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $collection = $this->getRestApiResponse($url);
        $logoHref = $this->getCollectionLogo($collection["uuid"]);
        $itemCount = $this->getItemCount($collection["uuid"]);
        $description = "";
        $shortDescription = "";
        if ($this->checkKey("metadata", $collection)) {
            if (array_key_exists("dc.description.abstract", $collection["metadata"])) {
                $shortDescription = $collection["metadata"]["dc.description.abstract"][0]["value"];
            } else {
                error_log("WARNING: A short description was not found for " . $collection["name"]);
            }
            if (array_key_exists("dc.description", $collection["metadata"])) {
                $description = $collection["metadata"]["dc.description"][0]["value"];
            } else {
                error_log("WARNING: A full description was not found for " . $collection["name"]);
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
        if (array_key_exists("page", $params)) {
            $query["page"] = $params["page"];
        }
        if (array_key_exists("pageSize", $params)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $url = $this->config["base"] . "/discover/search/objects";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $restResponse = $this->getRestApiResponse($url);
        $itemsArr = array();
        if ($this->checkKey("searchResult", $restResponse["_embedded"])) {
            if ($this->checkKey("_embedded", $restResponse["_embedded"]["searchResult"])) {
                foreach ($restResponse["_embedded"]["searchResult"]["_embedded"] as &$restElement) {
                    foreach ($restElement as &$item) {
                        $itemAssocArray = array();
                        if ($this->checkKey("indexableObject", $item["_embedded"])) {
                            $object = ($item["_embedded"]["indexableObject"]);
                            $itemAssocArray["name"] = $object["name"];
                            $itemAssocArray["uuid"] = $object["uuid"];
                            $metadata = $object["metadata"];
                            if ($this->checkKey('dc.contributor.author', $metadata)) {
                                $itemAssocArray["author"] = $metadata["dc.contributor.author"][0]["value"];
                            }
                            if ($this->checkKey('dc.date.issued', $metadata)) {
                                $itemAssocArray["date"] = $metadata["dc.date.issued"][0]["value"];
                            }
                            if ($this->checkKey('dc.description.abstract', $metadata)) {
                                $itemAssocArray["description"] = $metadata["dc.description.abstract"][0]["value"];
                            }
                            $itemAssocArray["owningCollection"] = $object["_links"]["owningCollection"];
                            if ($this->checkKey('thumbnail', $object["_links"])) {
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
    public function getCollectionCount($uuid, $params = []): string
    {
        $query = array (
            "page" => 0,
            "pageSize" => $this->config["defaultPageSize"]
        );
        if (array_key_exists("page", $params)) {
            $query["page"] = $params["page"];
        }
        if (array_key_exists("pageSize", $params)) {
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
        if (array_key_exists("page", $params)) {
            $query["page"] = $params["page"];
        }
        if (array_key_exists("pageSize", $params)) {
            $query["pageSize"] = $params["pageSize"];
        }
        $query["scope"] = $uuid;
        $url = $this->config["base"] . "/discover/search/objects?" . $uuid;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $item = $this->getRestApiResponse($url);
        if ($this->checkKey("totalElements", $item["_embedded"]["searchResult"]["page"])) {
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
        if (array_key_exists("page", $params)) {
            $query["page"] = $params["page"];
        }
        if (array_key_exists("pageSize", $params)) {
            $query["pageSize"] = $params["pageSize"];
        }
        if (array_key_exists("reverse", $params)) {
            $reverse = $params["pageSize"];
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
        if (array_key_exists("dc.description.abstract", $metadata)) {
            $author = $metadata["dc.contributor.author"][0]["value"];
        }
        if (array_key_exists("dc.description.abstract", $metadata)) {
            $description = $this->formatDescription($metadata["dc.description.abstract"][0]["value"]);
        }
        return array (
            "name" => $item["name"],
            "uuid" => $item["uuid"],
            "description" => $description,
            "creator" => $author,
            "owningCollection" => $item["_links"]["owningCollection"]["href"],
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

        if (array_key_exists("dc.title", $image["metadata"])) {
            $title = $image["metadata"]["dc.title"][0]["value"];
        }
        if (array_key_exists("iiif.label", $image["metadata"])) {
            $label = $image["metadata"]["iiif.label"][0]["value"];
        }
        if (array_key_exists("dc.description", $image["metadata"])) {
            $description = $image["metadata"]["dc.description"][0]["value"];
        }
        if (array_key_exists("dc.format.medium", $image["metadata"])) {
            $medium = $image["metadata"]["dc.format.medium"][0]["value"];
        }
        if (array_key_exists("dc.format.extent", $image["metadata"])) {
            $dimensions = $image["metadata"]["dc.format.extent"][0]["value"];
        }
        if (array_key_exists("dc.subject.other", $image["metadata"])) {
            $subject = $image["metadata"]["dc.subject.other"][0]["value"];
        }
        if (array_key_exists("dc.type", $image["metadata"])) {
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
        if ($this->checkKey("bitstreams", $bundle["_embedded"])) {
            if ($this->checkKey("_embedded", $bundle["_embedded"]["bitstreams"])) {
                if ($this->checkKey("bitstreams", $bundle["_embedded"]["bitstreams"]["_embedded"])) {
                    $bitstreams = $bundle["_embedded"]["bitstreams"]["_embedded"]["bitstreams"];
                }
            }
        }
        $imageArr = array();
        $thumbnail = "";
        $mainImage = "";
        foreach ($bitstreams as $image) {
            if (array_key_exists("_links", $image)) {
                if (array_key_exists("self", $image["_links"])) {
                    $thumbnail = $this->getThumbnail($image["_links"]["self"]["href"]);
                    $mainImage = $image["_links"]["content"]["href"];
                }
                $mimeType = $image["_embedded"]["format"]["mimetype"];
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
            if (array_key_exists("_links", $linkData)) {
                $imageLinks = $linkData["_links"];
                if ($imageLinks) {
                    if (array_key_exists("content", $imageLinks)) {
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
     * @return bool
     */
    private function checkKey(string $key, mixed $array): bool
    {
        if($array) {
            $found = array_key_exists($key, $array);
            if (!$found) {
                error_log("WARNING: Could not find key: " . $key);
            }
            return $found;
        } else {
            error_log("WARNING: A null array was provided to the checkKey function. This should not
            happen. There was likely a problem parsing the Dspace API response.");
        }
        return false;
    }

    /**
     * Utility method for throwing exception.
     * @param $message string the message to log
     * @throws Exception
     */
    private function error(string $message) {
        throw new Exception($message);

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
