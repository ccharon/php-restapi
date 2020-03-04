<?php

require_once('Database.php');
require_once('../model/Response.php');
require_once('../repo/UserRepository.php');

function handleRequest() {
    $usersRepo = new UserRepository(Database::connectDb());

    switch ($_SERVER['REQUEST_METHOD']) {
        case "POST":
            $response = createUser($usersRepo);
            $response->send();
            break;

        default:
            $response = new Response(405, false, null, "request method not allowed", false);
            $response->send();
            break;
    }
}

function createUser($usersRepo) {
    if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        return new Response(400, false, null, "content type header is not set to json", false);
    }

    $rawPostData = file_get_contents('php://input');
    if (!$jsonData = json_decode($rawPostData)) {
        return new Response(400, false, null, "request body is not valid json", false);
    }

    if (!isset($jsonData->fullname) || !isset($jsonData->username) || !isset($jsonData->password)) {
        $response = new Response(400, false, null, null, false);
        (!isset($jsonData->fullname) ? $response->addMessage("fullname is mandatory and must be provided") : false);
        (!isset($jsonData->username) ? $response->addMessage("username is mandatory and must be provided") : false);
        (!isset($jsonData->password) ? $response->addMessage("password is mandatory and must be provided") : false);

        return $response;
    }

    if (isInValidString($jsonData->fullname) || isInValidString($jsonData->username) || isInValidPassword($jsonData->password))  {
        $response = new Response(400, false, null, null, false);
        (isInValidString($jsonData->fullname) ? $response->addMessage("fullname must be between 1 - 255 characters, leading and trailing whitespaces get removed") : false);
        (isInValidString($jsonData->username) ? $response->addMessage("username must be between 1 - 255 characters, leading and trailing whitespaces get removed") : false);
        (isInValidPassword($jsonData->password) ? $response->addMessage("password must be between 10 - 255 characters") : false);
        
        return $response;
    }

}

function isInValidString($string) {
    return (strlen(trim($string)) < 1 || strlen(trim($string)) > 255);
}

function isInValidPassword($string) {
    return (strlen($string) < 10 || strlen($string) > 255);
}


function handleException($ex) {
    if (get_class($ex) === 'TaskException') {
        return new Response(400, false, null, $ex->getMessage(), false);
    } 
    
    if (get_class($ex) === 'PDOException') {
        error_log("Connection error - ".$ex, 0);
        return new Response(500, false, null, "database error", false);
    }

    error_log("unexpected exception - ".$ex, 0);
    return new Response(500, false, null, "server error", false);
}

try {
    handleRequest();

} catch (Exception $ex) {
    $response = handleException($ex);
    $response->send();
}

?>