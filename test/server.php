<?php

namespace Escapemania\test;

use Escapemania\ApiCommunication;

require_once '../src/APICommunication.php';

$apiKey = 'TWOJ KLUCZ';
$apiSecret = 'TWOJ SECRET';

$server = new ApiCommunication($apiKey, $apiSecret, 'http://escapemania-api-test/client');
$response = $server->request('test');
if ($response) {
    echo '<pre>' . print_r($response, true) . '</pre>';
}