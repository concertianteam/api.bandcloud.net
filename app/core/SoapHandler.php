<?php


class SoapHandler
{

    static function register($confirmCode, $email, $password)
    {
        $client = MySoapClient::getInstance();
        try {
            $response = json_decode($client->register($confirmCode, $email, $password), TRUE);
            if ($response['success'] == TRUE) return $response['accountId'];
            else return ACCOUNT_CREATE_FAILED;
        } catch (SoapFault $e) {
            echo $client->__getLastResponse();
        }
    }

    static function login($email, $password)
    {
        $client = MySoapClient::getInstance();
        try {
            $response = $client->checkLogin($email, $password);
        } catch (SoapFault $e) {
            echo $response = $client->__getLastResponse();
        }

        return Validation::validateLogin($response);

        /*  try {
              $response = json_decode($client->checkLogin($email, $password), TRUE);
              if ($response['success'] == TRUE) {
                  return $response['apiKey'];
              } else {
                  //$response ['idAccount']
                  //$response ['apiKey']
              }
          } catch (SoapFault $e) {
              echo $client->__getLastResponse();
          }*/
    }

    static function addSubscription($apiKey, $subscriptionId)
    {
        $client = MySoapClient::getInstance();
        try {
            $response = json_decode($client->addSubscription($apiKey, $subscriptionId), TRUE);
        } catch (SoapFault $e) {
            echo $client->__getLastResponse();
        }
        return $response['success'];
    }


    static function isApiKeyValid($apiKey)
    {
        $client = MySoapClient::getInstance();
        try {
            return $client->validateApiKey($apiKey);
        } catch (SoapFault $e) {
            echo $client->__getLastResponse();
        }
        return FALSE;
    }

    static function logout($apiKey)
    {
        $client = MySoapClient::getInstance();
        try {
            $response = json_decode($client->logout($apiKey), TRUE);
        } catch (SoapFault $e) {
            echo $client->__getLastResponse();
        }
        return $response['success'];
    }

    static function changePassword($apiKey, $oldPwd, $newPwd)
    {
        $client = MySoapClient::getInstance();
        try {
            $response = json_decode($client->changePassword($apiKey, $oldPwd, $newPwd), TRUE);
        } catch (SoapFault $e) {
            echo $client->__getLastResponse();
        }
        return $response['success'];
    }
}