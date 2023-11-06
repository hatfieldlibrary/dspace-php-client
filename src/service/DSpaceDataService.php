<?php

interface DSpaceDataService {

    /**
     * Search for indexed DSpace objects (Items, Collections, Communities (sections))
     * @param array $params required "query" parameter and optional DSpace parameters.
     * @return array
     * <code>
     *    array(
     *    "pagination" => array (
     *     "next" => array (
     *         "page" => string,
     *         "pageSize" => string
     *      ),
     *      "prev" => array (
     *         "page" => string,
     *         "pageSize" => string
     *       )
     *    ),
     *    "objects" => array (
     *        "name" => string,
     *        "uuid" => string,
     *        "type" => string,
     *        "metadata" => array (
     *          "title" => string,
     *          "creator" => string,
     *          "description" => string,
     *          "date" => string
     *        )
     *        "thumbnail" => array (
     *          "name" => string,
     *          "href" => string
     *        )
     *     )
     *     "count" => string
     *   )
     * </code>
     */
    public function search(array $params = []): array;

    function getTopLevelSections(array $params = []): array;

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
    function getSubSections(string $uuid, array $params = []): array;

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
    function getCollection(string $uuid): array;

    /**
     * Gets information about the items in a DSpace collection
     * @param $uuid string the DSpace collection uuid
     * @param $params array optional DSpace query parameters (e.g. pageSize)
     * @return array array of associative arrays containing DSpace item information
     * <code>
     *  array(
     *     array("name" => string,
     *           "uuid" => string,
     *           "author" => string,
     *           "date" => string,
     *           "description" => string,
     *           "owningCollection" => string,
     *           "logo" => string
     *   )
     * )
     * </code>
     */
    function getCollectionItems(string $uuid, array $params = []): array;

    /**
     * Gets list of collections for the community (section) with the provided uuid.
     * @param $uuid string uuid of the DSpace community
     * @param $params array optional query parameters
     * @param $reverseOrder boolean optional value that reverses order of the collection array (defaults to true)
     * @return array
     * <code>
     *   array(
     *     "pagination" => array (
     *       "next" => array (
     *          "page" => string,
     *          "size" => string
     *        )
     *        "prev" => array (
     *           "page" => string,
     *           "size" => string
     *         ),
     *     ),
     *     "objects" => array (
     *          array (
     *            "name" => string,
     *            "uuid" => string,
     *            "description" => string,
     *            "shortDescription" => string,
     *            "logo" => string,
     *            "itemCount => string
     *          )
     *     ),
     *     "count" => string
     *   )
     * </code>
     */
    function getCollectionsForCommunity(string $uuid, array $params = [], bool $reverseOrder = true): array;

    /**
     * Gets information about a DSpace item
     * @param string $uuid the DSpace item uuid
     * @param bool $formatDescription (optional) parameter to html format the DSpace item description
     * @param $bundleName string the (optional) bundle name
     * @return
     * <code>
     *  array (
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
     * )
     * </code>
     */
    function getItem(string $uuid, bool $formatDescription = false): array;

    /**
     * Gets the bitstreams (e.g. image files) for a DSpace item.
     * @param $uuid string the uuid of the DSpace item
     * @param $bundleName string the (optional) bundle name. Default is the ORIGINAL bundle.
     * @return array
     * <code>(
     *   "name" => string,
     *   "href" => string,
     *   "thumbnail" => string,
     *   "uuid" => string,
     *   "mimetype" => string
     * )
     * </code>
     */
    function getItemFiles(string $uuid, string $bundleName = "ORIGINAL"): array;

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
    function getBitstreamData(string $uuid): array;

    /**
     * Gets information about the community (section) with the provided uuid.
     * @param string $uuid
     * @return array
     */
    function getSection(string $uuid): array;

    function getItemCountForCollection(string $uuid): string;

}