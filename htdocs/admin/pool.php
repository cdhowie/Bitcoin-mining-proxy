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
            SELECT
                p.id AS id,
                p.name AS name,
                p.url AS url,
                p.enabled AS enabled,
                COUNT(wp.worker_id) AS worker_count

            FROM pool p

            LEFT OUTER JOIN worker_pool wp
            ON p.id = wp.pool_id

            GROUP BY p.id

            ORDER BY p.name
        ');

        foreach ($rows as $row) {
            $viewdata['pools'][] = new PoolModel($row);
        }

        return new AdminPoolsView($viewdata);
    }

    public function toggleEnabledDefaultView(PoolModel $pool)
    {
        if (!$pool->toggleEnabled()) {
            $_SESSION['tempdata']['errors'][] = 'Unable to toggle that pool.';
        }

        return new RedirectView('/admin/pool.php');
    }

    public function editGetView(PoolModel $pool)
    {
        if (!$pool->refresh()) {
            $_SESSION['tempdata']['errors'][] = 'Pool not found.';
            return new RedirectView('/admin/pool.php');
        }

        return new AdminEditPoolView(array('pool' => $pool));
    }

    public function newGetView()
    {
        return new AdminEditPoolView(array('pool' => new PoolModel()));
    }

    public function editPostView(PoolModel $pool)
    {
        $errors = $pool->validate();

        if ($errors !== TRUE) {
            $_SESSION['tempdata']['errors'] =
                array_merge((array)$_SESSION['tempdata']['errors'], $errors);

            return new AdminEditPoolView(array('pool' => $pool));
        }

        if (!$pool->save()) {
            $_SESSION['tempdata']['errors'] = 'Cannot save pool.  Another pool with the same name may already exist.';

            return new AdminEditPoolView(array('pool' => $pool));
        }

        return new RedirectView('/admin/pool.php');
    }

    public function deleteDefaultView(PoolModel $pool)
    {
        if ($pool->id) {
            $pdo = db_connect();

            $q = $pdo->prepare('
                DELETE p
                FROM pool p

                LEFT OUTER JOIN (
                    SELECT
                        wp.pool_id AS pool_id,
                        COUNT(wp.worker_id) AS workers

                    FROM worker_pool wp

                    GROUP BY wp.pool_id
                ) wp

                ON wp.pool_id = :pool_id

                WHERE p.id = :pool_id
                  AND (wp.workers = 0 OR wp.workers IS NULL)
            ');

            $q->execute(array(':pool_id' => $pool->id));

            if (!$q->rowCount()) {
                $_SESSION['tempdata']['errors'][] = 'Pool still has workers; cannot delete.';
            }
        }

        return new RedirectView('/admin/pool.php');
    }
}

MvcEngine::run(new AdminPoolController());

?>
