<?php

require __DIR__ . "/service/DSpaceDataServiceImpl.php";
require_once __DIR__ . "/Utils.php";
class Controller
{
    private DSpaceDataServiceImpl $service;
    private Utils $utils;

    private const ENDPOINTS = array (
        array(
            "endpoint" => "subcommunities/uuid -- communities / subcommunities",
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
        "collections/uuid",
        "collectionItems/uuid  - collections / items  ",
        "items/uuid[?format=true|false&bundle=bundleName]",
        "communityLogo/uuid",
        "collectionLogo/uuid",
        "itemThumbnail/uuid",
        "collectionCount/uuid",
        "communityCollections/uuid communiyies / collections",
        "link/uuid",
        "bitstreamData/uuid"

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
        $queryStringParams = $this->utils->getQueryStringParams();
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

    public function collectionscount($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $queryStringParams = $this->utils->getQueryStringParams();
        if ($requestMethod == 'GET') {
            $response = $this->service->getCollectionCount($uuid, $queryStringParams);
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