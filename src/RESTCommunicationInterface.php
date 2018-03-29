<?php

namespace chryssalid\REST;

/**
 * @author Łukasz Feller <lukaszfeller@gmail.com>
 */
interface RESTCommunicationInterface {

    const CALL_METHOD_GET    = 'GET',
          CALL_METHOD_PUT    = 'PUT',
          CALL_METHOD_POST   = 'POST',
          CALL_METHOD_DELETE = 'DELETE'
    ;

    public function request($action, $content = [], $method = self::CALL_METHOD_GET);

    /**
     * Reads input and returns it.
     * @param string $action - expected action from the input.
     * @param string $method - expected method for the $action.
     * @return string[]|boolean
     */
    public function read($action, $method);

    public function response($response, $response_code = 200);
}
