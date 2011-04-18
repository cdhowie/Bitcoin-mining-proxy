<?php

/*
 * ./htdocs/views/admin/pool.view.php
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

abstract class PoolView
    extends MasterView
{
    protected function getMenuId()
    {
        return "pools";
    }
}

class AdminPoolsView
    extends PoolView
    implements IJsonView
{
    protected function getTitle()
    {
        return "Pool management";
    }

    protected function renderBody()
    {
?>

<div id="pools">

<table class="data centered">
    <tr>
        <th>Name</th>
        <th>Enabled</th>
        <th>Url</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($this->viewdata['pools'] as $pool) { ?>
    <tr <?php if (!$pool->enabled) { ?>class="disabled"<?php } ?>>
        <td><?php echo_html($pool->name) ?></td>
        <td class="enabled-column">
            <form action="<?php echo_html(make_url('/admin/pool.php')) ?>" method="post">
                <fieldset>
                    <input type="hidden" name="id" value="<?php echo_html($pool->id) ?>" />
                    <?php
                        if ($pool->enabled) {
                            $this->renderImageButton('toggleEnabled', 'enabled', 'Yes', 'Toggle');
                        } else {
                            $this->renderImageButton('toggleEnabled', 'disabled', 'No', 'Toggle');
                        }
                    ?>
                </fieldset>
            </form>
        </td>
        <td><?php echo_html($pool->url) ?></td>
        <td>
            <form action="<?php echo_html(make_url('/admin/pool.php')) ?>" method="get">
                <fieldset>
                    <input type="hidden" name="id" value="<?php echo_html($pool->id) ?>" />
                    <?php
                        $this->renderImageButton('edit', 'edit-pool', 'Edit pool');
                        if (!$pool->worker_count) {
                            $this->renderImageButton('delete', 'delete-pool', 'Delete pool');
                        }
                    ?>
                </fieldset>
            </form>
        </td>
    </tr>
    <?php } ?>
    <tr>
        <td colspan="3">&nbsp;</td>
        <td>
            <form action="<?php echo_html(make_url('/admin/pool.php')) ?>">
                <fieldset>
                    <?php $this->renderImageButton('new', 'new-pool', 'New pool') ?>
                </fieldset>
            </form>
        </td>
    </tr>
</table>

</div>

<?php
    }
}

class AdminEditPoolView
    extends PoolView
{
    protected function getTitle()
    {
        $pool = $this->viewdata['pool'];

        return $pool->id ? "Edit pool - {$pool->name}" : 'New pool';
    }

    protected function renderBody()
    {
        $pool = $this->viewdata['pool'];

?>

<div id="edit-pool">

<form action="<?php echo_html(make_url('/admin/pool.php')) ?>" method="post">
<fieldset>
    <input type="hidden" name="action" value="edit" />
    <?php if ($pool->id) { ?>
    <input type="hidden" name="id" value="<?php echo_html($pool->id) ?>" />
    <?php } ?>
</fieldset>

<table class="entry centered">
    <tr>
        <th>Name:</th>
        <td><input type="text" name="name" size="50" value="<?php echo_html($pool->name) ?>" /></td>
    </tr>
    <tr>
        <th>Enabled:</th>
        <td><input type="checkbox" name="enabled" value="1" <?php if ($pool->enabled) { ?>checked="checked"<?php } ?> /></td>
    </tr>
    <tr>
        <th>URL:</th>
        <td><input type="text" name="url" size="50" value="<?php echo_html($pool->url) ?>" /></td>
    </tr>
    <tr class="submit">
        <td>&nbsp;</td>
        <td><input type="submit" value="<?php echo_html($pool->id ? 'Save changes' : 'Create pool') ?>" /></td>
    </tr>
</table>

</form>

</div>

<?php
    }
}

?>
