<?php

require __DIR__ . "/service/DSpaceDataServiceImpl.php";
require_once __DIR__ . "/Utils.php";
class Controller
{
    private DSpaceDataServiceImpl $service;
    private Utils $utils;
    private array $config;


    public function __construct()
    {
        $this->service = new DSpaceDataServiceImpl();
        $this->utils = new Utils();
        $settings = new Configuration();
        $this->config = $settings->getConfig();
    }

    public function sections($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getSection($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function toplevel(): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getTopLevelSections();
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function sectionssubsections(mixed $uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $queryStringParams = $this->utils->getQueryStringParams();
        if ($requestMethod == 'GET') {
            $response = $this->service->getSubSections($uuid, $queryStringParams);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function collections($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getCollection($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function items($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $queryStringParams = $this->utils->getQueryStringParams();
        if ($requestMethod == 'GET') {
            $format = "false";
            if ($queryStringParams) {
                if (array_key_exists("format",$queryStringParams)) {
                    $format = $queryStringParams["format"];
                }
            }
            $response = $this->service->getItem($uuid, $format);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function itemsfiles($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $queryStringParams = $this->utils->getQueryStringParams();
        if ($requestMethod == 'GET') {
            $format = "false";
            $bundle = "ORIGINAL";
            if ($queryStringParams) {
                if (array_key_exists("bundle",$queryStringParams)) {
                    $bundle = $queryStringParams["bundle"];
                }
            }
            $response = $this->service->getItemFiles($uuid, $bundle);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function collectionsitems($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $queryStringParams = $this->utils->getQueryStringParams();
        if ($requestMethod == 'GET') {
            $response = $this->service->getCollectionItems($uuid, $queryStringParams);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function sectionslogo($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getCommunityLogo($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function itemsowningcollection($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getOwningCollection($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function itemsthumbnail($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getItemThumbnail($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function sectionscollections($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $queryStringParams = $this->utils->getQueryStringParams();
        if ($requestMethod == 'GET') {
            $response = $this->service->getCollectionsForCommunity($uuid, $queryStringParams);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function fileslink($uuid) {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getFileLink($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function files($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getBitstreamData($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function search() :void {
        $queryStringParams = $this->utils->getQueryStringParams();
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->search($queryStringParams);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function endpoints(): void
    {
        $endpoints = array (
            array(
                "endpoint" => $this->config["base"] . "/endpoints",
                "content" => "The list of endpoints",
                "returns" => "array of objects"
            ),
            array(
                "endpoint" => $this->config["base"] . "/api/toplevel",
                "content" => "list of toplevel sections with subsection counts (includes pagination)",
                "returns" => "array of objects"

            ),
            array(
                "endpoint" => $this->config["base"] . "/sections/<uuid>",
                "content" => "information about the section with the provided uuid",
                "returns" => "object"
            ),
            array(
                "endpoint" => $this->config["base"] . "/sections/<uuid>/subsections",
                "content" => "information about subsections in the section with the provided uuid (includes pagination)",
                "query parameters" =>
                    array(
                        array("name"=>"page",
                            "value" => "the current page in pagination",
                            "default" => "0",
                            "optional"=> "true"
                        ),
                        array("name"=>"pageSize",
                            "value" => "the number of items per page",
                            "default" => "40",
                            "optional"=> "true"
                        )
                    ),
                "returns" => array (
                    "pagination" => array (
                        "next" => "associative array with page and pageSize",
                        "prev" => "associative array with page and pageSize"
                    ),
                    "objects" => "array of objects"
                ),
            ),
            array(
                "endpoint" => $this->config["base"] . "/sections/<uuid>/collections",
                "content" => "Collections in the section with the provided uuid (includes pagination)",
                "query parameters" =>
                    array(
                        array("name"=>"page",
                            "value" => "the current page in pagination",
                            "default" => "0",
                            "optional"=> "true"
                        ),
                        array("name"=>"pageSize",
                            "value" => "the number of items per page",
                            "default" => "40",
                            "optional"=> "true"
                        )
                    ),
                "returns" => array (
                    "pagination" => array (
                        "next" => "associative array with page and pageSize",
                        "prev" => "associative array with page and pageSize"
                    ),
                    "objects" => "array of objects"
                )
            ),
            array(
                "endpoint" => $this->config["base"] . "/collections/<uuid>",
                "content" => "The collection with the provided uuid",
                "returns" => "object"
            ),
            array(
                "endpoint" => $this->config["base"] . "/collections/<uuid>/items",
                "content" => "The items in the collection with the provided uuid (includes pagination)",
                "query parameters" =>
                    array(
                        array("name"=>"page",
                            "value" => "the current page in pagination",
                            "default" => "0",
                            "optional"=> "true"
                        ),
                        array("name"=>"pageSize",
                            "value" => "the number of items per page",
                            "default" => "40",
                            "optional"=> "true"
                        )
                    ),
                "returns" => array (
                    "pagination" => array (
                        "next" => "associative array with page and pageSize",
                        "prev" => "associative array with page and pageSize"
                    ),
                    "objects" => "array of objects"
                )
            ),
            array(
                "endpoint" => $this->config["base"] . "/items/<uuid>",
                "content" => "The item with the provided uuid",
                "query parameters" =>
                    array(
                        array("name"=>"format",
                            "value" => "if true will attempt to format the description with html paragraph tags",
                            "default" => "false",
                            "optional"=> "true"
                        )
                    ),
                "returns" => "object",
            ),
            array(
                "endpoint" => $this->config["base"] . "/items/<uuid>/files",
                "content" => "The files for the item with the provided uuid",
                "query parameters" =>
                    array(
                        array("name"=>"bundle",
                            "value" => "the DSpace bundle containing files",
                            "default" => "ORIGINAL",
                            "optional"=> "true"
                        )
                    ),
                "returns" => "array of objects",
            ),
            array (
                "endpoint" => $this->config["base"] . "/files/<uuid>",
                "content" => "information about the file with the provided uuid",
                "returns" => "object"
            ),
            array(
                "endpoint" => $this->config["base"] . "/search",
                "content" => "Search for items, collections and communities. (includes pagination)",
                "query parameters" =>
                    array(
                        array(
                            "name" => "query",
                            "value" => "the query terms",
                            "optional" => "false"
                        ),
                        array(
                            "name" => "scope",
                            "value" => "the DSpace uuid for the search scope",
                            "default" => "the default scope is defined in configuration",
                            "optional" => "true"
                        ),
                        array("name"=>"page",
                            "value" => "the current page in pagination",
                            "default" => "0",
                            "optional"=> "true"
                        ),
                        array("name"=>"pageSize",
                            "value" => "the number of items per page",
                            "default" => "40",
                            "optional"=> "true"
                        )
                    ),
            "returns" => array (
                "pagination" => array (
                    "next" => "associative array with page and pageSize",
                    "prev" => "associative array with page and pageSize"
                ),
                "objects" => "array of objects",
                "count" => "result count"
            )
            )

        );
        $this->utils->outputJSON($endpoints);
    }


}