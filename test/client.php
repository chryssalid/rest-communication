<?php

namespace chryssalid\REST\test;

use chryssalid\REST\RESTCallbackResponse;
use chryssalid\REST\RESTCallbackResult;
use chryssalid\REST\RESTCommunication;

require_once '../src/RESTCallbackResponse.php';
require_once '../src/RESTCommunication.php';

$apiKey = 'TWOJ KLUCZ';
$apiSecret = 'TWOJ SECRET';

$basepath = '/client/';

/**
 * Wywołanie z poziomu serwera //domena/client/test
 */

$server = new RESTCommunication($apiKey, $apiSecret);
$action = str_replace($basepath, '', $_SERVER['REQUEST_URI']);

if ($action == 'test') {
    $server->registerCallback('read:verification', function($parameters) use ($apiKey, $apiSecret) {
        $result = new RESTCallbackResult;
        $response = new RESTCallbackResponse;

        if ($parameters['method'] !== $_SERVER['REQUEST_METHOD']) {
            $response->setStatus(RESTCallbackResponse::STATUS_ERROR);
            $response->setMessage('Invalid HTTP request method.');
            $response->setResponseCode(406);
            $response->setSilent(false);
            $result->setResponse($response);
            return $result;
        }

        if ($method !== RESTCommunication::CALL_METHOD_GET && empty($parameters['content'])) {
            $response->setStatus(RESTCallbackResponse::STATUS_ERROR);
            $response->setMessage('Empty input.');
            $response->setResponseCode(406);
            $response->setSilent(false);
            $result->setResponse($response);
            return $result;
        }

        if ($parameters['apiKey'] !== $apiKey) {
            $response->setStatus(RESTCallbackResponse::STATUS_ERROR);
            $response->setMessage('Invalid api key.');
            $response->setSilent(false);
            $response->setResponseCode(404);
            $result->setResponse($response);
            return $result;
        }

        $hash = hash('sha256', $apiSecret . $parameters['action'] . $parameters['method'] . $parameters['content']);
        if ($hash !== $parameters['apiHash']) {
            $response->setStatus(RESTCallbackResponse::STATUS_ERROR);
            $response->setMessage('Invalid hash.');
            $response->setSilent(false);
            $response->setResponseCode(406);
            $result->setResponse($response);
            return $result;
        }

        $result->setResponse($response);
        return $result;
    });

    $response = $server->read('test', RESTCommunication::CALL_METHOD_GET);
    if ($response) {
        $server->response(['status' => 'ok', 'message' => 'test successful']);
    }
}