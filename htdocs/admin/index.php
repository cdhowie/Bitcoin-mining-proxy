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
        global $BTC_PROXY;
        $viewdata = array();

        $interval = $_REQUEST['interval'];
        if (strlen($interval) > 0) {
            $viewdata['interval_override'] = $interval;
        } else {
            $interval = $BTC_PROXY['average_interval'];
        }

        $pdo = db_connect();

        $viewdata['recent-submissions'] = db_query($pdo, "
            SELECT
                w.name AS worker,
                p.name AS pool,
                sw.result AS result,
                sw.time AS time

            FROM (
                SELECT pool_id, worker_id, result, time

                FROM submitted_work

                ORDER BY id DESC

                LIMIT {$BTC_PROXY['recent_work_num']}
            ) sw

            INNER JOIN pool p
            ON p.id = sw.pool_id

            INNER JOIN worker w
            ON w.id = sw.worker_id
            
            ORDER BY sw.time DESC
        ");

        $viewdata['recent-failed-submissions'] = db_query($pdo, " 
            SELECT
                w.name AS worker,
                p.name AS pool,
                sw.result AS result,
                sw.time AS time

            FROM (
                SELECT pool_id, worker_id, result, time

                FROM submitted_work

                WHERE result = 0

                ORDER BY id DESC

                LIMIT {$BTC_PROXY['recent_work_num']}
            ) sw

            INNER JOIN pool p
            ON p.id = sw.pool_id

            INNER JOIN worker w
            ON w.id = sw.worker_id
            
            ORDER BY sw.time DESC
        ");

        $viewdata['worker-status'] = db_query($pdo, '
            SELECT
                w.name AS worker,
                w.id AS worker_id,

                worked.pool_name AS active_pool,
                worked.latest AS active_time,

                submitted.pool_name AS last_accepted_pool,
                submitted.latest AS last_accepted_time,

                sli.shares_last_interval AS shares_last_interval,
                sli.accepted_last_interval,
                sli.rejected_last_interval,
                sli.shares_last_interval * 4294967296 / :average_interval / 1000000 as mhash

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

                LEFT OUTER JOIN pool p
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

                LEFT OUTER JOIN pool p
                    ON p.id = sw.pool_id

                GROUP BY sw.worker_id
            ) submitted

            ON submitted.worker_id = w.id

            LEFT OUTER JOIN (
                SELECT
                    worker_id,
                    COUNT(result) AS shares_last_interval,
                    SUM(IF(result = 1, 1, 0)) accepted_last_interval,
                    SUM(IF(result = 0, 1, 0)) rejected_last_interval
                FROM
                    submitted_work sw
                WHERE
                    time >= UTC_TIMESTAMP() - INTERVAL :average_interval_two SECOND
                GROUP BY
                    sw.worker_id
            ) sli
            ON sli.worker_id = w.id

            ORDER BY w.name
        ', array(
            ':average_interval'     => $interval,
            ':average_interval_two' => $interval
        ));
        
        $viewdata['pool-status'] = db_query($pdo, '
        
            SELECT
                p.id pool_id
              , p.name pool
              
              , w.name worker
              
              , IFNULL(sw.accepted, 0) accepted
              , IFNULL(sw.rejected, 0) rejected
              , IFNULL(sw.accepted, 0) + IFNULL(sw.rejected, 0) total
              
              , IFNULL(wd.records, 0) getworks
              , wd.last_request
              
            FROM
                pool p
                
                INNER JOIN 
                
                (
                    SELECT 
                        IFNULL(wd.pool_id, wd2.pool_id) pool_id
                      , worker_id
                      , records
                      , last_request
                    FROM 
                        (
                            SELECT
                                pool_id
                              , COUNT(*) records
                            FROM 
                                work_data
                            WHERE
                                time_requested >= UTC_TIMESTAMP() - INTERVAL :average_interval_1 SECOND
                            GROUP BY 
                                pool_id
                        ) wd
                        RIGHT JOIN
                        (
                            SELECT 
                                wd4.pool_id
                              , wd3.worker_id
                              , wd4.last_request
                            FROM 
                                work_data wd3
                                INNER JOIN
                                (
                                    SELECT
                                        pool_id
                                      , MAX(time_requested) AS last_request
                                    FROM
                                        work_data
                                    GROUP BY
                                        pool_id
                                ) wd4
                                ON wd3.time_requested = wd4.last_request
                            GROUP BY
                                wd4.pool_id
                        ) wd2
                        ON wd.pool_id = wd2.pool_id
                ) wd
                
                ON p.id = wd.pool_id
                
                LEFT OUTER JOIN
                (
                    SELECT 
                        pool_id
                      , SUM(IF(result = 1, 1, 0)) accepted
                      , SUM(IF(result = 1, 0, 1)) rejected
                    FROM 
                        submitted_work
                    WHERE 
                        time >= UTC_TIMESTAMP() - INTERVAL :average_interval_2 SECOND
                    GROUP BY 
                        pool_id
                ) sw
                
                ON wd.pool_id = sw.pool_id
                
                INNER JOIN worker w
                ON wd.worker_id = w.id
                
            WHERE
                p.enabled = 1
                
                
        ', array(
            ':average_interval_1'     => $interval,
            ':average_interval_2'     => $interval
        ));
        
        $version = db_query($pdo, "
            SELECT value FROM settings

            WHERE `key` = 'version'
        ");

        if ($version === false || count($version) == 0 || $version[0]['value'] != DB_SCHEMA_VERSION) {
            $viewdata['old-schema'] = true;
        }

        if (ini_get('allow_url_fopen') != 1) {
            $viewdata['url-fopen-disabled'] = true;
        }

        return new AdminDashboardView($viewdata);
    }
}

MvcEngine::run(new AdminDashboardController());

?>
