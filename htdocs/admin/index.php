<?php

require_once(dirname(__FILE__) . '/../common.inc.php');
require_once(dirname(__FILE__) . '/../admin/controller.inc.php');
require_once(dirname(__FILE__) . '/../views/admin/dashboard.view.php');

class AdminDashboardController extends AdminController
{
    public function indexDefaultView()
    {
        $viewdata = array();

        $pdo = db_connect();

        $viewdata['recent-submissions'] = db_query($pdo, '
            SELECT
                w.name AS worker,
                p.name AS pool,
                sw.result AS result,
                sw.time AS time

            FROM
                pool p,
                worker w,
                submitted_work sw

            WHERE sw.worker_id = w.id
              AND sw.pool_id = p.id

            ORDER BY sw.id DESC

            LIMIT 10
        ');

        $viewdata['recent-failed-submissions'] = db_query($pdo, '
            SELECT
                w.name AS worker,
                p.name AS pool,
                sw.result AS result,
                sw.time AS time

            FROM
                pool p,
                worker w,
                submitted_work sw

            WHERE sw.worker_id = w.id
              AND sw.pool_id = p.id
              AND sw.result = 0

            ORDER BY sw.id DESC

            LIMIT 10
        ');

        $viewdata['worker-status'] = db_query($pdo, '
            SELECT
                w.name AS worker,
                w.id AS worker_id,

                worked.pool_name AS active_pool,
                worked.latest AS active_time,

                submitted.pool_name AS last_accepted_pool,
                submitted.latest AS last_accepted_time

            FROM worker w

            LEFT OUTER JOIN (
                SELECT
                    t.worker_id AS worker_id,
                    t.time_requested AS latest,
                    p.name AS pool_name

                FROM (
                    SELECT worker_id, pool_id, time_requested

                    FROM work_data

                    ORDER BY time_requested DESC
                ) t

                INNER JOIN pool p
                    ON p.id = t.pool_id

                GROUP BY worker_id
            ) worked

            ON worked.worker_id = w.id

            LEFT OUTER JOIN (
                SELECT
                    t.worker_id AS worker_id,
                    t.time AS latest,
                    p.name AS pool_name

                FROM (
                    SELECT worker_id, pool_id, time

                    FROM submitted_work

                    WHERE result = 1

                    ORDER BY id DESC
                ) t

                INNER JOIN pool p
                    ON p.id = t.pool_id

                GROUP BY worker_id
            ) submitted

            ON submitted.worker_id = w.id

            ORDER BY w.name
        ');

        return new AdminDashboardView($viewdata);
    }
}

MvcEngine::run(new AdminDashboardController());

?>
