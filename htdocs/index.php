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
        INSERT IGNORE INTO work_data

        (worker_id, pool_id, data, time_requested)
            VALUES
        (:worker_id, :pool_id, :data, UTC_TIMESTAMP())
    ');

    $data = strtolower(substr($response->result->data, 0, 136));

    if (!$q->execute(array(
        ':worker_id' => $worker_id,
        ':pool_id'   => $pool_id,
        ':data'      => $data))) {
        json_error('Database error on INSERT into work_data: ' . json_encode($q->errorInfo()), $json_id);
    }
}

function set_headers($headers, $id, $url) {
    global $BTC_PROXY;

    $lpEnabled = isset($BTC_PROXY['long_polling']) ?
        $BTC_PROXY['long_polling'] : true;

    foreach ($headers as $fullHeader) {
        $pieces = explode(': ', $fullHeader, 2);

        if (count($pieces) != 2) {
            continue;
        }

        $header = strtolower($pieces[0]);
        $value = $pieces[1];

        if ($lpEnabled && $header == 'x-long-polling') {
            if (strpos($value, '://') !== FALSE) {
                $lpurl = $value;
            } else {
                $parts = parse_url($url);

                $lpurl = sprintf('%s://%s%s%s',
                    $parts['scheme'],
                    $parts['host'],
                    (isset($parts['port']) ? (':' . $parts['port']) : ''),
                    $value);
            }

            header(sprintf('X-Long-Polling: %s/%d/%s',
                $_SERVER['PHP_SELF'], $id, urlencode(base64_encode($lpurl))));
        } elseif ($header == 'x-roll-ntime') {
            header($fullHeader);
        }
    }
}

# Check request

$force_getwork = false;

if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '') {
    $lpparts = explode('/', $_SERVER['PATH_INFO']);
    if (count($lpparts) < 3) {
        json_error('Malformed long-polling request URL.', 'json');
    }

    $pool = $lpparts[1];
    $lpurl = base64_decode($lpparts[2]);

    set_time_limit(0);

    $q = $pdo->prepare('
        SELECT
            wp.pool_username AS username,
            wp.pool_password AS password,
            p.url AS url

        FROM worker_pool wp, pool p

        WHERE wp.pool_id = :pool_id
          AND wp.worker_id = :worker_id
          AND p.id = wp.pool_id
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

    $lpstart = time();

    $response = place_json_call(null, $lpurl, $row['username'], $row['password'], $headers, 3900);

    if (is_object($response) && is_object($response->result)) {
        set_headers($headers, $pool, $row['url']);

        process_work($pdo, $worker_id, $pool, $response, $response->id);

        json_success($response->result, $response->id);
    }

    # If the long-polling request failed, delay so that the request lasts 30
    # minutes overall, and then pretend that the user issued a getwork request.
    # Some miners will disable their long-polling mechanism permanently if one
    # long-polling request fails, so this will keep them happy.

    $duration = (30 * 60) - (time() - $lpstart);
    if ($duration > 0) {
        sleep($duration);
    }

    $force_getwork = true;

    set_time_limit(120);
} elseif ($_SERVER['REQUEST_METHOD'] != 'POST') {
    request_fail();
}

if ($force_getwork) {
    $json = new stdClass();
    $json->method = 'getwork';
    $json->params = array();
    $json->id = 'json';
} else {
    $body = @file_get_contents('php://input');

    $json = json_decode($body);
    if ($json == NULL) {
        request_fail();
    }
}

if ($json->method != 'getwork') {
    json_error("Method unsupported.", $json->id);
}

$params = $json->params;

if (is_array($params) && count($params) == 1) {
    $data = substr($params[0], 0, 136);

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
        ':data'          => strtolower($data)));

    $row = $q->fetch();
    $q->closeCursor();

    if ($row === FALSE) {
        json_error('Work not found in proxy database.', $json->id);
    }

    for ($i = 10; $i > 0; $i--) {
        $result = @place_json_call($json, $row['url'], $row['username'], $row['password'], $headers, 30);

        if (!$result) {
            sleep(1);
        } else {
            break;
        }
    }

    if (!$result) {
        json_error('Work submission request failed too many times.', $json->id);
    }

    set_headers($headers, $row['pool_id'], $row['url']);

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

// lets try harder to get work...
$tries = 0;
if(count($rows)) {
  while($tries < $BTC_PROXY['getwork_retries']) {
    foreach ($rows as $row) {
        $response = place_json_call($request, $row['url'], $row['username'], $row['password'], $headers);
    
        if (is_object($response) && is_object($response->result)) {
            set_headers($headers, $row['id'], $row['url']);
    
            process_work($pdo, $worker_id, $row['id'], $response, $response->id);
    
            json_success($response->result, $json->id);
        }
    }
    
    $tries++;
    sleep(1+rand(0,3));
  }
}

// and because of the way phoenix handles crap, for now atleast, it's better to just
// drop the call as it were.
//json_error("No enabled pools responded to the work request.", $json->id);

?>
