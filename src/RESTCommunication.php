<?php

namespace chryssalid\REST;

use Exception;

require_once __DIR__ . '\RESTCallbackResult.php';
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

    /**
     * @var RESTCallbackResponse[]
     */
    protected $callbacks = [];

    /**
     * @var RESTCallbackResult[]
     */
    protected $callbackResult= [];

    protected $lastResponse;
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
            $request = '';
        }
        else {
            $request = json_encode($content);
        }
        $hash = hash('sha256', $this->apiSecret . $action . $method . $request);

        $curl = curl_init($this->apiUrl . '/' . $action . ($method === self::CALL_METHOD_GET && !empty($content) ? '?' . http_build_query($content) : ''));
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

        $this->lastResponse = curl_exec($curl);
        if (curl_errno($curl)) {
            $this->errorNo = curl_errno($curl);
            $this->error = curl_error($curl);
            curl_close($curl);
            return false;
        } else {
            curl_close($curl);
            $json = json_decode($this->lastResponse, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				return $json;
			} else {
				$this->error = 'Invalid response: ' . $this->lastResponse;
				return false;
			}
        }
    }

    /**
     * @see ApiCommunicationInterface::read()
     */
    public function read($action) {
        $input = file_get_contents('php://input');
        $headers = apache_request_headers();
        $method = array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'UNKNOWN';

        if (!array_key_exists('Api-Key', $headers) || !array_key_exists('Api-Hash', $headers)) {
            $this->response(['status' => 'error', 'message' => 'Missing headers.'], 406);
            exit;
        }

        $this->callbackResult['onRead'] = $this->callback('onRead', ['apiKey' => $headers['Api-Key'], 'apiHash' => $headers['Api-Hash'], 'action' => $action, 'method' => $method, 'content' => $input]);
        if ($this->callbackResult['onRead'] && $this->callbackResult['onRead']->getResponse() instanceof RESTCallbackResponse) {
            if (!$this->callbackResult['onRead']->getResponse()->isSilent()) {
                $this->response($this->callbackResult['onRead']->getResponse()->getContent(), $this->callbackResult['onRead']->getResponse()->getResponseCode());
                exit;
            }
        }

        if ($method === self::CALL_METHOD_GET) {
            return true;
        }

        $json = json_decode($input, true);
        return json_last_error() === JSON_ERROR_NONE ? $json : false;
    }

    public function registerCallback($name, $function) {
        $this->callbacks[$name] = $function;
    }

    public function getCallbackResult($callbackName) {
        return $this->callbackResult[$callbackName];
    }

    /**
     * @param string $name
     * @param string[] $parameters
     * @return RESTCallbackResponse|null
     */
    public function callback($name, $parameters) {
        if (isset($this->callbacks[$name])) {
            $return = $this->callbacks[$name]($parameters);
            if ($return instanceof RESTCallbackResult) {
                return $return;
            } else {
                throw new Exception(sprintf('Invalid callback\'s %s return. RESTCallbackResult is required.', $name));
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

    public function getLastResponse() {
        return $this->lastResponse;
    }
}
