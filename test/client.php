<?php

namespace chryssalid\REST\test;

use chryssalid\REST\RESTCallbackResponse;
use chryssalid\REST\RESTCommunication;

require_once '../src/RESTCallbackResponse.php';
require_once '../src/RESTCommunication.php';

$apiKey = 'TWOJ KLUCZ';
$apiSecret = 'TWOJ SECRET';

$basepath = '/client/';

$server = new RESTCommunication($apiKey, $apiSecret);
$action = str_replace($basepath, '', $_SERVER['REQUEST_URI']);

if ($action == 'test') {
    $server->registerCallback('read:verification', function($parameters) use ($apiKey, $apiSecret) {
        $response = new RESTCallbackResponse(RESTCallbackResponse::STATUS_OK);
        $response->setSilent(true);
        $response->setResponseCode(200);

        if ($parameters['apiKey'] !== $apiKey) {
            $response->setStatus(RESTCallbackResponse::STATUS_ERROR);
            $response->setMessage('Invalid api key.');
            $response->setSilent(false);
            $response->setResponseCode(404);
        }

        $hash = hash('sha256', $apiSecret . $parameters['action'] . $parameters['method'] . $parameters['content']);
        if ($hash !== $parameters['apiHash']) {
            $response->setStatus(RESTCallbackResponse::STATUS_ERROR);
            $response->setMessage('Invalid hash.');
            $response->setSilent(false);
            $response->setResponseCode(404);
        }
        return $response;
    });

    $response = $server->read('test', RESTCommunication::CALL_METHOD_GET);
    if ($response) {
        $server->response(['status' => 'ok', 'message' => 'test successful']);
    }
}