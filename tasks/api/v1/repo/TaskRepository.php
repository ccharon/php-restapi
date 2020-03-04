<?php

include('../model/Task.php');

class TaskRepository {
    private $dbConnection;

    public function __construct($dbConnection) {
        $this->dbConnection = $dbConnection;
    }

    public function createTask($task) {
        $query = $this->dbConnection->prepare('insert into tbl_task_tasks (title, description, deadline, completed ) values (:title, :description, STR_TO_DATE(:deadline, \'%d/%m/%Y %H:%i\'), :completed)');

        $title = $task->getTitle();
        $description = $task->getDescription();
        $deadline = $task->getDeadline();
        $completed = $task->getCompleted();

        $query->bindParam(':title', $title, PDO::PARAM_STR);
        $query->bindParam(':description', $description, PDO::PARAM_STR);
        $query->bindParam(':deadline', $deadline, PDO::PARAM_STR);
        $query->bindParam(':completed', $completed, PDO::PARAM_STR);
        $query->execute();

        // den gerade geschriebenen Datensatz lesen
        $lastTaskId = $this->dbConnection->lastInsertId();
    
        return getTask($lastTaskId);
    }

    public function getTask($taskId) {
        $query = $this->dbConnection->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbl_task_tasks where id = :taskId');
        $query->bindParam(':taskId', $taskId, PDO::PARAM_INT);
        $query->execute();

        $task = null;

        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
        }

        return $task;
    }

    public function updateTask($task) {
        $query = $this->dbConnection->prepare('update tbl_task_tasks set title = :title, description = :description, deadline = STR_TO_DATE(:deadline, \'%d/%m/%Y %H:%i\'), completed = :completed where id = :taskId');

        $title = $task->getTitle();
        $description = $task->getDescription();
        $deadline = $task->getDeadline();
        $completed = $task->getCompleted();
        $taskId = $task->getId();

        $query->bindParam(':taskId', $taskId, PDO::PARAM_INT);
        $query->bindParam(':title', $title, PDO::PARAM_STR);
        $query->bindParam(':description', $description, PDO::PARAM_STR);
        $query->bindParam(':deadline', $deadline, PDO::PARAM_STR);
        $query->bindParam(':completed', $completed, PDO::PARAM_STR);
        $query->execute();

        return $this->getTask($taskId);
    }

    public function getAllTasks() {
        $query = $this->dbConnection->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbl_task_tasks');
        $query->execute();
    
        $rowCount = $query->rowCount();
    
        $taskArray = array();
    
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
            $taskArray[] = $task->returnTaskAsArray();
        }

        return $taskArray;
    }

    public function getTasksByCompleted($completed) {
        $query = $this->dbConnection->prepare(' select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbl_task_tasks where completed = :completed');
        $query->bindParam(':completed', $completed, PDO::PARAM_STR);
        $query->execute();
    
        $tasks = array();
    
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
            $tasks[] = $task->returnTaskAsArray();
        }
    
        return $tasks;
    }

    public function deleteTask($taskId) {
        $query = $this->dbConnection->prepare('delete from tbl_task_tasks where id = :taskId');
        $query->bindParam(':taskId', $taskId, PDO::PARAM_INT);
        $query->execute();

        return $query->rowCount();
    }

    public function getTasksCount() {
        $query = $this->dbConnection->prepare('select count(id) as totalNumberOfTasks from tbl_task_tasks');
        $query->execute();

        $row = $query->fetch(PDO::FETCH_ASSOC);
        $tasksCount = intval($row['totalNumberOfTasks']);

        return $tasksCount;
    }

    public function getTasksPage($pageSize, $offset) {
        $query = $this->dbConnection->prepare(' select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbl_task_tasks limit :pageSize offset :offset');
        $query->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
        $query->bindParam(':offset', $offset, PDO::PARAM_INT);
        $query->execute();
    
        $tasks = array();
    
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
            $tasks[] = $task->returnTaskAsArray();
        }
    
        return $tasks;
    }
}

?>