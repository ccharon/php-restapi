<?php

require_once('Database.php');
require_once('../model/Response.php');
require_once('../repo/TaskRepository.php');

/**
 * Zentrale Methode die wird aufgerufen und dann wird je nach Parametern verzweigt,
 * in den Parametern wird dann nach HTTP Methode geschaut
 */
function handleRequest() {
    $taskRepo = new TaskRepository(Database::connectDb());

    //if ($_SERVER['REQUEST_METHOD'] == 'GET' && array_key_exists("taskId", $_GET)) {
    if (array_key_exists("taskId", $_GET)) {
        handleRequestWithTaskIdParam($taskRepo);

    } elseif (array_key_exists("completed", $_GET)) {
        handleRequestWithCompletedParam($taskRepo);

    } elseif (array_key_exists("page", $_GET)) {
        handleRequestWithPageParam($taskRepo);

    } elseif (empty($_GET)) {
        handleRequestWithoutParam($taskRepo);
        
    } else {
        $response = new Response(404, false, null, "endpoint not found", false);
        $response->send();
    }    
}

/**
 * Alle Requests mit Page Parameter landen hier
 */
function handleRequestWithPageParam($taskRepo) {
    $page = $_GET['page'];

        if ($page == '' || !is_numeric($page)) {
            $response = new Response(400, false, null, "page can not be blank or must be numeric", false);
            $response->send();
            exit();
        }

        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                $response = getTasksPage($taskRepo, $page); 
                $response->send();
                break;

            default:
                $response = new Response(405, false, null, "request method not allowed", false);
                $response->send();
                break;
        }
}

/**
 * Alle Requests mit TaskId Parameter landen hier
 */
function handleRequestWithTaskIdParam($taskRepo) {
    $taskId = $_GET['taskId'];

        if ($taskId == '' || !is_numeric($taskId)) {
            $response = new Response(400, false, null, "task id can not be blank or must be numeric", false);
            $response->send();
            exit();
        }

        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                // Task lesen
                $response = getTaskById($taskRepo, $taskId); 
                $response->send();
                break;

            case "DELETE":
                // Task loeschen
                $response = deleteTaskById($taskRepo, $taskId); 
                $response->send();
                break;

            case "PATCH": 
                // Task aktualisieren
                $response = updateTask($taskRepo, $taskId); 
                $response->send();
                break;

            default:
                // alles andere wird nicht unterstuetzt
                $response = new Response(405, false, null, "request method not allowed", false);
                $response->send();
                break;
        }
}

/**
 * Alle Requests mit Completed Parameter landen hier
 */
function handleRequestWithCompletedParam($taskRepo) {
    $completed = $_GET['completed'];

    if ($completed !== 'Y' && $completed !== 'N') {
        $response = new Response(400, false, null, "completed filter must be Y or N", false);
        $response->send();
        exit;
    }

    switch ($_SERVER['REQUEST_METHOD']) {
        case "GET":
            // lesen aller Tasks unterschieden nach complete / incomplete
            $response = getTasksByCompleted($taskRepo, $completed);
            $response->send();
            break;

        default:
            // alles andere wird nicht unterstuetzt
            $response = new Response(405, false, null, "request method not allowed", false);
            $response->send();
            break;
    }
}

/**
 * Alle Requests ohne Parameter landen hier
 */
function handleRequestWithoutParam($taskRepo) {
    switch ($_SERVER['REQUEST_METHOD']) {
        case "GET":
            // Task lesen
            $response = getAllTasks($taskRepo);
            $response->send();
            break;

        case "POST":
            $response = createTask($taskRepo);
            $response->send();
            break;

        default:
            $response = new Response(405, false, null, "request method not allowed", false);
            $response->send();
            break;
    }
}

/**
 * Tasks seitenweise ausgeben
 */
function getTasksPage($taskRepo, $page) {
    $pageSize = 20;
    $tasksCount = $taskRepo->getTasksCount();

    $numPages = ceil($tasksCount/$pageSize);

    if ($numPages == 0) {
        $numPages = 1;
    }

    if($page > $numPages || $page == 0) {
        return new Response(404, false, null, "page not found", false);
    }

    $offset = ($page == 1 ? 0 : (($page - 1) * $pageSize));

    $tasks  = $taskRepo->getTasksPage($pageSize, $offset);
    
    $returnData = array();
    $returnData['rows_returned'] = sizeof($tasks);
    $returnData['total_rows'] = $tasksCount;
    $returnData['total_pages'] = $numPages;

    if ($page < $numPages) {
        $returnData['has_next_page'] = true;
    } else {
        $returnData['has_next_page'] = false;
    }

    if ($page > 1 ) {
        $returnData['has_previous_page'] = true;
    } else {
        $returnData['has_previous_page'] = false;
    }

    $returnData['tasks'] = $tasks;    

    return new Response(200, true, $returnData, null, true);
}

/**
 * Task mit einer bestimmten Id aktualisieren
 */
function updateTask($taskRepo, $taskId) {
    if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        return new Response(400, false, null, "content type header is not set to json", false);
    }

    $rawPatchData = file_get_contents('php://input');
    if (!$jsonData = json_decode($rawPatchData)) {
        return new Response(400, false, null, "request body is not valid json", false);
    }

    $task = $taskRepo->getTask($taskId);

    if (is_null($task)) {
        return new Response(400, false, null, "task not found for update", false);
    }

    $title_updated = false;
    $description_updated = false;
    $deadline_updated = false;
    $completed_updated = false;

    if (isset($jsonData->title)) {
        $title_updated = true;
        $task->setTitle($jsonData->title);
    }
    if (isset($jsonData->description)) {
        $description_updated = true;
        $task->setDescription($jsonData->description);
    }
    if (isset($jsonData->deadline)) {
        $deadline_updated = true;
        $task->setDeadline($jsonData->deadline);
    }
    if (isset($jsonData->completed)) {
        $completed_updated = true;
        $task->setCompleted($jsonData->completed);
    }

    if ($title_updated === false && $description_updated === false && $deadline_updated == false && $completed_updated === false) {
        return new Response(400, false, null, "no task fields provided", false);
    }

    $task = $taskRepo->updateTask($task);

    if ($task !== null) {
        $taskArray[] = $task->returnTaskAsArray();

        $returnData = array();
        $returnData['rows_returned'] = 1;
        $returnData['tasks'] = $taskArray;

        return new Response(200, true, $returnData, null, false);
    } 

   new Response(500, false, null, "database error", false);
}

/**
 * Task erstellen
 */
function createTask($taskRepo) {
    if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        return new Response(400, false, null, "content type header is not set to json", false);
    }

    $rawPostData = file_get_contents('php://input');
    if (!$jsonData = json_decode($rawPostData)) {
        return new Response(400, false, null, "request body is not valid json", false);
    }

    if (!isset($jsonData->title) || !isset($jsonData->completed)) {
        $response = new Response(400, false, null, null, false);
        if (!isset($jsonData->title)) {
            $response->addMessage("title is mandatory and must be provided");
        }
        if (!isset($jsonData->completed)) {
            $response->addMessage("completed is mandatory and must be provided");
        }

        return $response;
    }

    $newTask = new Task(
        null, 
        $jsonData->title, 
        (isset($jsonData->description) ? $jsonData->description : null),
        (isset($jsonData->deadline) ? $jsonData->deadline : null),
        $jsonData->completed  
    );

    $task = $taskRepo->createTask($newTask);

    if ($task !== null) {
        $taskArray[] = $task->returnTaskAsArray();

        $returnData = array();
        $returnData['rows_returned'] = 1;
        $returnData['tasks'] = $taskArray;

        return new Response(201, true, $returnData, null, false);
    } 

   new Response(500, false, null, "database error", false);
}

function getAllTasks($taskRepo) {
    $tasks = $taskRepo->getAllTasks();

    $returnData = array();
    $returnData['rows_returned'] = sizeof($tasks);
    $returnData['tasks'] = $tasks;

    return new Response(200, true, $returnData, null, true);
}


function getTaskById($taskRepo, $taskId) {
    $task = $taskRepo->getTask($taskId);

    if ($task !== null) {
        $taskArray[] = $task->returnTaskAsArray();

        $returnData = array();
        $returnData['rows_returned'] = 1;
        $returnData['tasks'] = $taskArray;

        return new Response(200, true, $returnData, null, true);
    } 

    return new Response(404, false, null, "task not found", false);
}

function deleteTaskById($taskRepo, $taskId) {
    $deletedRows = $taskRepo->deleteTask($taskId);
    
    if ($deletedRows === 0) { 
        return new Response(404, false, null, "task not found", false);
    } 

    return new Response(200, true, null, "task deleted", false);
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

function getTasksByCompleted($taskRepo, $completed) {
    $tasks = $taskRepo->getTasksByCompleted($completed);

    $returnData = array();
    $returnData['rows_returned'] = sizeof($tasks);
    $returnData['tasks'] = $tasks;

    return new Response(200, true, $returnData, null, true);
}

try {
    handleRequest();

} catch (Exception $ex) {
    $response = handleException($ex);
    $response->send();
}
