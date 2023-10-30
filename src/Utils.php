<?php

class Utils {

    public function getQueryStringParams(): array
    {
        if(array_key_exists("QUERY_STRING", $_SERVER)) {
            parse_str($_SERVER['QUERY_STRING'], $query);
            return $query;
        }
        return array();
    }

    public function getUriSegments()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return explode('/', $uri);
    }

    public function outputJSON($output, $httpHeaders = array()): void
    {
        $out = json_encode($output);
        if (!$out) {
            $httpHeaders = array('HTTP/1.1 500 Server Error');
        }
        $httpHeaders[] = "Content-Type: application/json";

        if (is_array($httpHeaders) && count($httpHeaders)) {
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
            }
        }
        echo json_encode($output);
        exit;
    }

}