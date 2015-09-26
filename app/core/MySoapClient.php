<?php

/**
 * Handling soap connection
 *
 */
class MySoapClient
{
    private static $soapClient = NULL;

    /**
     * private constructor
     */
    private function __construct()
    {
    }

    /**
     * Establishing soap connection
     * @return soap connection handler
     */
    public static function getInstance()
    {
        $soapData = Config::load('soap');

        if (self::$soapClient === NULL) {
            try {
                $params = ['location' => $soapData['LOCATION'], 'uri' => $soapData['URI'], 'trace' => TRUE];
                self::$soapClient = new SoapClient(NULL, $params);
            } catch (SoapFault $e) {
                die("Failed to connect to SOAP server: " . $e->getMessage());
            }
        }

        return self::$soapClient;
    }
}