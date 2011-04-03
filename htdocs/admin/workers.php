<?php

require_once(dirname(__FILE__) . '/../common.inc.php');
require_once(dirname(__FILE__) . '/../admin/controller.inc.php');
require_once(dirname(__FILE__) . '/../views/admin/workers.view.php');

class AdminWorkersController extends AdminController
{
    public function indexView()
    {
        $viewdata = array(
            'title'     => 'bitcoin-mining-proxy worker management'
        );

        $pdo = db_connect();

        $viewdata['workers'] = db_query($pdo, '
            SELECT name, password
            
            FROM worker

            ORDER BY name
        ');

        return new AdminWorkersView($viewdata);
    }
}

MvcEngine::run(new AdminWorkersController());

?>
