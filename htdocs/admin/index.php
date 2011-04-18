<?php

/*
 * ./htdocs/admin/index.php
 *
 * Copyright (C) 2011  Chris Howie <me@chrishowie.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
                    wd.worker_id AS worker_id,
                    wd.time_requested AS latest,
                    p.name AS pool_name

                FROM work_data wd

                INNER JOIN (
                    SELECT
                        worker_id,
                        MAX(time_requested) AS latest

                    FROM work_data

                    GROUP BY worker_id
                ) wd2
                    ON wd.worker_id = wd2.worker_id
                   AND wd.time_requested = wd2.latest

                INNER JOIN pool p
                    ON p.id = wd.pool_id

                GROUP BY wd.worker_id
            ) worked

            ON worked.worker_id = w.id

            LEFT OUTER JOIN (
                SELECT
                    sw.worker_id AS worker_id,
                    sw.time AS latest,
                    p.name AS pool_name

                FROM submitted_work sw

                INNER JOIN (
                    SELECT
                        worker_id,
                        MAX(time) AS latest

                    FROM submitted_work

                    WHERE result = 1

                    GROUP BY worker_id
                ) sw2
                    ON sw.worker_id = sw2.worker_id
                   AND sw.result = 1
                   AND sw.time = sw2.latest

                INNER JOIN pool p
                    ON p.id = sw.pool_id

                GROUP BY sw.worker_id
            ) submitted

            ON submitted.worker_id = w.id

            ORDER BY w.name
        ');

        return new AdminDashboardView($viewdata);
    }
}

MvcEngine::run(new AdminDashboardController());

?>
