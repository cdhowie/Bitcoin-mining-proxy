<?php

require_once(dirname(__FILE__) . '/config.inc.php');

session_start();

function db_connect() {
    global $BTC_PROXY;

    return new PDO($BTC_PROXY['db_connection_string'], $BTC_PROXY['db_user'], $BTC_PROXY['db_password']);
}

function db_query($pdo, $query, $args = array()) {
    $q = $pdo->prepare($query);

    $q->execute($args);

    $results = $q->fetchAll(PDO::FETCH_ASSOC);
    $q->closeCursor();

    return $results;
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

function json_error($message, $id) {
    $object = new stdClass();
    $object->error = $message;
    $object->result = null;
    $object->id = $id;

    json_response($object);
}

function json_success($result, $id) {
    $object = new stdClass();
    $object->error = null;
    $object->result = $result;
    $object->id = $id;

    json_response($object);
}

function json_response($object) {
    header('Content-Type: application/json-rpc');
    echo json_encode($object);

    exit;
}

function place_json_call($object, $url, $username = '', $password = '') {
    $authHeader = "";

    if (strlen($username) != 0) {
        $authHeader = "Authorization: Basic " . base64_encode($username . ':' . $password) . "\r\n";
    }

    $context = stream_context_create(array(
        'http'  => array(
            'method'    => 'POST',
            'header'    => "Content-Type: application/json-rpc\r\n$authHeader",
            'content'   => json_encode($object),
            'timeout'   => 5
        )
    ));

    return @json_decode(@file_get_contents($url, false, $context));
}

function echo_html($text) {
    echo htmlspecialchars($text);
}

function make_url($uri) {
    global $BTC_PROXY;

    return $BTC_PROXY['site_uri'] . $uri;
}

function make_absolute_url($uri)
{
    global $BTC_PROXY;

    if (isset($_SERVER['HTTPS'])) {
        $scheme = 'https';
        $default_port = 443;
    } else {
        $scheme = 'http';
        $default_port = 80;
    }

    $port = ($default_port == $_SERVER['SERVER_PORT']) ? "" :
        ":" . $_SERVER['SERVER_PORT'];

    $base = "$scheme://{$_SERVER['SERVER_NAME']}$port{$BTC_PROXY['site_uri']}";

    return $base . $uri;
}

function do_admin_auth() {
    global $BTC_PROXY;

    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        auth_fail();
    }

    if (    $_SERVER['PHP_AUTH_USER'] != $BTC_PROXY['admin_user'] ||
            $_SERVER['PHP_AUTH_PW']   != $BTC_PROXY['admin_password']) {
        auth_fail();
    }
}

function get_tempdata($key)
{
    $value = $_SESSION['tempdata'][$key];

    unset($_SESSION['tempdata'][$key]);

    return $value;
}

?>
