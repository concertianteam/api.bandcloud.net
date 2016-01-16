<?php

class ClientEcho
{
    /**
     * Echoing json response to client
     *
     * $statusCode Http response code
     * $response Json response
     */
    static function echoResponse($statusCode, $response)
    {
        $app = \Slim\Slim::getInstance();
        // Http response code
        $app->status($statusCode);

        // setting response content type to json
        $app->contentType('application/json');

        echo json_encode($response);
    }

    /*
     * @param $result - result from database
     * @param $type - type of response (from responseTypes.php)
     * @param $bands - array of bands if ($type == EVENT), NULL otherwise
     */
    static function buildResponse($result, $type)
    {
        $response ["success"] = TRUE;
        // looping through result and preparing array
        if (!count($result) == 0) {
            switch ($type) {
                case EVENT :
                    $response = ClientEcho::buildEventsResponse($result);
                    break;
                case MONTH :
                    $response = ClientEcho::buildMonthEventsResponse($result);
                    break;
                case VENUE :
                    $response = ClientEcho::buildVenuesResponse($result);
                    break;
                case FAVOURITE :
                    $response = ClientEcho::buildFavsResponse($result);
                    break;
                case VENUENAMES:
                    $response = ClientEcho::buildVenueNamesResponse($result);
                    break;
                default :
                    $response ["success"] = FALSE;
                    $response ["message"] = "Oops! An error occurred!";
                    break;
            }

            ClientEcho::echoResponse(OK, $response);
        } else {
            $response ["success"] = FALSE;
            $response ["message"] = "The requested resource doesn't exists";
            ClientEcho::echoResponse(NOT_FOUND, $response);
        }
    }

    private static function buildEventsResponse($result)
    {
        foreach ($result as $row) {
            $tmp = array();

            $tmp ["idEvent"] = $row ["idEvents"];
            $tmp ["name"] = $row ["name"];
            $tmp ["detail"] = $row ["details"];
            $tmp ["entry"] = $row ["entry"];
            $tmp ["imgUrl"] = $row ["imgUrl"];
            $tmp ["date"] = $row ["date"];
            $tmp ["time"] = $row ["time"];
            $tmp ["status"] = $row ["status"];
            $tmp ["visible"] = $row ["visible"];
            $tmp ["note"] = $row ["note"];
            $tmp ["performerEmail"] = $row ["performerEmail"];
            $tmp ["performerPhoneNumber"] = $row ["performerPhoneNumber"];

            if (isset($row["views"])) {
                $tmp ["views"] = $row ["views"];
            }

            $response ['events'] [] = $tmp;
        }

        return $response;
    }

    private static function buildVenueNamesResponse($result)
    {
        foreach ($result as $row) {
            $tmp = array();

            $tmp ["idVenue"] = $row ["idVenues"];
            $tmp ["name"] = $row ["name"];

            $response[] = $tmp;

        }

        return $response;
    }

    /*private static function buildBandsResponse($result) {
        foreach ( $result as $row ) {
            $tmp = array ();

            $tmp ["idBands"] = $row ["idBands"];
            $tmp ["name"] = $row ["name"];
            $tmp ["email"] = $row ["email"];

            $response ['bands'] [] = $tmp;
        }
        return $response;
    }*/
    private static function buildVenuesResponse($result)
    {
        foreach ($result as $row) {
            $tmp = array();

            $tmp ["idVenue"] = $row ["idVenues"];
            $tmp ["name"] = $row ["name"];
            $tmp ["email"] = $row ["email"];
            $tmp ["urlPhoto"] = $row ["urlPhoto"];
            $tmp ["state"] = $row ["state"];
            $tmp ["city"] = $row ["city"];
            $tmp ["zip"] = $row ["zip"];
            $tmp ["address_1"] = $row ["address_1"];
            $tmp ["address_2"] = $row ["address_2"];
            $tmp ["idAccount"] = $row ["idAccount"];

            $response ['venues'] [] = $tmp;
        }

        return $response;
    }

    private static function buildFavsResponse($result)
    {
        foreach ($result as $row) {
            $tmp = array();

            $tmp ["idFavourite"] = $row ["idFavorite"];
            $tmp ["idVenue"] = $row ["email"];

            $response ['bands'] [] = $tmp;
        }
        return $response;
    }

    private static function buildMonthEventsResponse($result)
    {
        foreach ($result as $row) {
            $tmp = array();

            $tmp ["id"] = $row ["id"];
            $tmp ["venueId"] = $row ["venueId"];
            $tmp ["eventName"] = $row ["eventName"];
            $tmp ["date"] = $row ["date"];
            //$tmp ["stringDate"] = ClientEcho::formatDate($row["date"]);
            $tmp ["time"] = $row ["time"];
            $tmp ["detail"] = $row ["details"];
            $tmp ["entry"] = $row ["entry"];
            $tmp ["imgUrl"] = $row ["imgUrl"];
            $tmp ["visible"] = $row ["visible"];
            $tmp ["venueName"] = $row ["venueName"];
            $tmp ["venueEmail"] = $row["venueEmail"];
            $tmp ["urlPhoto"] = $row ["urlPhoto"];
            $tmp ["address"] = $row['address_1'];
            $tmp ["city"] = $row ["city"];
            $tmp ["state"] = $row ["state"];
            $tmp ["zip"] = $row ["zip"];

            $response ['events'] [] = $tmp;
        }
        return $response;
    }

    /*
     * private static function sortBands($idEvent, $bands) {
     * $tmp = array ();
     * $result = array ();
     *
     * foreach ( $bands as $band ) {
     * if ($band ['idEvent'] != $idEvent) {
     * $tmp [] ['bands'] = array (
     * 'idBand' => $row ["idBand"],
     * 'role' => $row ["role"],
     * 'reward' => $row ["reward"],
     * 'extras' => $row ["extras"],
     * 'technicalNeeds' => $band ['technicalNeeds'],
     * 'note' => $band ['note']
     * );
     * array_push ( $result, $tmp );
     * }
     * }
     * return $result;
     * }
     */
}