<?php

require_once('config.inc.php');

function db_connect() {
    global $BTC_PROXY;

    return new PDO($BTC_PROXY['db_connection_string'], $BTC_PROXY['db_user'], $BTC_PROXY['db_password']);
}

function auth_fail() {
    header('WWW-Authenticate: Basic realm="bitcoin-mining-proxy"');
    header('HTTP/1.0 401 Unauthorized');
    header('Content-Type: text/plain');

    echo "Sorry, I don't know you.";

    exit;
}

function request_fail() {
    header('HTTP/1.0 400 Bad Request');
    header('Content-Type: text/plain');

    echo "Sorry, I don't understand what you just said.";

    exit;
}

?>
