<?php

/*
 * ./htdocs/index.php
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

require_once(dirname(__FILE__) . '/common.inc.php');


# Authenticate

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    auth_fail();
}

$pdo = db_connect();

$q = $pdo->prepare('
    SELECT id FROM worker

    WHERE name = :name
      AND password = :password
');

$q->execute(array(
    ':name'     => $_SERVER['PHP_AUTH_USER'],
    ':password' => $_SERVER['PHP_AUTH_PW']));

$worker_id = $q->fetchColumn();
if ($worker_id === FALSE) {
    auth_fail();
}

$q->closeCursor();


function process_work($pdo, $worker_id, $pool_id, $response, $json_id) {
    $q = $pdo->prepare('
        INSERT INTO work_data

        (worker_id, pool_id, data, time_requested)
            VALUES
        (:worker_id, :pool_id, :data, UTC_TIMESTAMP())
    ');

    if (!$q->execute(array(
        ':worker_id' => $worker_id,
        ':pool_id'   => $pool_id,
        ':data'      => substr($response->result->data, 0, 152)))) {
        json_error('Database error on INSERT into work_data: ' . json_encode($q->errorInfo()), $json_id);
    }
}

# Check request

if (isset($_GET['lpurl']) && isset($_GET['pool'])) {
    $lpurl = $_GET['lpurl'];
    $pool = $_GET['pool'];

    set_time_limit(0);

    $q = $pdo->prepare('
        SELECT
            pool_username AS username,
            pool_password AS password

        FROM worker_pool

        WHERE pool_id = :pool_id
          AND worker_id = :worker_id
    ');

    $q->execute(array(
        ':pool_id'      => $pool,
        ':worker_id'    => $worker_id
    ));

    $row = $q->fetch(PDO::FETCH_ASSOC);

    if ($row === FALSE) {
        json_error('Unable to locate worker-pool association record.', 'json');
    }

    $q->closeCursor();

    $response = place_json_call(null, $lpurl, $row['username'], $row['password'], $headers);

    if (!is_object($response)) {
        json_error('Invalid response from long-poll request.', 'json');
    }

    process_work($pdo, $worker_id, $pool, $response, $response->id);

    json_success($response->result, $response->id);
} elseif ($_SERVER['REQUEST_METHOD'] != 'POST') {
    request_fail();
}

$body = @file_get_contents('php://input');

$json = json_decode($body);
if ($json == NULL) {
    request_fail();
}

if ($json->method != 'getwork') {
    json_error("Method unsupported.", $json->id);
}

$params = $json->params;

if (is_array($params) && count($params) == 1) {
    $data = substr($params[0], 0, 152);

    $q = $pdo->prepare('
        SELECT
            p.id AS pool_id,
            wp.pool_username AS username,
            wp.pool_password AS password,
            p.url AS url

        FROM
            work_data d,
            worker_pool wp,
            pool p

        WHERE d.data = :data
          AND d.worker_id = :worker_id

          AND d.pool_id = p.id

          AND wp.worker_id = :worker_id_two
          AND wp.pool_id = p.id
    ');

    $q->execute(array(
        ':worker_id'     => $worker_id,
        ':worker_id_two' => $worker_id,
        ':data'          => $data));

    $row = $q->fetch();
    $q->closeCursor();

    if ($row === FALSE) {
        json_error('Work not found in proxy database.', $json->id);
    }

    $result = place_json_call($json, $row['url'], $row['username'], $row['password'], $headers);

    $q = $pdo->prepare('
        INSERT INTO submitted_work

        (worker_id, pool_id, result, time)
            VALUES
        (:worker_id, :pool_id, :result, UTC_TIMESTAMP())
    ');

    $q->execute(array(
        ':worker_id'    => $worker_id,
        ':pool_id'      => $row['pool_id'],
        ':result'       => $result->result ? 1 : 0
    ));

    json_response($result);
}

# Work request.

$q = $pdo->prepare('
    SELECT
        wp.pool_username AS username,
        wp.pool_password AS password,
        p.id AS id,
        p.url AS url

    FROM worker_pool wp, pool p

    WHERE wp.worker_id = :worker
      AND wp.pool_id = p.id
      AND wp.enabled
      AND p.enabled

    ORDER BY wp.priority DESC
');

$q->execute(array(':worker' => $worker_id));

$rows = $q->fetchAll();

$q->closeCursor();

$request = new stdClass;
$request->params = array();
$request->method = "getwork";
$request->id = "json";

foreach ($rows as $row) {
    $response = place_json_call($request, $row['url'], $row['username'], $row['password'], $headers);

    if (is_object($response)) {
        foreach ($headers as $header) {
            $pieces = explode(': ', $header, 2);

            if (count($pieces) == 2 && $pieces[0] == 'X-Long-Polling') {
                $parts = parse_url($row['url']);

                $lpurl = sprintf('%s://%s%s%s',
                    $parts['scheme'],
                    $parts['host'],
                    (isset($parts['port']) ? (':' . $parts['port']) : ''),
                    $pieces[1]);

                header(sprintf('X-Long-Polling: %s?lpurl=%s&pool=%d',
                    $_SERVER['PHP_SELF'], urlencode($lpurl), $row['id']));
            }
        }

        process_work($pdo, $worker_id, $row['id'], $response, $response->id);

        json_success($response->result, $json->id);
    }
}

json_error("No enabled pools responded to the work request.", $json->id);

?>
