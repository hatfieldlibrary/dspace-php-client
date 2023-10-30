<?php

require __DIR__ . "/service/DSpaceDataServiceImpl.php";
require_once __DIR__ . "/Utils.php";
class Controller
{
    private DSpaceDataServiceImpl $service;
    private Utils $utils;

    private const ENDPOINTS = array (
        "subcommunities/uuid[?page=&pageSize=]",
        "collections/uuid",
        "collectionItems/uuid",
        "items/uuid[?format=true|false&bundle=bundleName]",
        "communityLogo/uuid",
        "collectionLogo/uuid",
        "itemThumbnail/uuid",
        "collectionCount/uuid",
        "communityCollections/uuid",
        "link/uuid",
        "bitstreamData/uuid"

    );

    public function __construct()
    {
        $this->service = new DSpaceDataServiceImpl();
        $this->utils = new Utils();
    }

    public function getEndpoints(): void
    {
        $this->utils->outputJSON(self::ENDPOINTS);
    }

    public function subcommunities($uuid): void
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
        $queryStringParams = $this->utils->getQueryStringParams();
        if ($requestMethod == 'GET') {
            $response = $this->service->getCollection($uuid, $queryStringParams);
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
            $bundle = "ORIGINAL";
            if ($queryStringParams) {
                if (array_key_exists("format",$queryStringParams)) {
                    $format = $queryStringParams["format"];
                }
                if (array_key_exists("bundle",$queryStringParams)) {
                    $bundle = $queryStringParams["bundle"];
                }
            }
            $response = $this->service->getItem($uuid, $format, $bundle);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function collectionItems($uuid): void
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

    public function communityLogo($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getCommunityLogo($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function collectionLogo($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getCollectionLogo($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function owningCollection($href): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getOwningCollection($href);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function itemThumbnail($uuid): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getItemThumbnail($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function collectionCount($uuid): void
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

    public function communityCollections($uuid): void
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

    public function link($uuid) {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getFileLink($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

    public function bitstreamData($uuid) {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        if ($requestMethod == 'GET') {
            $response = $this->service->getBitstreamData($uuid);
            $this->utils->outputJSON($response);
        } else {
            $this->utils->outputJSON('', array('HTTP/1.1 405 Method Not Allowed'));
        }
    }

}