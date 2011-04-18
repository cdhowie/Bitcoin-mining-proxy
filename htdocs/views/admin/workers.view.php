<?php

/*
 * ./htdocs/views/admin/workers.view.php
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

require_once(dirname(__FILE__) . '/../master.view.php');

abstract class WorkersView
    extends MasterView
{
    protected function getMenuId()
    {
        return "workers";
    }
}

class AdminWorkersView
    extends WorkersView
    implements IJsonView
{
    protected function getTitle()
    {
        return "Worker management";
    }

    protected function renderBody()
    {
?>

<div id="workers">

<table class="data centered">
    <tr>
        <th>Name</th>
        <th>Password</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($this->viewdata['workers'] as $row) { ?>
    <tr>
        <td><?php echo_html($row['name'])     ?></td>
        <td><?php echo_html($row['password']) ?></td>
        <td>
            <form action="<?php echo_html(make_url('/admin/worker-pool.php')) ?>">
                <fieldset>
                    <input type="hidden" name="id" value="<?php echo_html($row['id']) ?>" />
                    <?php $this->renderImageButton('index', 'manage-pools', 'Manage pools') ?>
                </fieldset>
            </form>
            <form action="<?php echo_html(make_url('/admin/workers.php')) ?>" method="get">
                <fieldset>
                    <input type="hidden" name="id" value="<?php echo_html($row['id']) ?>" />
                    <?php $this->renderImageButton('edit', 'edit-worker', 'Edit worker') ?>
                </fieldset>
            </form>
        <?php
            if ($row['pools'] == 0) {
?>
            <form action="<?php echo_html(make_url('/admin/workers.php')) ?>" method="post">
                <fieldset>
                    <input type="hidden" name="id" value="<?php echo_html($row['id']) ?>" />
                    <?php $this->renderImageButton('delete', 'delete-worker', 'Delete worker') ?>
                </fieldset>
            </form>
<?php
            }
        ?></td>
    </tr>
    <?php } ?>
    <tr>
        <td colspan="2">&nbsp;</td>
        <td>
            <form action="<?php echo_html(make_url('/admin/workers.php')) ?>">
                <fieldset>
                    <?php $this->renderImageButton('new', 'new-worker', 'New worker') ?>
                </fieldset>
            </form>
        </td>
    </tr>
</table>

</div>

<?php
    }
}

class AdminWorkerNewEditView
    extends WorkersView
{
    protected function getTitle()
    {
        return $this->viewdata['worker']->id ? 'Edit worker' : 'New worker';
    }

    protected function getDivId()
    {
        return $this->viewdata['worker']->id ? 'edit-worker' : 'new-worker';
    }

    protected function getAction()
    {
        return $this->viewdata['worker']->id ? 'edit' : 'new';
    }

    protected function getSubmitValue()
    {
        return $this->viewdata['worker']->id ? 'Save changes' : 'Create worker';
    }

    protected function renderBody()
    {
?>

<div id="<?php echo $this->getDivId() ?>">

<form action="<?php echo_html(make_url('/admin/workers.php')) ?>" method="post">

<fieldset>
<input type="hidden" name="action" value="<?php echo $this->getAction() ?>" />
<?php if ($this->viewdata['worker']->id) { ?>
<input type="hidden" name="id" value="<?php echo_html($this->viewdata['worker']->id) ?>" />
<?php } ?>
</fieldset>

<table class="entry centered">
    <tr>
        <th><label for="name">Name:</label></th>
        <td><input name="name" id="name" size="25" value="<?php echo_html($this->viewdata['worker']->name) ?>" /></td>
    </tr>
    <tr>
        <th><label for="password">Password:</label></th>
        <td><input name="password" id="password" size="25" value="<?php echo_html($this->viewdata['worker']->password) ?>" /></td>
    </tr>
    <tr class="submit">
        <td>&nbsp;</td>
        <td><input type="submit" value="<?php echo $this->getSubmitValue() ?>" /></td>
    </tr>
</table>

</form>

</div>

<?php
    }
}

?>
