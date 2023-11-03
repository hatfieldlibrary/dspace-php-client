<?php

require __DIR__ . "/service/DSpaceDataServiceImpl.php";
require_once __DIR__ . "/Utils.php";
class Controller
{
    private DSpaceDataServiceImpl $service;
    private Utils $utils;

    private const ENDPOINTS = array (
        array(
            "endpoint" => "endpoints",
            "returns" => "objects array",
            "content" => "The list of endpoints",
        ),
        array(
            "endpoint" => "sections/uuid",
            "returns" => "object",
            "content" => "The section with the provided uuid",
        ),
        array(
            "endpoint" => "sections/uuid/subsections",
            "returns" => array (
                "pagination" => array (
                    "next" => "associative array with page and pageSize",
                    "prev" => "associative array with page and pageSize"
                ),
                "objects" => "object array"
            ),
            "content" => "Subsections in the section with the provided uuid and pagination",
            "optional parameters" =>
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
                )
        ),
        array(
            "endpoint" => "sections/uuid/collections",
            "returns" => array (
                "pagination" => array (
                    "next" => "associative array with page and pageSize",
                    "prev" => "associative array with page and pageSize"
                ),
                "objects" => "object array"
            ),
            "content" => "Collections in the section with the provided uuid and pagination",
            "optional parameters" =>
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
                )
        ),
        array(
            "endpoint" => "collections/uuid",
            "returns" => "object",
            "content" => "The collection with the provided uuid",
        ),
        array(
            "endpoint" => "collections/uuid/items",
            "returns" => array (
                "pagination" => array (
                    "next" => "associative array with page and pageSize",
                    "prev" => "associative array with page and pageSize"
                ),
                "objects" => "object array"
            ),
            "content" => "The items in the collection with the provided uuid and pagination",
            "optional parameters" =>
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
                )
        ),
        array(
            "endpoint" => "items/uuid",
            "returns" => "object",
            "content" => "The item with the provided uuid",
            "optional parameters" =>
                array(
                    array("name"=>"format",
                        "value" => "if true will attempt to format the description with html paragraph tags",
                        "default" => "false",
                        "optional"=> "true"
                    )
                )
        ),
        array(
            "endpoint" => "items/uuid/files",
            "returns" => "objects array",
            "content" => "The files for the item with the provided uuid",
            "optional parameters" =>
                array(
                    array("name"=>"bundle",
                        "value" => "the DSpace bundle containing files",
                        "default" => "ORIGINAL",
                        "optional"=> "true"
                    )
                )
        ),
        array(
            "endpoint" => "items/uuid/thumbnail",
            "returns" => "string",
            "content" => "The link to the logo image for the item with the provided uuid",
        ),
        array(
            "endpoint" => "section/uuid/logo",
            "returns" => "string",
            "content" => "The link to the logo image for the item with the provided uuid",
        ),
        array(
            "endpoint" => "collection/uuid/logo",
            "returns" => "string",
            "content" => "The link to the thumbnail image for the item with the provided uuid",
        ),
        array(
            "endpoint" => "communities/uuid/collectionscount",
            "returns" => "string",
            "content" => "The number of items in the collection with the provided uuid",
        )
    );

    public function __construct()
    {
        $this->service = new DSpaceDataServiceImpl();
        $this->utils = new Utils();
    }

    public function endpoints(): void
    {
        $this->utils->outputJSON(self::ENDPOINTS);
    }

    public function sections($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getCommunity($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function sectionssubsections($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $queryStringParams = $this->utils->getQueryStringParams();
        if ($requestMethod == 'GET') {
            $response = $this->service->getSubCommunities($uuid, $queryStringParams);
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

    public function collectionslogo($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getCollectionLogo($uuid);
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

    public function communitiescollectionscount($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $queryStringParams = $this->utils->getQueryStringParams();
        if ($requestMethod == 'GET') {
            $response = $this->service->getCommunityCollectionCount($uuid, $queryStringParams);
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

}