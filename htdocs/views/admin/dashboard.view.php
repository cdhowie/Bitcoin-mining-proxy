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

require_once(dirname(__FILE__) . '/../../config.inc.php');
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

    private function renderWorkTable($rows, $hideResultColumn = FALSE)
    {
?>
    <tr>
        <th>Worker</th>
        <th>Pool</th>
        <?php if (!$hideResultColumn) { ?><th>Result</th><?php } ?>
        <th>Time</th>
    </tr>
    <?php foreach ($rows as $row) { ?>
    <tr class="<?php echo $row['result'] ? 'accepted' : 'rejected' ?>">
        <td><?php echo htmlspecialchars($row['worker']) ?></td>
        <td><?php echo htmlspecialchars($row['pool']) ?></td>
        <?php if (!$hideResultColumn) { ?><td><?php echo $row['result'] ? 'Accepted' :'Rejected' ?></td><?php } ?>
        <td><?php echo htmlspecialchars(format_date($row['time'])) ?></td>
    </tr>
    <?php } ?>
<?php
    }

    protected function renderBody()
    {
        global $BTC_PROXY;
?>

<div id="dashboard">

<?php if ($this->viewdata['old-schema']) { ?>
<div id="old-schema"><span>Your database schema is out of date.  Please run the schema migration script (check the readme for instructions).  Until you migrate your database schema, you may notice errors or poor performance.</span></div>
<?php } ?>

<div id="recent-submissions">

<h2>Recent work submissions</h2>

<table class="data"><?php $this->renderWorkTable($this->viewdata['recent-submissions']) ?></table>

</div>

<div id="recent-failed-submissions">

<h2>Recent failed work submissions</h2>

<table class="data"><?php $this->renderWorkTable($this->viewdata['recent-failed-submissions'], TRUE) ?></table>

</div>

<br />

<div id="worker-status">

<h2>Worker status</h2>

<table class="data">
    <tr>
        <th>Worker</th>
        <th>Last work request</th>
        <th>Last accepted submission</th>
        <th>Shares<sup>*</sup></th>
        <th>Rejected<sup>*</sup></th>
        <th>Hashing speed<sup>*</sup></th>
    </tr>
    <?php foreach ($this->viewdata['worker-status'] as $row) { ?>
    <?php if ($row['accepted_last_interval'] > 0 and round(($row['rejected_last_interval'] / $row['shares_last_interval']) * 100, 2) >= $BTC_PROXY['rejected_alert']) { ?>
    <tr class="alert">
    <?php } elseif (!isset($row['active_pool']) or !isset($row['last_accepted_pool']) or !isset($row['shares_last_interval'])) { ?>
    <tr class="alert">
    <?php } else { ?>
    <tr>
    <?php } ?>
        <td>
            <form action="<?php echo_html(make_url('/admin/worker-pool.php')) ?>">
                <fieldset>
                    <input type="hidden" name="id" value="<?php echo_html($row['worker_id']) ?>" />
                    <?php $this->renderImageButton('index', 'manage-pools', 'Manage worker-pool') ?>
                </fieldset>
            </form>
            <?php echo htmlspecialchars($row['worker']) ?>
        </td>
        <td><?php
            if (isset($row['active_time'])) {
                if (isset($row['active_pool'])) {
                    echo_html("At " . format_date($row['active_time']) . " from {$row['active_pool']}");
                } else {
                    echo "At " . format_date($row['active_time']) . " from <i>(Unknown)</i>";
                }
            } else {
                echo "Never";
            }
        ?></td>
        <td><?php
            if (isset($row['last_accepted_time'])) {
                if (isset($row['last_accepted_pool'])) {
                    echo_html("At " . format_date($row['last_accepted_time']) . " to {$row['last_accepted_pool']}");
                } else {
                    echo "At " . format_date($row['last_accepted_time']) . " to <i>(Unknown)</i>";
                }
            } else {
                echo "Never";
            }
        ?></td>
        <td><?php
            if (isset($row['shares_last_interval'])) {
                echo_html("{$row['shares_last_interval']} shares");
            } else {
                echo "No shares";
            }
        ?></td>
        <td><?php
            if (isset($row['shares_last_interval']) and isset($row['rejected_last_interval'])) {
                if ($row['shares_last_interval'] > 0) {
                    echo_html(number_format(($row['rejected_last_interval'] / $row['shares_last_interval']) * 100, 2).'%');
                } else {
                    echo "0.00%";
                }
            } else {
                echo "0.00%";
            }
        ?></td>
        <td><?php
            if (isset($row['mhash'])) {
                print(round($row['mhash'],3));
            } else {
                echo "0";
            }
        ?> MHash/s</td>
    </tr>
    <?php } ?>
</table>

</div>

<br />

<div id="pool-status">

<h2>Pool status</h2>

<table class="data">
    <tr>
        <th>Pool</th>
        <th>Latest work requested</th>
        <th>Getworks<sup>*</sup></th>
        <th>Shares<sup>*</sup></th>
        <th>Rejected<sup>*</sup></th>
    </tr>
    <?php foreach ($this->viewdata['pool-status'] as $row) { ?>
    <?php if ($row['total'] > 0 and round(($row['rejected'] / $row['total']) * 100, 2) >= $BTC_PROXY['rejected_alert']) { ?>
    <tr class="alert">
    <?php } else { ?>
    <tr>
    <?php } ?>
        <td>
            <form action="<?php echo_html(make_url('/admin/pool.php')) ?>">
                <fieldset>
                    <input type="hidden" name="id" value="<?php echo_html($row['pool_id']) ?>" />
                    <?php $this->renderImageButton('edit', 'edit-pool', 'Edit pool') ?>
                </fieldset>
            </form>
            <?php echo htmlspecialchars($row['pool']) ?>
        </td>
        <td><?php
            if (isset($row['last_request'])) {
                if (isset($row['worker'])) {
                    echo_html("By {$row['worker']} at " . format_date($row['last_request']));
                } else {
                    echo "By <i>(Unknown)</i> at " . format_date($row['last_request']);
                }
            } else {
                echo "Never";
            }
        ?></td>
        <td><?php
            if (isset($row['getworks'])) {
                echo_html($row['getworks']);
            } else {
                echo "0";
            }
        ?></td>
        <td><?php
            if (isset($row['total'])) {
                echo_html($row['total']);
            } else {
                echo "0";
            }
        ?></td>
        <td><?php
            if (isset($row['total']) and isset($row['rejected'])) {
                if ($row['total'] > 0) {
                    echo_html(number_format(($row['rejected'] / $row['total']) * 100, 2).'%');
                } else {
                    echo "0.00%";
                }
            } else {
                echo "0.00%";
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
