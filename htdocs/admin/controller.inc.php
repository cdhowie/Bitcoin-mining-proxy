<?php

require_once(dirname(__FILE__) . '/../common.inc.php');
require_once(dirname(__FILE__) . '/../mvc.inc.php');

abstract class AdminController extends Controller
{
    public function preExecute()
    {
        do_admin_auth();

        parent::preExecute();
    }
}

?>
