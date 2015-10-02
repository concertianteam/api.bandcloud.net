<?php
mb_internal_encoding("UTF-8");

header("Access-Control-Allow-Origin: *"); // docasne!!
header('Access-Control-Allow-Headers: Content-Type');
header("Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");
header("X-Frame-Options: SAMEORIGIN");
header("Connection: close");

define("APP_ROOT", __DIR__);

require_once(APP_ROOT . "/app/core/Config.php");
require_once(APP_ROOT . "/app/core/Database.php");
require_once(APP_ROOT . "/app/core/DbHandler.php");
require_once(APP_ROOT . "/app/core/SoapHandler.php");
require_once(APP_ROOT . "/app/core/MySoapClient.php");
require_once(APP_ROOT . "/app/utils/ClientEcho.php");
require_once(APP_ROOT . "/app/utils/Validation.php");
require_once(APP_ROOT . "/app/utils/PassHash.php");
require_once(APP_ROOT . "/config/statusCodes.php");
require_once(APP_ROOT . "/config/responseTypes.php");
require_once(APP_ROOT . "/config/constants.php");
require(APP_ROOT . "/libs/Slim/Slim.php");

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim ();

$app->get('/', function () use ($app) {
    // test
    Database::getInstance(); // ok?
    echo('idze');
});

/* ----------------------REGISTER METHODS------------------------- */

/**
 * Account Registration
 * url - /register
 * method - POST
 * params - name, email, photo URL, state, city, zip code 1-2
 */
$app->post('/register', function () use ($app) {
    // check for required params
    $validation = new Validation ();
    $validation->verifyRequiredParams(array(
        'email',
        'password',
        'name',
        'addressFirst',
        'city',
        'state',
        'zipCode'
    ));

    $response = array();

    // reading post params
    $email = $app->request->post('email');
    $password = $app->request->post('password');
    $name = $app->request->post('name');
    $addressFirst = $app->request->post('addressFirst');
    $addressSecond = $app->request->post('addressSecond');
    $city = $app->request->post('city');
    $country = $app->request->post('country');
    $state = $app->request->post('state');
    $zipCode = $app->request->post('zipCode');
    $urlImage = $app->request->post('urlImage');

    // validating email address
    $validation->validateEmail($email);

    $dbHandler = new DbHandler ();
    $res = $dbHandler->createAccount($email, $password, $name, $addressFirst, $addressSecond, $city, $country, $state, $zipCode, $urlImage);

    if ($res == ACCOUNT_CREATED_SUCCESSFULLY) {
        $response ["success"] = TRUE;
        $response ["message"] = "You are successfully registered";
        ClientEcho::echoResponse('CREATED', $response);
    } else if ($res == ACCOUNT_CREATE_FAILED) {
        $response ["success"] = FALSE;
        $response ["message"] = "Oops! An error occurred while registering";
        ClientEcho::echoResponse('SUCCESS', $response);
    } else if ($res == ACCOUNT_ALREADY_EXIST) {
        $response ["success"] = FALSE;
        $response ["message"] = "Sorry, this email already existed";
        ClientEcho::echoResponse(OK, $response);
    }
});

/**
 * Login
 * url - /auth
 * method - POST
 * params - email, password
 */
$app->post('/auth', function () use ($app) {
    // check for required params
    $validation = new Validation ();
    $validation->verifyRequiredParams(array(
        'email',
        'password'
    ));
    // reading post params
    $email = $app->request->post('email');
    $password = $app->request->post('password');
    $response = array();

    $dbHandler = new DbHandler ();
    $apiKey = SoapHandler::login($email, $password);
    // check for correct email and password
    if ($apiKey != INVALID_CREDENTIALS) {
        // get Account by email
        $account = $dbHandler->getAccountByEmail($email);
        if ($account != NULL) {
            $response ["success"] = TRUE;
            $response ['apiKey'] = $apiKey;
            $response ['name'] = $account ['name'];
            $response ['email'] = $account ['email'];
            $response ['urlPhoto'] = $account ['urlPhoto'];
            $response ['idAccount'] = $account ['idAccount'];
            $response ['state'] = $account ['state'];
            $response ['city'] = $account ['city'];
            $response ['zipCode'] = $account ['zip'];
            $response ['addressFirst'] = $account ['address_1'];
            $response ['addressSecond'] = $account ['address_2'];

            ClientEcho::echoResponse(OK, $response);
        } else {
            // unknown error occured
            $response ['success'] = FALSE;
            $response ['message'] = "An error occured. Please try again!";
            ClientEcho::echoResponse(INTERNAL_SERVER_ERROR, $response);
        }
    } else {
        // account credentials are wrong
        $response ['success'] = FALSE;
        $response ['message'] = "Login failed. Incorrect credentials!";
        ClientEcho::echoResponse(UNAUTHORIZED, $response);
    }
});

/**
 * Logout
 * url - /auth
 * method - DELETE
 * params - api_key
 */
$app->delete('/auth', array(
    'Validation',
    'authenticate'
), function () use ($app) {
    $headers = apache_request_headers();
    $apiKey = $headers ['Authorization'];
    // logout account
    $res = SoapHandler::logout($apiKey);

    // chceck if logging out was successfull
    if ($res) {
        // account was logged out
        $response ['success'] = TRUE;
        $response['message'] = "Successfully logged out.";
        ClientEcho::echoResponse('SUCCESS', $response);
    } else {
        // unknown error occurred
        $response ['success'] = FALSE;
        $response ['message'] = "An error occurred. Please try again!";
        ClientEcho::echoResponse(INTERNAL_SERVER_ERROR, $response);
    }
});

/**
 * changePassword
 * url - /auth/changePassword
 * method - post
 * params - api_key, oldPwd, new Pwd
 *//*
$app->post('/auth/changepassword', array(
    'Validation',
    'authenticate'
), function () use ($app) {
    $validation = new Validation ();
    $validation->verifyRequiredParams(array(
        'oldPwd',
        'newPwd'
    ));
    $headers = apache_request_headers();
    $apiKey = $headers ['Authorization'];

    $oldPwd = $app->request->post('oldPwd');
    $newPwd = $app->request->post('newPwd');

    $res = SoapHandler::changePassword($apiKey, $oldPwd, $newPwd);

    // chceck if logging out was successfull
    if ($res) {
        // account was logged out
        $response ['success'] = TRUE;
        $response['message'] = "Password successfully changed.";
        ClientEcho::echoResponse('SUCCESS', $response);
    } else {
        // unknown error occurred
        $response ['success'] = FALSE;
        $response ['message'] = "An error occurred. Please try again!";
        ClientEcho::echoResponse(INTERNAL_SERVER_ERROR, $response);
    }
});*/

/* ----------------------EVENTS METHODS------------------------- */

/**
 * Create new event
 * url - /events
 * method - POST
 * params - idVenue, name, dateTime, status, visible
 */
$app->post('/events', array(
    'Validation',
    'authenticate'
), function () use ($app) {
    $validation = new Validation ();
    $dbHandler = new DbHandler ();
    $validation->verifyRequiredParams(array(
        'idVenue',
        'name',
        'status',
        'visible'
    ));

    // reading post params
    $idVenue = $app->request->post('idVenue');
    $name = $app->request->post('name');
    $date = $app->request->post('date');
    $time = $app->request->post('time');
    $status = $app->request->post('status');
    $visible = $app->request->post('visible');
    //$bands = ( array )$app->request->post('bands');

    $eventId = $dbHandler->createEvent($idVenue, $name, $date, $time, $status, $visible);

    $response = array();
    if ($eventId != NULL) {
        $response ["success"] = TRUE;
        $response ["message"] = "Event created successfully";
        $response ["idEvent"] = $eventId;
    } else {
        $response ["success"] = FALSE;
        $response ["message"] = "Failed to create Event. Please try again ";
    }

    ClientEcho::echoResponse(CREATED, $response);
});

/**
 * Create new event - doèasné
 * url - /events
 * method - POST
 * params - idVenue, name, dateTime, status, visible, idSoundengineer, Bandsarray
 */
$app->post('/events/unregistered', function () use ($app) {
    $validation = new Validation ();
    $dbHandler = new DbHandler ();
    $validation->verifyRequiredParams(array(
        'idVenue',
        'eventName',
        'date',
        'time'
    ));

    // reading post params
    $idVenue = $app->request->post('idVenue');
    $eventName = $app->request->post('eventName');
    $date = $app->request->post('date');
    $time = $app->request->post('time');

    $eventId = $dbHandler->createEvent($idVenue, $eventName, $date, $time, 1, 0);

    $response = array();
    if ($eventId != NULL) {
        $response ["success"] = TRUE;
        $response ["message"] = "Event created successfully";
    } else {
        $response ["success"] = FALSE;
        $response ["message"] = "Failed to create Event. Please try again ";
    }

    ClientEcho::echoResponse(CREATED, $response);
});

/**
 * Listing all events
 * url - /events
 * method - GET
 */
$app->get('/events', array(
    'Validation',
    'authenticate'
), function () {
    $dbHandler = new DbHandler ();

    // fetching all events
    $result = $dbHandler->getAllEvents();

    ClientEcho::buildResponse($result, EVENT);
});

/**
 * Listing single event
 * url - /events/:id
 * method - GET
 */
$app->get('/events/:id', array(
    'Validation',
    'authenticate'
), function ($idEvent) {
    $dbHandler = new DbHandler ();

    // fetching all events
    $result = $dbHandler->getSingleEvent($idEvent);
    ClientEcho::buildResponse($result, EVENT);
});

/**
 * Update event
 * url - /events/:id
 * method - PUT
 * params - idVenue, name, datetime, status, visible, Bandsarray
 */
$app->put('/events/:id', array(
    'Validation',
    'authenticate'
), function ($idEvent) use ($app) {
    $validation = new Validation ();
    // check for required params
    $validation->verifyRequiredParams(array(
        'name',
        'datetime',
        'status',
        'visible'
    ));
    $name = $app->request->put('name');
    $datetime = $app->request->put('datetime');
    $status = $app->request->put('status');
    $visible = $app->request->put('visible');
    //$bands = $app->request->put('bands');

    $dbHandler = new DbHandler ();
    $response = array();

    // updating event
    $result = $dbHandler->updateEvent($idEvent, $name, $datetime, $status, $visible, $bands);
    if ($result) {
        // event successfully updated
        $response ["success"] = TRUE;
        $response ["message"] = "Event updated successfully";
    } else {
        // event failed to update
        $response ["success"] = FALSE;
        $response ["message"] = "Event failed to update. Please try again!";
    }
    ClientEcho::echoResponse(OK, $response);
});

/**
 * Delete event
 * url - /events/:id
 * method - DELETE
 */
$app->delete('/events/:id', array(
    'Validation',
    'authenticate'
), function ($idEvent) {
    $dbHandler = new DbHandler ();
    $response = array();

    // delete event
    $result = $dbHandler->deleteEvent($idEvent);
    if ($result) {
        // event successfully deleted
        $response ["success"] = TRUE;
        $response ["message"] = "Event deleted successfully";
    } else {
        // event failed to update
        $response ["success"] = FALSE;
        $response ["message"] = "Event failed to delete. Please try again!";
    }
    ClientEcho::echoResponse(OK, $response);
});

/* ----------------------BANDS METHODS-------------------------

/**
 * Create new band
 * url - /bands
 * method - POST
 * params - ID Venue, name, email
 *
$app->post('/bands', array(
    'Validation',
    'authenticate'
), function () use ($app) {
    $validation = new Validation ();
    $dbHandler = new DbHandler ();
    $validation->verifyRequiredParams(array(
        'name',
        'email'
    ));

    // reading post params
    $name = $app->request->post('name');
    $email = $app->request->post('dateTime');

    $bandId = $dbHandler->createBand($name, $email);

    $response = array();
    if ($bandId != NULL) {
        $response ["success"] = TRUE;
        $response ["message"] = "Band created successfully";
        $response ["idEvent"] = $bandId;
    } else {
        $response ["success"] = FALSE;
        $response ["message"] = "Failed to create Band. Please try again ";
    }

    ClientEcho::echoResponse(CREATED, $response);
});

/**
 * Listing all bands
 * url - /bands
 * method - GET
 *
$app->get('/bands', array(
    'Validation',
    'authenticate'
), function () {
    $dbHandler = new DbHandler ();

    // fetching all bands
    $result = $dbHandler->getAllBands();
    ClientEcho::buildResponse($result, BAND);
});

/**
 * Listing single band
 * url - /bands/:id
 * method - GET
 *
$app->get('/bands/:id', array(
    'Validation',
    'authenticate'
), function ($idBand) {
    $dbHandler = new DbHandler ();

    // fetching single band
    $result = $dbHandler->getSingleBand($idBand);
    ClientEcho::buildResponse($result, BAND);
});

/**
 * Update band
 * url - /bands/:id
 * method - PUT
 * params - name, email
 *
$app->put('/bands/:id', array(
    'Validation',
    'authenticate'
), function ($idBand) use ($app) {
    $validation = new Validation ();
    // check for required params
    $validation->verifyRequiredParams(array(
        'name',
        'email'
    ));

    $name = $app->request->put('name');
    $email = $app->request->put('email');

    $dbHandler = new DbHandler ();
    $response = array();

    // updating band
    $result = $dbHandler->updateBand($idBand, $name, $email);
    if ($result) {
        // band successfully updated
        $response ["success"] = TRUE;
        $response ["message"] = "Band updated successfully";
    } else {
        // band failed to update
        $response ["success"] = FALSE;
        $response ["message"] = "Band failed to update. Please try again!";
    }
    ClientEcho::echoResponse(OK, $response);
});

/**
 * Delete band
 * url - /bands/:id
 * method - DELETE
 *
$app->delete('/bands/:id', array(
    'Validation',
    'authenticate'
), function ($idBand) {

    $dbHandler = new DbHandler ();
    $response = array();

    // delete band
    $result = $dbHandler->deleteBand($idBand);

    if ($result) {
        // band successfully deleted
        $response ["success"] = TRUE;
        $response ["message"] = "Band deleted successfully";
    } else {
        // band failed to update
        $response ["success"] = FALSE;
        $response ["message"] = "Band failed to delete. Please try again!";
    }
    ClientEcho::echoResponse(OK, $response);
});

 ----------------------VENUES METHODS------------------------- */

/**
 * Listing all venues
 * url - /venues
 * method - GET
 */
$app->get('/venues', array(
    'Validation',
    'authenticate'
), function () {
    $dbHandler = new DbHandler ();

    // fetching all venues
    $result = $dbHandler->getAllVenues();
    ClientEcho::buildResponse($result, VENUE);
});

/**
 * Listing single venue
 * url - /venues/:id
 * method - GET
 */
$app->get('/venues/:id', array(
    'Validation',
    'authenticate'
), function ($idVenue) {
    $dbHandler = new DbHandler ();

    // fetching single venue
    $result = $dbHandler->getSingleVenue($idVenue);
    ClientEcho::buildResponse($result, VENUE);
});

/**
 * Update venue photo
 * url - /venues/:id
 * method - POST
 * params - name, email
 */
$app->put('/venues', array(
    'Validation',
    'authenticate'
), function () use ($app) {
    $validation = new Validation ();
    // check for required params
    $validation->verifyRequiredParams(array(
        'urlPhoto'
    ));

    $urlPhoto = $app->request->put('urlPhoto');

    $dbHandler = new DbHandler ();
    $response = array();

    // updating venue photo
    $result = $dbHandler->updateVenue($urlPhoto);
    if ($result) {
        // venue successfully updated
        $response ["success"] = TRUE;
        $response ["message"] = "Venue updated successfully";
    } else {
        // venue failed to update
        $response ["success"] = FALSE;
        $response ["message"] = "Venue failed to update. Please try again!";
    }
    ClientEcho::echoResponse(OK, $response);
});

/**
 * Delete venue
 * url - /venues/:id
 * method - DELETE
 */
$app->delete('/venues/:id', array(
    'Validation',
    'authenticate'
), function ($idVenue) {
    //???????????????????????????
});

/* ----------------------FAVORITES METHODS------------------------- */

/**
 * Create new favourite venue
 * url - /favourites
 * method - POST
 * params - idVenue1, idVenue2
 */
$app->post('/favourites', array(
    'Validation',
    'authenticate'
), function () use ($app) {
    $validation = new Validation ();
    $dbHandler = new DbHandler ();
    $validation->verifyRequiredParams(array(
        'idVenue'
    ));

    // reading post params
    $idVenue = $app->request->post('idVenue');

    $favouriteId = $dbHandler->createFavourite($idVenue);

    $response = array();
    /*if ($bandId != NULL) {
        $response ["success"] = TRUE;
        $response ["message"] = "favourite created successfully";
        $response ["idFavourite"] = $favouriteId;
    } else {
        $response ["success"] = FALSE;
        $response ["message"] = "Failed to create favourite. Please try again ";
    }*/

    ClientEcho::echoResponse(CREATED, $response);
});

/**
 * Listing all favourite venues
 * url - /favourites
 * method - GET
 */
$app->get('/favourites', array(
    'Validation',
    'authenticate'
), function () {
    $dbHandler = new DbHandler ();

    // fetching all venues
    $result = $dbHandler->getAllFavourites();
    ClientEcho::buildResponse($result, FAVOURITE);
});

/**
 * Delete favourite venue
 * url - /favourites/:id
 * method - DELETE
 */
$app->delete('/favourites/:id', array(
    'Validation',
    'authenticate'
), function ($idFavourite) {
    $dbHandler = new DbHandler ();
    $response = array();

    // delete favourite
    $result = $dbHandler->deleteFavourite($idFavourite);

    if ($result) {
        // favourite successfully deleted
        $response ["success"] = TRUE;
        $response ["message"] = "favourite deleted successfully";
    } else {
        // favourite failed to update
        $response ["success"] = FALSE;
        $response ["message"] = "favourite failed to delete. Please try again!";
    }
    ClientEcho::echoResponse(OK, $response);
});
$app->run();