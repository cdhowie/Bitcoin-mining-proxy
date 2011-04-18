<?php

/*
 * ./htdocs/admin/worker-pool.php
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
require_once(dirname(__FILE__) . '/../models/worker-pool.inc.php');
require_once(dirname(__FILE__) . '/../views/admin/worker-pool.view.php');

class AdminWorkerPoolController extends AdminController
{
    public function indexGetView($request)
    {
        $id = (int)$request['id'];

        if ($id == 0) {
            return new RedirectView('/admin/workers.php');
        }

        $pdo = db_connect();

        $name = db_query($pdo, '
            SELECT name

            FROM worker

            WHERE id = :worker_id
        ', array(':worker_id' => $id));

        if (count($name) == 0) {
            return new RedirectView('/admin/workers.php');
        }

        $name = $name[0]['name'];

        $viewdata = array(
            'worker-id'     => $id,
            'worker-name'   => $name
        );

        $viewdata['worker-pools'] = db_query($pdo, '
            SELECT
                wp.pool_username AS username,
                wp.pool_password AS password,
                wp.priority AS priority,
                wp.enabled AS enabled,

                p.id AS `pool-id`,
                p.name AS pool,
                p.enabled AS `pool-enabled`

            FROM pool p

            LEFT OUTER JOIN worker_pool wp
                ON p.id = wp.pool_id
               AND wp.worker_id = :worker_id

            ORDER BY priority DESC 
        ', array(':worker_id' => $id));

        return new AdminWorkerPoolView($viewdata);
    }

    public function setEnabledPostView($request)
    {
        $id = (int)$request['id'];

        if ($id == 0) {
            return new RedirectView('/admin/workers.php');
        }

        $enabled = (int)$request['enabled'];
        $pool = (int)$request['pool-id'];

        $pdo = db_connect();

        $q = $pdo->prepare('
            UPDATE worker_pool

            SET enabled = :enabled

            WHERE worker_id = :worker_id
              AND pool_id = :pool_id
        ');

        $q->execute(array(
            ':enabled'      => $enabled,
            ':pool_id'      => $pool,
            ':worker_id'    => $id
        ));

        if (!$q->rowCount()) {
            $_SESSION['tempdata']['errors'][] =
                sprintf('Pool not found or not affected.');
        }

        return new RedirectView("/admin/worker-pool.php?id=$id");
    }

    public function editGetView(WorkerPoolModel $model)
    {
        if ($model->worker_id == 0) {
            return new RedirectView('/admin/workers.php');
        }

        if ($model->pool_id == 0) {
            return new RedirectView("/admin/worker-pool.php?id={$model->worker_id}");
        }

        $model->refresh();

        return new WorkerPoolEditView(array('worker-pool' => $model));
    }

    public function editPostView(WorkerPoolModel $model)
    {
        if ($model->worker_id == 0) {
            return new RedirectView('/admin/workers.php');
        }

        if ($model->pool_id == 0) {
            return new RedirectView("/admin/worker-pool.php?id={$model->worker_id}");
        }

        $errors = $model->validate();
        if ($errors !== TRUE) {
            $_SESSION['tempdata']['errors'] = array_merge(
                (array)$_SESSION['tempdata']['errors'], $errors);

            return new WorkerPoolEditView(array('worker-pool' => $model));
        }

        if (!$model->save()) {
            $_SESSION['tempdata']['errors'][] = 'Unable to save worker pool data.';

            return new WorkerPoolEditView(array('worker-pool' => $model));
        }

        return new RedirectView("/admin/worker-pool.php?id={$model->worker_id}");
    }

    public function deleteDefaultView(WorkerPoolModel $model)
    {
        if ($model->pool_id != 0 && $model->worker_id != 0) {
            $pdo = db_connect();

            $q = $pdo->prepare('
                DELETE FROM worker_pool

                WHERE worker_id = :worker_id
                  AND pool_id = :pool_id
            ');

            $q->execute(array(
                ':worker_id'    => $model->worker_id,
                ':pool_id'      => $model->pool_id
            ));

            if (!$q->rowCount()) {
                $_SESSION['tempdata']['errors'][] = 'Unable to delete worker pool assignment.';
            }
        }

        if ($model->worker_id != 0) {
            return new RedirectView("/admin/worker-pool.php?id={$model->worker_id}");
        }

        return new RedirectView('/admin/workers.php');
    }
}

MvcEngine::run(new AdminWorkerPoolController());

?>
