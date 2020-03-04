<?php

class Response {
    private $success;
    private $httpStatusCode;
    private $messages = array();
    private $data;
    private $toCache = false; 
    private $responseData = array();
    
    public function __construct($httpStatusCode, $success, $data, $message, $toCache) {
        $this->setHttpStatusCode($httpStatusCode);
        $this->setSuccess($success);
        $this->setData($data);
        $this->addMessage($message);
        $this->toCache($toCache);
    }

    public function setSuccess($success) {
        $this->success = $success;
    }

    public function setHttpStatusCode($httpStatusCode) {
        $this->httpStatusCode = $httpStatusCode;
    }

    public function addMessage($message) {
        if ($message !== null) {
            $this->messages[] = $message;
        }
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function toCache($toCache) {
        $this->toCache = $toCache;
    }

    public function send() {
        header('Content-type: application/json;charset=utf-8');
        
        if ($this->toCache == true) {
            // allow to be cached for max-age seconds
            header('Cache-control: max-age=60');
        } else {
            header('Cache-control: no-cache, no-store');
        }

        // if something is wrong http 500 will be returned
        if (($this->success !== false && $this->success !== true) || !is_numeric($this->httpStatusCode)) {
            http_response_code(500);

            $this->addMessage("Response creation error");

            $this->responseData['statusCode'] = 500;
            $this->responseData['success'] = false;
            $this->responseData['messages'] = $this->messages;

        } else {
            http_response_code($this->httpStatusCode);

            $this->responseData['statusCode'] = $this->httpStatusCode;
            $this->responseData['success'] = $this->success;
            $this->responseData['messages'] = $this->messages;
            $this->responseData['data'] = $this->data;
        }

        // als json im Browser ausgeben
        echo json_encode($this->responseData);
    }
}

?>