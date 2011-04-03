<?php

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



# Check request

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
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

          AND wp.worker_id = :worker_id
          AND wp.pool_id = p.id
    ');

    $q->execute(array(
        ':worker_id'    => $worker_id,
        ':data'         => $data));

    $row = $q->fetch();
    $q->closeCursor();

    if ($row === FALSE) {
        json_error('Work not found in proxy database.', $json->id);
    }

    $result = place_json_call($json, $row['url'], $row['username'], $row['password']);

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
    $response = place_json_call($request, $row['url'], $row['username'], $row['password']);

    if (is_object($response)) {
        $q = $pdo->prepare('
            INSERT INTO work_data

            (worker_id, pool_id, data, time_requested)
                VALUES
            (:worker_id, :pool_id, :data, UTC_TIMESTAMP())
        ');

        $q->execute(array(
            'worker_id' => $worker_id,
            'pool_id'   => $row['id'],
            'data'      => substr($response->result->data, 0, 152)));

        json_success($response->result, $response->id);
    }
}

json_error("No enabled pools responded to the work request.", $json->id);

?>
