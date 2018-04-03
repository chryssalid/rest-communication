<?php

namespace chryssalid\REST;

use Exception;

require_once __DIR__ . '\RESTCallbackResponse.php';
require_once __DIR__ . '\RESTCommunicationInterface.php';

/**
 * @author Łukasz Feller <lukaszfeller@gmail.com>
 */
class RESTCommunication implements RESTCommunicationInterface {

    const API_VERSION = 1;

    protected $apiKey;
    protected $apiSecret;
    protected $apiUrl;

    protected $callbacks = [];

    protected $error;
    protected $errorNo;

    public function __construct($apiKey = null, $apiSecret = null, $apiUrl = null) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->apiUrl = empty($apiUrl) ? 'https://escapemania.pl/api/v' . self::API_VERSION . '/call' : $apiUrl;
    }

    public function test() {
        return $this->request('test');
    }

    public function request($action, $content = [], $method = self::CALL_METHOD_GET) {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            throw new Exception('Can\'t send request. API key or API secret is not set.');
        }

        if (!is_array($content)) {
            throw new Exception('$content must be array.');
        }

        if ($method === self::CALL_METHOD_GET) {
            if (!empty($content)) {
                throw new Exception('Can\'t send any content with GET request.');
            }
            $request = '';
        }
        else {
            $request = json_encode($content);
        }
        $hash = hash('sha256', $this->apiSecret . $action . $method . $request);

        $curl = curl_init($this->apiUrl . '/' . $action);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "Api-Key: {$this->apiKey}",
          "Api-Hash: {$hash}"
        ));
        switch($method){
            case self::CALL_METHOD_PUT:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
                break;
            case self::CALL_METHOD_POST:
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
                break;
            case self::CALL_METHOD_DELETE:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
                break;
        }

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $this->errorNo = curl_errno($curl);
            $this->error = curl_error($curl);
            curl_close($curl);
            return false;
        } else {
            curl_close($curl);
            $json = json_decode($response, true);
            return json_last_error() === JSON_ERROR_NONE ? $json : $response;
        }
    }

    /**
     * @see ApiCommunicationInterface::read()
     */
    public function read($action, $method) {
        $input = file_get_contents('php://input');
        $headers = apache_request_headers();
        if (!array_key_exists('Api-Key', $headers) || !array_key_exists('Api-Hash', $headers)) {
            $this->response(['status' => 'error', 'message' => 'Missing headers.'], 406);
            exit;
        }
        if ($method !== $_SERVER['REQUEST_METHOD']) {
            $this->response(['status' => 'error', 'message' => 'Invalid HTTP request method.'], 406);
            exit;
        }
        if ($method !== self::CALL_METHOD_GET && empty($input)) {
            $this->response(['status' => 'error', 'message' => 'Empty input.'], 404);
            exit;
        }

        $callbackResult = $this->callback('read:verification', ['apiKey' => $headers['Api-Key'], 'apiHash' => $headers['Api-Hash'], 'action' => $action, 'method' => $method, 'content' => $input]);
        if ($callbackResult instanceof RESTCallbackResponse) {
            if (!$callbackResult->isSilent()) {
                $this->response($callbackResult->getContent(), $callbackResult->getResponseCode());
                exit;
            }
        }

//        $hash = hash('sha256', $this->apiSecret . $action . $method . $input);
//        if ($hash !== $headers['Api-Hash']) {
//            $this->response(['status' => 'error', 'message' => 'Invalid hash.'], 406);
//            exit;
//        }

        if ($method === self::CALL_METHOD_GET) {
            return true;
        }

        $json = json_decode($input, true);
        return json_last_error() === JSON_ERROR_NONE ? $json : false;
    }

    public function registerCallback($name, $function) {
        $this->callbacks[$name] = $function;
    }

    /**
     * @param string $name
     * @param string[] $parameters
     * @return RESTCallbackResponse|null
     */
    public function callback($name, $parameters) {
        if (isset($this->callbacks[$name])) {
            $return = $this->callbacks[$name]($parameters);
            if ($return instanceof RESTCallbackResponse) {
                return $return;
            } else {
                throw new Exception(sprintf('Invalid callback\'s %s return. RESTCallbackResponse is required.', $name));
            }
        }
    }

    /**
     * Sends response $response to the output as json.
     * @param string[] $response
     * @param int $response_code
     */
    public function response($response, $response_code = 200) {
        header('Content-Type: application/json');
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        http_response_code($response_code);
        echo json_encode($response);
    }

    public function getError() {
        return $this->error;
    }
    
    public function getErrorNo() {
        return $this->errorNo;
    }
}
