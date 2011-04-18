<?php

/*
 * ./htdocs/views/notfound.view.php
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

require_once(dirname(__FILE__) . '/master.view.php');

class NotFoundView
    extends MasterView
{
    function __construct($requested_type = FALSE)
    {
        parent::__construct(array(), $requested_type);
    }

    public function getTitle()
    {
        return '404 Not Found';
    }

    public function renderHtmlHeaders()
    {
        parent::renderHtmlHeaders();

        header('HTTP/1.0 404 Not Found');
    }

    public function renderBody()
    {
?>

<p style="text-align:center;">This is not the page you're looking for.</p>

<?php
    }
}

?>
