<?php

namespace chryssalid\REST;

/**
 * @author Łukasz Feller
 */
final class RESTCallbackResponse {

    const STATUS_OK = 'ok',
          STATUS_ERROR = 'error'
    ;

    protected $status;
    protected $message;
    protected $responseCode = 200;
    protected $silent = true;

    public function __construct($status = self::STATUS_OK) {
        $this->status = $status;
    }

    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    public function getStatus() {
        return $this->status;
    }
    
    public function setMessage($message) {
        $this->message = $message;
        return $this;
    }

    public function getMessage() {
        return $this->message;
    }

    public function getContent() {
        return ['status' => $this->status, 'message' => $this->message];
    }

    public function setResponseCode($code) {
        $this->responseCode = $code;
        return $this;
    }

    public function getResponseCode() {
        return $this->responseCode;
    }

    public function isSilent() {
        return $this->silent;
    }

    public function setSilent($silent) {
        $this->silent = (boolean)$silent;
        return $this;
    }
}
