<?php

// For development use only.
header("Access-Control-Allow-Origin: *");

require __DIR__ . "/Controller.php";
require_once __DIR__ . "/Utils.php";

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

$utils = new Utils();
$controller = new Controller();

$uri = $utils->getUriSegments();
$response = "";


if (count($uri) < 4) {
    error_log("ERROR: No method was provided in request.");
    $utils->outputJSON("404 Not Found. No method provided in request.", array('HTTP/1.1 404 Not Found.'));
}
if (count($uri) == 4) {
    $resource = $uri[3];
    $response = callController($controller, $utils, $resource);
}
if (count($uri) == 5) {
    $resource = $uri[3];
    $uuid = $uri[4];
    $response = callController($controller, $utils, $resource, $uuid);
}
if (count($uri) == 6) {
    $uuid = $uri[4];
    $resource = $uri[3];
    $qualifier = $uri[5];
    $method = $resource . $qualifier;
    $response = callController($controller, $utils, $method, $uuid);
}
    echo $response;

function checkControllerMethod(object & $controller, object & $utils, string $method): void
{
    if (!method_exists($controller, $method)) {
        error_log("ERROR: API function not found.");
        $utils->outputJSON("404 Not Found. An valid API endpoint was not provided in the request.", array('HTTP/1.1 404 Not Found. '));
    }
}
function callController(object & $controller, object & $utils, string $method, string $uuid = ""): mixed
{
    checkControllerMethod($controller, $utils, $method);
    try {
        set_error_handler(
            function ($err_severity, $err_msg, $err_file, $err_line)
            { throw new ErrorException( $err_msg, 0, $err_severity, $err_file, $err_line ); },
            E_WARNING
        );
        if ($uuid) {
            $resp = $controller->{$method}($uuid);
        } else {
            $resp = $controller->{$method}();
        }
        restore_error_handler();
        return $resp;
    } catch (Exception $err) {
        error_log($err);
        $utils->outputJSON("There was an error when executing the requested service. Check the server log for details. For complete logs use debug mode in Configuration.", array("HTTP/1.1 500 Server Error"));
    }
    return null;
}

