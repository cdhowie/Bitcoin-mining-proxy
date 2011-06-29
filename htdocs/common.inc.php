<?php

/*
 * ./htdocs/common.inc.php
 *
 * Copyright (C) 2011  Chris Howie <me@chrishowie.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__) . '/config.inc.php');

define('DB_SCHEMA_VERSION', 3);

# This header satisfies the Section 13 requirement in the AGPL for both
# unauthenticated users and clients requesting work from the proxy.
header('X-Source-Code: https://github.com/cdhowie/Bitcoin-mining-proxy');

function db_connect() {
    global $BTC_PROXY;

    return new PDO($BTC_PROXY['db_connection_string'], $BTC_PROXY['db_user'], $BTC_PROXY['db_password']);
}

function db_query($pdo, $query, $args = array()) {
    $q = $pdo->prepare($query);

    if ($q === false) {
        return false;
    }

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
    $error = new stdClass();
    $error->code = 0; // TODO
    $error->message = $message;

    $object = new stdClass();
    $object->error = $error;
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

function place_json_call($object, $url, $username = '', $password = '', &$headers) {
    $authHeader = "";

    if (strlen($username) != 0) {
        $authHeader = "Authorization: Basic " . base64_encode($username . ':' . $password) . "\r\n";
    }

    if (isset($object)) {
        $context_options = array(
            'method'    => 'POST',
            'header'    => "Content-Type: application/json-rpc\r\n$authHeader",
            'content'   => json_encode($object),
            'timeout'   => 2
        );
    } else {
        $context_options = array(
            'method'    => 'GET',
            'header'    => $authHeader,
            'timeout'   => 3900
        );
    }

    $context = stream_context_create(array('http' => $context_options));

    $result = @json_decode(@file_get_contents($url, false, $context));

    $headers = $http_response_header;

    return $result;
}

function echo_html($text) {
    echo htmlspecialchars($text);
}

function get_site_uri() {
    global $BTC_PROXY;

    $site_uri = $BTC_PROXY['site_uri'];
    $length = strlen($site_uri);

    while ($length != 0 && $site_uri[$length - 1] == '/') {
        $site_uri = substr($site_uri, 0, --$length);
    }

    return $site_uri;
}

function make_url($uri) {
    return get_site_uri() . $uri;
}

function make_absolute_url($uri) {
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

    $base = "$scheme://{$_SERVER['SERVER_NAME']}$port" . get_site_uri();

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

function format_date($date)
{
    global $BTC_PROXY;

    $obj = new DateTime($date, new DateTimeZone('UTC'));

    $obj->setTimezone(new DateTimeZone($BTC_PROXY['timezone']));

    return $obj->format($BTC_PROXY['date_format']);
}

?>
