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

    function sendConfirmationEmail($confirmCode, $email, $name)
    {
        $subject = "Your registration with Concertian";

        $confirm_url = "http://api.bandcloud.net/confirmreg.php?code=";
        $message = "Hello " . $name . "\r\n" .
            "Thanks for your registration with Concertian \r\n" .
            "Please click the link below to confirm your registration.\r\n" .
            "<a href=\"" . $confirm_url . $confirmCode . "\">Confirm</a>\r\n" .
            "\r\n" .
            "Regards,\r\n" .
            "Webmaster\r\n <b>Concertian</b>.com";

        $headers = "From: Concertian < info@concertian.com >\r\n";
        $headers .= "Cc: Concertian < info@concertian.com >\r\n";
        $headers .= "X-Sender: Concertian < info@concertian.com >\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
        $headers .= "X-Priority: 1\r\n"; // Urgent message!
        $headers .= "Return-Path: info@concertian.com\r\n"; // Return path for errors
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=iso-8859-1\r\n";

        mail($email, $subject, $message, $headers);

        return true;
    }

    function makeConfirmationMd5($email)
    {
        $randno1 = rand();
        $randno2 = rand();
        return md5($email . $randno1 . '' . $randno2);
    }

    public static function validateLogin($response)
    {
        $app = \Slim\Slim::getInstance();

        switch ($response['status']) {
            case PAID:
                //everything is ok, returning api key
                return $response['apiKey'];
                break;
            case NOT_PAID:
                //payment is not registered for this account
                $app->response->redirect('https://manager.concertian.com/payment.html?idAccount=' . $response['idAccount']);
                //array('idAccount' => $response['idAccount'])));
                $app->stop();
                break;
            case INVALID_CREDENTIALS:
                // account credentials are wrong
                $response ['success'] = FALSE;
                $response ['message'] = "Login failed. Incorrect credentials!";
                ClientEcho::echoResponse(UNAUTHORIZED, $response);
                $app->stop();
                break;
            case ERROR:
                // Unknown error
                $response ["success"] = false;
                $response ["message"] = "Unknown Error!";
                ClientEcho::echoResponse(BAD_REQUEST, $response);
                $app->stop();
                break;
        }
    }
}