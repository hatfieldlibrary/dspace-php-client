<?php

interface DSpaceDataService {

    /**
     * Search for indexed DSpace objects (Items, Collections, Communities (sections))
     * <br>
     * Called by <code>api/search</code>
     * <br>
     * @param array $params required "query" parameter and optional DSpace parameters.
     * @return array
     * <code>
     * ['pagination']      array Contains information on next and previous links (when needed).
     *     [next]            array Defines the options for next link
     *        ['page']         string The next page index
     *        ['size']         string The size of pages used in pagination
     *     [prev]            array Defines the options for previous link
     *         ['page']        string The previous page index
     *         ['size']        string The size of pages used in pagination
     *  ['objects']        array Contains information about the objects found
     *     ['name']             string The name of the object
     *     ['uuid']             string The uuid of the object
     *     ['type']             string The type of object (item, collection, community)
     *     ['thumbnail']        array  The information about the thumbnail image
     *       ['name']             string The name of the thumbnail image
     *       ['href']             string The link to the thumbnail image content
     *     ['metadata']         array The item metadata
     *       ['title']            string The title of the object
     *       ['creator']          string The creator of the object
     *       ['description']      string The description of the object
     *       ['date']             string The date of the object
     * ['count']            string The number of objects found
     *
     * </code>
     */
    public function search(array $params = []): array;

    /**
     * Gets information about top level DSpace sections
     * <br>
     * Called by <code>api/toplevel</code>
     *
     * @param array $params
     * @return array
     * <code>
     * ['pagination']      array Contains information on next and previous links (when needed).
     *    [next]          array Defines the options for next link
     *        ['page']       string The next page index
     *        ['size']       string The size of pages used in pagination
     *    [prev]           array Defines the options for previous link
     *         ['page']      string The previous page index
     *         ['size']      string The size of pages used in pagination
     * ['objects']         array Contains information about subsections
     *        ['section name']   array The key is the name of the section
     *            ['name']            string The name of the section
     *            ['uuid']            string The uuid of the section
     *            ['collectionCount']  string The number of collections in the section
     *            ['subsectionCount']  string The number of subsections in the section
     *  ['count']          string The number of top level sections
     * </code>
     */
    function getTopLevelSections(array $params = []): array;

    /**
     * Gets information about DSpace communities (sections).
     *
     * Called by <code>api/sections/<uuid>/subsections</code>
     *
     * @param $uuid string the parent community id.
     * @param $params array optional DSpace request parameters
     * @return array
     * <code>
     *  ['pagination']   array Contains information on next and previous links (when needed).
     *     [next]          array Defines the options for next link
     *        ['page']       string The next page index
     *        ['size']       string The size of pages used in pagination
     *      [prev]           array Defines the options for previous link
     *         ['page']      string The previous page index
     *         ['size']      string The size of pages used in pagination
     *  ['objects']       array Contains information about subsections
     *       ['section name']   array The key is the name of the section
     *          ['name']             string The name of the section
     *          ['uuid']             string The uuid of the section
     *          ['collectionCount']  string The number of collections in the section
     *          ['subsectionCount']  string The number of subsections in the section
     *          ['logo']             string The href for the section logo.
     *   ['count']         string The number of subsections
     *  </code>
     */
    function getSubSections(string $uuid, array $params = []): array;

    /**
     * Gets information about a specific DSpace collection.
     * @param $uuid string DSpace collection uuid
     * @return array  an array of collection information
     * <code>
     * ['name']             string The name of the collection
     * ['uuid']             string The uuid of the collection
     * ['itemCount']        string (optional) The number of items in the collection (see Configuration)
     * ['shortDescription'] string The short description of the collection
     * ['description']      string (optional) The description of the collection
     * ['logo']             string The href of the collection logo (if available)
     * </code>
     */
    function getCollection(string $uuid): array;

    /**
     * Gets information about the items in a DSpace collection.
     * <br>
     * Called by <code>api/collections/<uuid>/items</code>
     * <br>
     * @param $uuid string the DSpace collection uuid
     * @param $params array optional DSpace query parameters (e.g. page, size)
     * @return array containing DSpace item information
     * <code>
     * ['pagination']  array Contains information on next and previous links (when needed).
     *   [next]            array Defines the options for next link
     *      ['page']         string The next page index
     *      ['size']         string The size of pages used in pagination
     *    [prev]           array Defines the options for previous link
     *       ['page']        string The previous page index
     *       ['size']        string The size of pages used in pagination
     *  ['objects']    array Contains list of items
     *     ['name']           string The name of the item
     *     ['uuid']           string The uuid of the item
     *     ['metadata']       string The metadata for the item
     *        ['title']          string The title of the item
     *        ['description']    string The description of the item
     *        ['creator']        string The creator of the item
     *        ['date']           string The date of the item
     *    ['owningCollection'] array Contains information about the collection the owns the item
     *        ['href']        string  The link to the owning collection
     *    ['thumbnail']       string  The link to the item thumbnail image
     * ['count']         string  The number of items in the section
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
     *  ['pagination']  array Contains information on next and previous links (when needed).
     *    [next]            array Defines the options for next link
     *       ['page']         string The next page index
     *       ['size']         string The size of pages used in pagination
     *     [prev]           array Defines the options for previous link
     *        ['page']        string The previous page index
     *        ['size']        string The size of pages used in pagination
     *   ['objects']    array Contains list of collections
     *       ['name']             string The name of the collection
     *       ['uuid']             string The uuid of the collection
     *       ['itemCount']        string (optional) The number of items in the collection (see Configuration)
     *       ['shortDescription'] string The short description of the collection
     *       ['description']      string (optional) The description of the collection
     *       ['logo']             string The href of the collection logo (if available)
     *   ['count']       string Number of collections in section
     * </code>
     */
    function getCollectionsForSection(string $uuid, array $params = [], bool $reverseOrder = true): array;

    /**
     * Gets information about a DSpace item
     * <br>
     * Called by <code>api/items/<uuid></code>
     * <br>
     * @param string $uuid the DSpace item uuid
     * @param bool $formatDescription (optional) parameter to html format the DSpace item description
     * @return array
     * <code>
     * ['name']           string The name of the item
     * ['uuid']           string The uuid of the item
     * ['metadata']       string The metadata for the item
     *    ['title']           string The title of the item
     *    ['description']     string The description of the item
     *    ['creator']         string The creator of the item
     *    ['date']            string The date of the item
     * ['owningCollection'] array Contains information about the collection the owns the item
     *    ['href']            string  The link to the owning collection
     *    ['name']            string  The name of the owning collection
     *    ['uuid']            string  The uuid of the owning collection
     * ['thumbnail']       string  The link to the item thumbnail image
     *
     * </code>
     */
    function getItem(string $uuid, bool $formatDescription = false): array;

    /**
     * Gets the bitstreams (e.g. image files) for a DSpace item.
     * <br>
     * Called by <code>api/items/<uuid>/files</code>
     * <br>
     * @param $uuid string the uuid of the DSpace item
     * @param $bundleName string the (optional) bundle name. Default is the ORIGINAL bundle.
     * @return array
     * <code>
     * ['objects']   array Contains the list of files
     *   ['name']       string The name of the file
     *   ['href']       string The link to the file
     *   ['uuid']       string The uuid of the file
     *   ['mimetype']   string The mimetype of the file
     *   ['thumbnail']  string The link to the thumbnail image for the file
     *   ['metadata']    array The metadata for the file
     *     ['title']       string The title of the file (i.e. the filename)
     *     ['label']      string The label of the file (from the iiif.label metadata)
     *     ['medium']      string The medium of the object (i.e. oil on canvas)
     *     ['dimensions']  string The physical dimensions of the object
     *     ['subject']     string The subjects assigned to the object
     *     ['description'] string The description of the object
     *     ['type']        string The type of work (e.g. illustration)
     *  ['count']     string Number of files in item
     *
     * </code>
     */
    function getItemFiles(string $uuid, string $bundleName = "ORIGINAL"): array;

    /**
     * Gets metadata for a DSpace bitstream.
     * <br>
     * Called by <code>api/files/<uuid></code>
     * @param $uuid string the DSpace bitstream uuid
     * @return array
     * <code>
     * ['name']       string The name of the file
     * ['href']       string The link to the file
     * ['uuid']       string The uuid of the file
     * ['mimetype']   string The mimetype of the file
     * ['thumbnail']  string The link to the thumbnail image for the file
     * ['metadata']    array The metadata for the file
     *    ['title']       string The title of the file (i.e. the filename)
     *    ['label']      string The label of the file (from the iiif.label metadata)
     *    ['medium']      string The medium of the object (i.e. oil on canvas)
     *    ['dimensions']  string The physical dimensions of the object
     *    ['subject']     string The subjects assigned to the object
     *    ['description'] string The description of the object
     *    ['type']        string The type of work (e.g. illustration)
     * </code>
     */
    function getBitstreamData(string $uuid): array;

    /**
     * Gets information about the community (section) with the provided uuid.
     *
     * Called by <code>api/sections/<uuid></code>
     *
     * @param string $uuid
     * @return array
     * <code>
     * ['name']            string The name of the section
     * ['uuid']            string The uuid of the section
     * ['collectionCount']  string The number of collections in the section
     * ['subsectionCount']  string The number of subsections in the section
     * ['logo']    string The link to the section logo
     *
     * </code>
     */
    function getSection(string $uuid): array;

    /**
     * Gets the number of items in a collection.
     * <br>
     * Called by <code>api/collections/<uuid>/itemcount</code>
     * @param string $uuid the uuid of the collection
     * @return string The item count.
     */
    function getItemCountForCollection(string $uuid): string;

}