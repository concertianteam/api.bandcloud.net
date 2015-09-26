<?php

class Validation
{

    // venue id from database - Global Variable
    static $idVenue;

    /**
     * Verifying requred params
     * $requiredParamsArray = array('param1', 'param2', '...', 'paramN');
     */
    function verifyRequiredParams($requiredParamsArray)
    {
        $success = true;
        $missingParams = "";
        $requestParams = array();
        $requestParams = $_REQUEST;

        // Handling PUT request params
        if ($_SERVER ['REQUEST_METHOD'] == 'PUT') {
            $app = \Slim\Slim::getInstance();
            parse_str($app->request()->getBody(), $requestParams);
        }

        foreach ($requiredParamsArray as $param) {
            if (!isset ($requestParams [$param]) || strlen(trim($requestParams [$param])) <= 0) {
                $success = false;
                $missingParams .= $param . ', ';
            }
        }

        if (!$success) {
            // Required field(s) are missing or empty
            // echo error json and stop the app
            $response = array();
            $app = \Slim\Slim::getInstance();
            $response ["success"] = $success;
            $response ["message"] = 'Required param(s) ' . substr($missingParams, 0, -2) . ' are missing or empty!';
            ClientEcho::echoResponse(BAD_REQUEST, $response);
            $app->stop();
        }
    }

    /**
     * Validating email address
     */
    function validateEmail($email)
    {
        $app = \Slim\Slim::getInstance();
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response ["success"] = false;
            $response ["message"] = 'Email address is not valid';
            ClientEcho::echoResponse(BAD_REQUEST, $response);
            $app->stop();
        }
    }

    /**
     * Middle layer to autenticate every request
     * Checking if the request has valid API key in the 'Authorization' header
     */
    public static function authenticate(\Slim\Route $route)
    {
        // Getting request headers
        $headers = apache_request_headers();
        $response = array();
        $app = \Slim\Slim::getInstance();

        // Verifying Authorization header
        if (isset ($headers ['Authorization'])) {
            $dbHandler = new DbHandler ();

            // get the api key
            $apiKey = $headers ['Authorization'];
            // validating api key
            $idAccount = SoapHandler::isApiKeyValid($apiKey);
            if ($idAccount == ERROR) {
                // api key does not exist in database
                $response ["success"] = false;
                $response ["message"] = "Access Denied. Invalid Api Key!";
                ClientEcho::echoResponse(UNAUTHORIZED, $response);
                $app->stop();
            } else {
                // get venue id
                $venue = $dbHandler->getVenueId($idAccount);
                if ($venue != NULL)
                    Validation::$idVenue = $venue;
            }
        } else {
            // api key is missing in header
            $response ["success"] = false;
            $response ["message"] = "Api key is missing!";
            ClientEcho::echoResponse(BAD_REQUEST, $response);
            $app->stop();
        }
    }
}