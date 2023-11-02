<?php

interface DSpaceDataService {

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
    function getSubCommunities(string $uuid, array $params = []): array;

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
    function getOwningCollection(string $uuid): array;

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
     * Gets href for the DSpace community logo
     * @param $uuid string the DSpace community uuid
     * @return string
     */
    function getCommunityLogo(string $uuid): string;

    /**
     * Gets href for the DSpace collection logo
     * @param $uuid string the DSpace collection uuid
     * @return string
     */
    function getCollectionLogo(string $uuid): string;

    /**
     * Gets href for the DSpace item thumbnail image
     * @param $uuid string the DSpace item uuid
     * @return string
     */
    function getItemThumbnail(string $uuid): string;

    /**
     * @param $communityUuid string the DSpace community uuid
     * @param $params array optional DSpace request parameters
     * @return string
     */
    function getCommunityCollectionCount(string $communityUuid, array $params = []): string;

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
     * Creates the full URL for bitstream content in DSpace based on the uuid
     * @param $uuid string the DSpace uuid for the file
     * @return string file url
     */
    function getFileLink(string $uuid): string;

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

    function getCommunity(string $uuid): array;

    function getItemCount(string $uuid): string;

}