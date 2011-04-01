<?php

require_once('common.inc.php');



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

?>
