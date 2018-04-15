<?php

namespace chryssalid\REST;

/**
 * Format rezultatu zwracanego przez funkcje callback.
 * 
 * @author Łukasz Feller
 */
final class RESTCallbackResult {
    /**
     * @var RESTCallbackResponse
     */
    protected $response;

    protected $data = array();

    public function setResponse(RESTCallbackResponse $response) {
        $this->response = $response;
        return $this;
    }

    /**
     * @return RESTCallbackResponse
     */
    public function getResponse() {
        return $this->response;
    }

    public function setData($data) {
        $this->data = $data;
        return $this;
    }
    
    public function addData($key, $data) {
        $this->data[$key] = $data;
        return $this;
    }

    public function getData($key, $default = null) {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function removeData($key) {
        unset($this->data[$key]);
        return $this;
    }
}
