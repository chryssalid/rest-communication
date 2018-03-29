<?php

namespace Escapemania\test;

use Escapemania\ApiCommunication;

require_once '../src/APICommunication.php';

$apiKey = 'TWOJ KLUCZ';
$apiSecret = 'TWOJ SECRET';

$basepath = '/client/';

$server = new ApiCommunication($apiKey, $apiSecret);
$action = str_replace($basepath, '', $_SERVER['REQUEST_URI']);

if ($action == 'test') {
    $response = $server->read('test', ApiCommunication::CALL_METHOD_GET);
    if ($response) {
        $server->response(['status' => 'ok']);
    }
}