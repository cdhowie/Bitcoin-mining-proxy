<?php

require_once(dirname(__FILE__) . '/../common.inc.php');
require_once(dirname(__FILE__) . '/../admin/controller.inc.php');
require_once(dirname(__FILE__) . '/../models/pool.inc.php');
require_once(dirname(__FILE__) . '/../views/admin/pool.view.php');

class AdminPoolController extends AdminController
{
    public function indexGetView()
    {
        $pdo = db_connect();

        $rows = db_query($pdo, '
            SELECT id, name, url, enabled

            FROM pool

            ORDER BY name
        ');

        foreach ($rows as $row) {
            $viewdata['pools'][] = new PoolModel($row);
        }

        return new AdminPoolsView($viewdata);
    }

    public function toggleEnabledDefaultView(PoolModel $pool)
    {
        if (!$pool->toggleEnabled()) {
            $_SERVER['tempdata']['errors'][] = 'Unable to toggle that pool.';
        }

        return new RedirectView('/admin/pool.php');
    }
}

MvcEngine::run(new AdminPoolController());

?>
