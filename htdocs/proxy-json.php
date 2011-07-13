<?php

require_once(dirname(__FILE__) . '/config.inc.php');

// Set your return content type
header('Content-type: application/json');

global $BALANCE_JSON;

// Website url to open
$daurl = $BALANCE_JSON[$_REQUEST['pool']]['url'];

if ($daurl) {
  // Get that website's content
  $handle = fopen($daurl, "r");

  // If there is something, read and return
  if ($handle) {
      while (!feof($handle)) {
        $buffer = fgets($handle, 64);
          echo $buffer;
      }
      fclose($handle);
  }
} else {
  $execstat = $BALANCE_JSON[$_REQUEST['pool']]['exec'];
  echo exec($execstat);
}

?>
