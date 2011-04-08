<?php

require_once(dirname(__FILE__) . '/../common.inc.php');
require_once(dirname(__FILE__) . '/../admin/controller.inc.php');

class NotFoundController extends AdminController
{
    # No methods means NotFoundView gets returned all the time.
}

MvcEngine::run(new NotFoundController());

?>
