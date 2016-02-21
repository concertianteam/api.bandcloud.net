<?php


class TicketsSoapHandler
{

    static function getTickets($idEvent)
    {
        $client = TicketsSoapClient::getInstance();
        try {
            return $client->getTickets($idEvent);
        } catch (SoapFault $e) {
            echo $client->__getLastResponse();
        }
    }

}