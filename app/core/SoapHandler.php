<?php

class SoapHandler
{
    static function register($email, $password)
    {
        $client = MySoapClient::getInstance();
        try {
            $response = json_decode($client->register($email, $password), TRUE);
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
            $response = json_decode($client->checkLogin($email, $password), TRUE);
            if ($response['success'] == TRUE) {
                return $response['apiKey'];
            } else {
                //$response ['idAccount']
                //$response ['apiKey']
            }
        } catch (SoapFault $e) {
            echo $client->__getLastResponse();
        }
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