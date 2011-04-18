<?php

/*
 * ./htdocs/models/worker.inc.php
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

class WorkerModel
{
    public $id;

    public $name;
    public $password;

    function __construct($form = FALSE)
    {
        if ($form !== FALSE) {
            $this->id = $form['id'];

            $this->name = $form['name'];
            $this->password = $form['password'];
        }
    }

    function validate()
    {
        $errors = array();

        $this->name = trim($this->name);
        $this->password = trim($this->password);

        if ($this->name == '') {
            $errors[] = 'Worker name is required.';
        } elseif (strpos($this->name, ':') !== FALSE) {
            $errors[] = 'Worker name may not contain a colon.';
        }

        if ($this->password == '') {
            $errors[] = 'Worker password is required.';
        }

        return count($errors) ? $errors : TRUE;
    }
}

?>
