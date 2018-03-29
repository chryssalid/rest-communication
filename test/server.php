<?php

namespace chryssalid\REST\test;

use chryssalid\REST\RESTCommunication;

require_once '../src/RESTCommunication.php';

$apiKey = 'TWOJ KLUCZ';
$apiSecret = 'TWOJ SECRET';

$server = new RESTCommunication($apiKey, $apiSecret, 'http://escapemania-api-test/client');
$response = $server->request('test');
if ($response) {
    echo '<pre>' . print_r($response, true) . '</pre>';
}