<?php

require_once(dirname(__FILE__) . '/../common.inc.php');
require_once(dirname(__FILE__) . '/../views/admin/dashboard.view.php');

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    auth_fail();
}

if (    $_SERVER['PHP_AUTH_USER'] != $BTC_PROXY['admin_user'] ||
        $_SERVER['PHP_AUTH_PW']   != $BTC_PROXY['admin_password']) {
    auth_fail();
}

$viewdata = array(
    'title'     => 'bitcoin-mining-proxy dashboard'
);

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
    SELECT DISTINCT
        w.name AS worker,
        p.name AS active_pool,
        sub.active_time AS active_time,
        swp.name AS last_accepted_pool,
        sw.submission_time AS last_accepted_submission

    FROM
        work_data wd,
        pool p,
        worker w,
        (
            SELECT
                worker_id,
                MAX(time_requested) AS active_time

            FROM work_data

            GROUP BY worker_id
        ) sub

    LEFT OUTER JOIN (
        SELECT
            worker_id,
            MAX(time) AS submission_time

        FROM submitted_work

        WHERE result = 1

        GROUP BY worker_id
    ) sw
        ON sw.worker_id = sub.worker_id

    LEFT OUTER JOIN submitted_work swd
       ON swd.worker_id = sw.worker_id
      AND swd.time = sw.submission_time

    LEFT OUTER JOIN pool swp
       ON swd.pool_id = swp.id

    WHERE wd.time_requested = sub.active_time
      AND wd.worker_id = sub.worker_id
      AND p.id = wd.pool_id

      AND w.id = sub.worker_id

    ORDER BY worker
');

$view = new AdminDashboardView();
$view->render($viewdata);

?>
