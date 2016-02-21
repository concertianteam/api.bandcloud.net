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
        $confirm_url = "https://api.concertian.com/confirmreg.php?code=";

        $mail = new PHPMailer;

        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.websupport.sk';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = 'service@concertian.com';                 // SMTP username
        $mail->Password = 'Heslokleslo2x#';                           // SMTP password
        $mail->Port = 25;                                    // TCP port to connect to

        $mail->CharSet = 'UTF-8';
        $mail->setFrom('service@concertian.com');
        $mail->addAddress($email);     // Add a recipient
        $mail->addReplyTo('service@concertian.com');
        $mail->isHTML(true);                                  // Set email format to HTML


        $confirm_url = "https://api.concertian.com/confirmreg.php?code=";

        $messageHTML = '<span class="outer" style="display: block; background: #f5f5f5; width : 100%; height: 80%; text-align: center;
            box-sizing: border-box; padding: 5%; font-size: 1.2em; font-weight: 600; border-radius: 10px;"><span class="heading" style="display: block">
            Dobrý deň <strong style="font-weight: 100;">' . $name . '</strong>,<br>Ďakujeme za registráciu v systéme concertian LITE, prosím pre dokončenie
            registrácie kliknite dokončiť<br></span><span class="buttonWrapper" style="display: block; text-align: center; margin-top: 8%;">
            <a href="' . $confirm_url . $confirmCode . '" style="display: block; width: 60%; margin-left: 18%; text-align: center; font-size: 1.5em; border-radius: 4px; color: #fff;
            text-decoration: none; background: #ffbb33 ; box-sizing: border-box; padding: 2% 0%; font-weight: 100; cursor: poiter;" >Dokončiť</a>';

        $messagePlain = 'Dobrý deň ' . $name . '
            Dobrý deň ' . $name . ' Ďakujeme za registráciu v systéme concertian LITE, prosím pre dokončenie registrácie skopírujte nasledujúcu adresu do Vášho prehliadača: ' . $confirm_url . $confirmCode;

        $mail->Subject = 'Your registration with Concertian';
        $mail->Body = $messageHTML;
        $mail->AltBody = $messagePlain;

        if (!$mail->send()) {
            return false;
        } else {
            return true;
        }

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
                $auth['apiKey'] = $response['apiKey'];
                $auth['subscriptionId'] = $response['subscriptionId'];
                return $auth;
                break;
            case NOT_PAID:
                //payment is not registered for this account
                $app->response->redirect('https://manager.concertian.com/payment.html?apiKey=' . $response['apiKey']);

                //array('idAccount' => $response['idAccount'])));
                $app->stop();
                break;
            case INVALID_CREDENTIALS:
                // account credentials are wrong
                $res ['success'] = FALSE;
                $res ['message'] = "Login failed. Incorrect credentials!";
                ClientEcho::echoResponse(UNAUTHORIZED, $res);
                $app->stop();
                break;
            case ERROR:
                // Unknown error
                $res ["success"] = FALSE;
                $res ["message"] = "Unknown Error!";
                ClientEcho::echoResponse(BAD_REQUEST, $res);
                $app->stop();
                break;
        }
    }
}