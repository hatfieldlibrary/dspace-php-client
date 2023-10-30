<?php
require __DIR__ . "/Controller.php";
require_once __DIR__ . "/Utils.php";

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

$utils = new Utils();
$uri = $utils->getUriSegments();
$controller = new Controller();
// lists the available endpoints
if (count($uri) == 2) {
    if($uri[1] == "api") {
        $controller->getEndpoints();
    } else {
        $utils->outputJSON("404 Not Found. Method not found.", array('HTTP/1.1 404 Not Found. '));
    }
}
// the api endpoints
$method = $uri[2];
if ($method) {
    if (!method_exists($controller, $method)) {
        error_log("ERROR: API function not found.");
        $utils->outputJSON("404 Not Found. An valid API endpoint was not provided in the request.", array('HTTP/1.1 404 Not Found. '));
    }
    $r = "";
    // zero parameter endpoints
    if (count($uri) == 3) {
        try {
            set_error_handler(
                function ($err_severity, $err_msg, $err_file, $err_line)
                { throw new ErrorException( $err_msg, 0, $err_severity, $err_file, $err_line ); },
                E_WARNING
            );
            $r = $controller->{$method};
            restore_error_handler();
        } catch (Exception $err) {
            $utils->outputJSON("Invalid request.", array("HTTP/1.1 400 Invalid Request"));
            error_log($err);
        }
    }
    // endpoints with uuid parameter
    $uuid = "";
    if (count($uri) > 3) {
        $uuid = $uri[3];
        $r = $controller->{$method}($uuid);
    }
    echo $r;

} else {
    error_log("ERROR: No method was provided in request.");
    $utils->outputJSON("404 Not Found. No method provided in request.", array('HTTP/1.1 404 Not Found.'));
}

