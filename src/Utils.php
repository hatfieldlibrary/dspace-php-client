<?php

require_once __DIR__ . "/Configuration.php";

class Utils {

    private const REQUEST_FAILED = "DSPACE_REQUEST_ERROR";

    private array $config;

    public function __construct()
    {
        $settings = new Configuration();
        $this->config = $settings->getConfig();
    }

    public function getConfig(): array {
        return $this->config;
    }

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
        echo $out;
        exit;
    }

    public function checkUUID($uuid): void
    {
        if (!$uuid) {
            $this->outputJSON("Required DSpace UUID is missing from the request.",
                array("HTTP/1.1 400 Invalid Request"));
            exit;
        }

    }
    /**
     * Utility method for checking whether a key exists in an array.
     * @param $key string the key to look for
     * @param $array mixed the array that contains the key or null
     * @param $type string optional DSO type for logging
     * @return bool
     */
    public function checkKey(string $key, mixed $array, string $type = ""): bool
    {
        if(!is_null($array)) {
            $found = array_key_exists($key, $array);
            if (!$found && $this->config["debug"]) {
                error_log("WARNING: Failed to find the key '" . $key . "' in the DSpace " . $type . " response.");
            }
            return $found;
        } else {
            // NOTE: It might be a good idea to throw an exception here.
            error_log("ERROR: Checking key: " . $key . ". A null array was provided to the checkKey function. This should not
                happen. There was likely a problem parsing the Dspace API response.");
        }
        return false;
    }


    /**
     * Utility method for validating paths to DSpace response elements.
     * @param array $path array containing the keys to validate
     * @param array $array the DSpace response element
     * @param string $type the type of DSpace entity
     * @return bool
     */
    public function checkPath(array $path, array $array, string $type = "") : bool {
        if (!$path || count($path) == 0) {
            error_log("ERROR: Invalid path array.");
            return false;
        }

        $count = 0;
        foreach($path as $key) {
            if (!$this->checkKey($key, $array, $type)) {
                return false;
            }
            $array = $array[$key];
        }
        return true;
    }

    /**
     * Utility method for DSpace API requests. Uses curl.
     * @param $url string the fully qualified URL
     * @return mixed the DSpace API response
     */
    public function getRestApiResponse(string $url): mixed
    {
        try {

            if ($this->config["debug"]) {
                error_log("DEBUG: DSpace API request: " . $url);
            }
            set_error_handler(
                function ($err_severity, $err_msg, $err_file, $err_line)
                { throw new ErrorException( $err_msg, 0, $err_severity, $err_file, $err_line ); },
                E_WARNING
            );

            if ( ! function_exists( 'curl_init' ) ) {
                die( 'The cURL library is not installed.' );
            }
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            $response = curl_exec( $ch );
            curl_close( $ch );
            restore_error_handler();
            if ($this->config["debug"]) {
                error_log("DEBUG: DSpace REST API response: " . $response);
            }

        } catch (Exception $err) {
            error_log("ERROR: DSpace API request did not return data.");
            error_log($err);
            if (!headers_sent()) {
                $this->outputJSON(self::REQUEST_FAILED,
                    array("HTTP/1.1 400 Invalid Request"));
            }
            return self::REQUEST_FAILED;
        }

        return json_decode($response, true);
    }


}