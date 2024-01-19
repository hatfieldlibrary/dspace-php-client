<?php

require __DIR__ . "/SolrDataService.php";
require_once __DIR__ . "/../Utils.php";

/**
 * This class provides a way to query extra (non-DSpace) solr indices.
 */
class SolrDataServiceImpl implements SolrDataService {

    private const PARAMS = "SOLR_QUERY";

    private array $config;

    private Utils $utils;

    public function __construct()
    {
        $this->utils = new Utils();
        $this->config = $this->utils->getConfig();
    }

    /**
     * Solr query returns the raw solr response for the configured solr core.
     * This service is used with custom solr cores that are not part of the
     * DSpace distribution.
     * @param array $params
     * @return string
     */
    public function search(array $params = []): string
    {
        $query = array (
            "q" => $params["query"],
            "df" => "search_text",
            "q.op" => "AND"
        );
        if ($this->utils->checkKey("page", $params, self::PARAMS)) {
            $query["start"] = $params["page"];
        }
        if ($this->utils->checkKey("size", $params, self::PARAMS)) {
            $query["rows"] = $params["size"];
        }
        $host = $this->config["solr"];
        $core = $this->config["solr_core"];
        $url = $host . "/" . $core . "/select";
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        return $this->utils->getSolrResponse($url);

    }
}
