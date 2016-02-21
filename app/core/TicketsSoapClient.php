<?php

/**
 * Handling soap connection
 *
 */
class TicketsSoapClient
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
                $params = ['location' => $soapData['TICKETS_LOCATION'], 'uri' => $soapData['TICKETS_URI'], 'trace' => TRUE];
                self::$soapClient = new SoapClient(NULL, $params);
            } catch (SoapFault $e) {
                die("Failed to connect to SOAP server: " . $e->getMessage());
            }
        }

        return self::$soapClient;
    }
}