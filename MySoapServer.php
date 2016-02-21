<?php
define("APP_ROOT", __DIR__);

require_once(APP_ROOT . "/app/core/Config.php");
require_once(APP_ROOT . "/app/core/Database.php");
require_once(APP_ROOT . "/app/core/DbHandler.php");
require_once(APP_ROOT . "/app/utils/Validation.php");
require_once(APP_ROOT . "/app/utils/PassHash.php");
require_once(APP_ROOT . "/config/statusCodes.php");
require_once(APP_ROOT . "/config/responseTypes.php");
require_once(APP_ROOT . "/config/constants.php");

/**
 * Created by PhpStorm.
 * User: gasstan
 * Date: 26.1.2016
 * Time: 12:23
 */
class MySoapServer
{

    function getEvent($idEvent)
    {

        $dbHandler = new DbHandler();
        return $dbHandler->getTicketInfo($idEvent);
    }

}

$server = new SoapServer(NULL, ['uri' => 'api.concertian.com/agents/MySoapServer.php']);
$server->setClass('MySoapServer');
$server->handle();