<?php

class TaskException extends Exception { }

class Task {
    private $id;
    private $title;
    private $description;
    private $deadline;
    private $completed;

    public function __construct($id, $title, $description, $deadline, $completed) {
        $this->setId($id);
        $this->setTitle($title);
        $this->setDescription($description);
        $this->setDeadline($deadline);
        $this->setCompleted($completed);
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        if (($id !== null) && (!is_numeric($id) || $id <= 0 || $id > 9223372036854775807 || $this->id !== null)) {
            throw new TaskException("task id validation failed");
        }

        $this->id = $id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        if(strlen($title) < 1 || strlen($title) > 255) {
            throw new TaskException("task title validation failed");
        }

        $this->title = $title;
    }
    
    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        if(($description !== null) && strlen($description) > 4096) {
            throw new TaskException("task description validation failed");
        }

        $this->description = $description;
    }
    
    public function getDeadline() {
        return $this->deadline;
    }

    public function setDeadline($deadline) {
        if (($deadline !== null) &&  date_format(date_create_from_format('d/m/Y H:i', $deadline), 'd/m/Y H:i') != $deadline ){
            throw new TaskException("task deadline validation failed");
        }

        $this->deadline = $deadline;
    }

    public function getCompleted() {
        return $this->completed;
    }

    public function setCompleted($completed) {
        $upperCompleted = strtoupper($completed);

        if(strtoupper($upperCompleted) !== 'Y' && strtoupper($upperCompleted) !== 'N') {
            throw new TaskException("task completed validation failed");
        }

        $this->completed = $upperCompleted;
    }

    public function returnTaskAsArray() {
        $task = array();
        $task['id'] = $this->getId();
        $task['title'] = $this->getTitle();
        $task['descriptiom'] = $this->getDescription();
        $task['deadline'] = $this->getDeadline();
        $task['completed'] = $this->getCompleted();

        return $task;
    }
}
?>