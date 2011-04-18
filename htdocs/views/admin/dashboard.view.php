<?php

/*
 * ./htdocs/views/admin/dashboard.view.php
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

class AdminDashboardView
    extends MasterView
    implements IJsonView
{
    protected function getTitle()
    {
        return "Dashboard";
    }

    protected function getMenuId()
    {
        return "dashboard";
    }

    private function renderWorkTable($rows)
    {
?>
    <tr>
        <th>Worker</th>
        <th>Pool</th>
        <th>Result</th>
        <th>Time</th>
    </tr>
    <?php foreach ($rows as $row) { ?>
    <tr class="<?php echo $row['result'] ? 'accepted' : 'rejected' ?>">
        <td><?php echo htmlspecialchars($row['worker']) ?></td>
        <td><?php echo htmlspecialchars($row['pool'])   ?></td>
        <td><?php echo $row['result'] ? 'Accepted' : 'Rejected' ?></td>
        <td><?php echo htmlspecialchars($row['time'])   ?></td>
    </tr>
    <?php } ?>
<?php
    }

    protected function renderBody()
    {
?>

<div id="dashboard">

<div id="recent-submissions">

<h2>Recent work submissions</h2>

<table class="data"><?php $this->renderWorkTable($this->viewdata['recent-submissions']) ?></table>

</div>

<div id="recent-failed-submissions">

<h2>Recent failed work submissions</h2>

<table class="data"><?php $this->renderWorkTable($this->viewdata['recent-failed-submissions']) ?></table>

</div>

<br />

<div id="worker-status">

<h2>Worker status</h2>

<table class="data">
    <tr>
        <th>Worker</th>
        <th>Last work request</th>
        <th>Last accepted submission</th>
    </tr>
    <?php foreach ($this->viewdata['worker-status'] as $row) { ?>
    <tr>
        <td>
            <form action="<?php echo_html(make_url('/admin/worker-pool.php')) ?>">
                <fieldset>
                    <input type="hidden" name="id" value="<?php echo_html($row['worker_id']) ?>" />
                    <?php $this->renderImageButton('index', 'manage-pools', 'Manage pools') ?>
                </fieldset>
            </form>
            <?php echo htmlspecialchars($row['worker']) ?>
        </td>
        <td><?php
            if (isset($row['active_pool'])) {
                echo_html("At {$row['active_time']} from {$row['active_pool']}");
            } else {
                echo "Never";
            }
        ?></td>
        <td><?php
            if (isset($row['last_accepted_pool'])) {
                echo_html("At {$row['last_accepted_time']} to {$row['last_accepted_pool']}");
            } else {
                echo "Never";
            }
        ?></td>
    </tr>
    <?php } ?>
</table>

</div>

</div>

<?php
    }
}

?>
