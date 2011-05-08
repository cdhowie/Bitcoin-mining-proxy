<?php

/*
 * ./htdocs/models/pool.inc.php
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

class PoolModel
{
    public $id;

    public $name;
    public $url;
    public $enabled;

    public $worker_count;

    function __construct($form = FALSE)
    {
        if ($form !== FALSE) {
            $this->id = $form['id'];

            $this->name = $form['name'];
            $this->url = $form['url'];
            $this->enabled = $form['enabled'];

            $this->worker_count = $form['worker_count'];

            $this->canonize();
        }
    }

    public function canonize()
    {
        $this->id = (int)$this->id;

        $this->name = trim($this->name);
        $this->url = trim($this->url);
        $this->enabled = $this->enabled ? 1 : 0;

        $this->worker_count = (int)$this->worker_count;
    }

    public function toggleEnabled()
    {
        $this->canonize();

        if ($this->id == 0) {
            return false;
        }
        
        $pdo = db_connect();

        $q = $pdo->prepare('
            UPDATE pool

            SET enabled = NOT enabled

            WHERE id = :pool_id
        ');

        if ($q->execute(array(':pool_id' => $this->id))) {
            $this->enabled = $this->enabled ? 0 : 1;
            return TRUE;
        }

        return FALSE;
    }

    public function refresh()
    {
        $id = (int)$this->id;

        if ($id == 0) {
            return FALSE;
        }

        $pdo = db_connect();

        $q = $pdo->prepare('
            SELECT
                p.name AS name,
                p.enabled AS enabled,
                p.url AS url,
                COUNT(wp.worker_id) AS worker_count

            FROM pool p

            LEFT OUTER JOIN worker_pool wp
            ON p.id = :pool_id

            WHERE p.id = :pool_id_two
        ');

        if (!$q->execute(array(
                ':pool_id'     => $id,
                ':pool_id_two' => $id))) {
            return FALSE;
        }

        $row = $q->fetch();
        $q->closeCursor();

        if ($row === FALSE) {
            return FALSE;
        }

        $this->id = $id;

        $this->name = $row['name'];
        $this->url = $row['url'];
        $this->enabled = $row['enabled'];

        $this->worker_count = $row['worker_count'];

        return TRUE;
    }

    public function save()
    {
        if (!$this->validate()) {
            return FALSE;
        }

        $pdo = db_connect();

        $params = array(
            ':name'     => $this->name,
            ':enabled'  => $this->enabled,
            ':url'      => $this->url
        );

        if ($this->id) {
            $q = $pdo->prepare('
                UPDATE pool

                SET name = :name,
                    url = :url,
                    enabled = :enabled

                WHERE id = :id
            ');

            $params[':id'] = $this->id;
        } else {
            $q = $pdo->prepare('
                INSERT INTO pool

                (name, url, enabled)
                    VALUES
                (:name, :url, :enabled)
            ');
        }

        if (!$q->execute($params)) {
            return FALSE;
        }

        return TRUE;
    }

    public function validate()
    {
        $errors = array();

        $this->canonize();

        if ($this->name == '') {
            $errors[] = 'Pool name is required.';
        }

        if ($this->url == '') {
            $errors[] = 'Pool URL is required.';
        }

        return count($errors) ? $errors : TRUE;
    }
}

?>
