<?php

namespace chryssalid\REST\test;

use chryssalid\REST\RESTCommunication;

require_once '../src/RESTCommunication.php';

$apiKey = 'TWOJ KLUCZ';
$apiSecret = 'TWOJ SECRET';

$basepath = '/client/';

$server = new RESTCommunication($apiKey, $apiSecret);
$action = str_replace($basepath, '', $_SERVER['REQUEST_URI']);

if ($action == 'test') {
    $response = $server->read('test', RESTCommunication::CALL_METHOD_GET);
    if ($response) {
        $server->response(['status' => 'ok']);
    }
}