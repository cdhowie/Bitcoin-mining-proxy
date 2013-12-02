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
        global $BALANCE_JSON;
?>

<div id="dashboard">

<?php if ($this->viewdata['url-fopen-disabled']) { ?>
<div id="url-fopen-disabled" class="dashboard-error"><span>The <tt>allow_url_fopen</tt> PHP configuration option is disabled.  <b>The proxy will be unable to contact pools until this option is enabled.</b></span></div>
<?php } ?>

<?php if ($this->viewdata['old-schema']) { ?>
<div id="old-schema" class="dashboard-warning"><span>Your database schema is out of date.  Please run the schema migration script (check the readme for instructions).  Until you migrate your database schema, you may notice errors or poor performance.</span></div>
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
    <?php 
    $workerstatus_count = 0;
    foreach ($this->viewdata['worker-status'] as $row) { 
          $workerstatus_count++;
    ?>
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
                    echo_html(format_date_with_prefix($row['active_time'], true) . " from {$row['active_pool']}");
                } else {
                    echo format_date_with_prefix($row['active_time'], true) . " from <i>(Unknown)</i>";
                }
            } else {
                echo "Never";
            }
        ?></td>
        <td><?php
            if (isset($row['last_accepted_time'])) {
                if (isset($row['last_accepted_pool'])) {
                    echo_html(format_date_with_prefix($row['last_accepted_time'], true) . " to {$row['last_accepted_pool']}");
                } else {
                    echo format_date_with_prefix($row['last_accepted_time'], true) . " to <i>(Unknown)</i>";
                }
            } else {
                echo "Never";
            }
        ?></td>
        <td><?php
            if (isset($row['shares_last_interval'])) {
                echo_html($row['shares_last_interval']);
            } else {
                echo "0";
            }
        ?></td>
        <td><?php
            if (isset($row['rejected_last_interval'])) {
                echo_html($row['rejected_last_interval']);
            } else {
                echo "0";
            }
            echo " (";
            if (isset($row['shares_last_interval']) and isset($row['rejected_last_interval'])) {
                if ($row['shares_last_interval'] > 0) {
                    echo_html(number_format(($row['rejected_last_interval'] / $row['shares_last_interval']) * 100, 2).'%');
                } else {
                    echo "0.00%";
                }
            } else {
                echo "0.00%";
            }
            echo ")";
        ?></td>
        <td><?php
            if (isset($row['mhash'])) {
                print(round($row['mhash'],3));
            } else {
                echo "0";
            }
        ?> MHash/s</td>
    </tr>
    <?php
        //build cumulative counts so we don't have to reload the data
        $global_worker_status['shares_last_interval'] = $global_worker_status['shares_last_interval']+$row['shares_last_interval'];
        $global_worker_status['rejected_last_interval'] = $global_worker_status['rejected_last_interval']+$row['rejected_last_interval'];
        $global_worker_status['mhash'] = $global_worker_status['mhash']+$row['mhash'];
    } ?> 
    <tr class="datatotals">
        <td><b>Totals</b></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td><?php
            if (isset($global_worker_status['shares_last_interval'])) {
                echo_html($global_worker_status['shares_last_interval']);
            } else {
                echo "0";
            }
        ?></td>
        <td><?php
            if (isset($global_worker_status['rejected_last_interval'])) {
                echo_html($global_worker_status['rejected_last_interval']);
            } else {
                echo "0";
            }
            echo " (";
            if (isset($global_worker_status['shares_last_interval']) and isset($global_worker_status['rejected_last_interval'])) {
                if ($global_worker_status['shares_last_interval'] > 0) {
                    echo_html(number_format(($global_worker_status['rejected_last_interval'] / $global_worker_status['shares_last_interval']) * 100, 2).'%');
                } else {
                    echo "0.00%";
                }
            } else {
                echo "0.00%";
            }
            echo ")";
        ?></td>
        <td><?php
            if (isset($global_worker_status['mhash'])) {
                print(round($global_worker_status['mhash'],3));
            } else {
                echo "0";
            }
        ?> MHash/s</td>
    </tr>
</table>
<div id="workerstatus-chart" align="center"></div>
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
    <?php 
    $poolstatus_count = 0;
    foreach ($this->viewdata['pool-status'] as $row) { 
	$poolstatus_count++;
    ?>
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
                    echo_html("By {$row['worker']} " . format_date_with_prefix($row['last_request'], false));
                } else {
                    echo "By <i>(Unknown)</i> " . format_date_with_prefix($row['last_request'], false);
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
            if (isset($row['rejected'])) {
                echo_html($row['rejected']);
            } else {
                echo "0";
            }
            echo " (";
            if (isset($row['total']) and isset($row['rejected'])) {
                if ($row['total'] > 0) {
                    echo_html(number_format(($row['rejected'] / $row['total']) * 100, 2).'%');
                } else {
                    echo "0.00%";
                }
            } else {
                echo "0.00%";
            }
            echo ")";
        ?></td>
    </tr>
    <?php
        //build cumulative counts so we don't have to reload the data
        $global_pool_status['getworks'] = $global_pool_status['getworks']+$row['getworks'];
        $global_pool_status['total'] = $global_pool_status['total']+$row['total'];
        $global_pool_status['rejected'] = $global_pool_status['rejected']+$row['rejected'];
    } ?>
    <tr class="datatotals">
        <td><b>Totals</b></td>
        <td>&nbsp;</td>
        <td><?php
            if (isset($global_pool_status['getworks'])) {
                echo_html($global_pool_status['getworks']);
            } else {
                echo "0";
            }
        ?></td>
        <td><?php
            if (isset($global_pool_status['total'])) {
                echo_html($global_pool_status['total']);
            } else {
                echo "0";
            }
        ?></td>
        <td><?php
            if (isset($global_pool_status['rejected'])) {
                echo_html($global_pool_status['rejected']);
            } else {
                echo "0";
            }
            echo " (";
            if (isset($global_pool_status['total']) and isset($global_pool_status['rejected'])) {
                if ($global_pool_status['total'] > 0) {
                    echo_html(number_format(($global_pool_status['rejected'] / $global_pool_status['total']) * 100, 2).'%');
                } else {
                    echo "0.00%";
                }
            } else {
                echo "0.00%";
            }
            echo ")";
        ?></td>
    </tr>
</table>
</div>

<br/>
<div class="poolcharts" align="center">
<table>
<tr style="border: 0;">
   <td><div id="poolchart_div"></div></td>
   <td><div id="poolchart_balance_col"></div><div id="balance_col_footer" align="center"></div></td>
</tr>
</table>
</div>

<div id="interval_config">
    <h2>Interval Override</h2>
    <form action="" method="GET">
        Interval(seconds):<input type="text" name="interval" size="4"/>
    </form>
</div>

<?php
if ($BTC_PROXY['enable_graphs']) { ?>

<script type="text/javascript">
    google.load("visualization", "1", {packages:["corechart"]});
    google.load("jquery", "1.6.2");
    google.setOnLoadCallback(drawChartPool);
    google.setOnLoadCallback(drawChartWorkerShares);
    function drawChartPool() {
      var data = new google.visualization.DataTable();
      data.addColumn('string', 'Pool');
      data.addColumn('number', 'Shares');
      data.addRows(<?php echo $poolstatus_count ?>);
      <?php
      $idx = 0;
      foreach ($this->viewdata['pool-status'] as $row) {
         echo "data.setValue({$idx}, 0, \"{$row['pool']}\");";
         echo "data.setValue({$idx}, 1, {$row['total']});";
         echo "\n";
         $idx++;
      }
      ?>

      var chart = new google.visualization.PieChart(document.getElementById('poolchart_div'));
      chart.draw(data, {backgroundColor: '#222', 
		width: 440, height: 260, legend: 'left',
		titleTextStyle: {color: 'white'},
		pieSliceTextStyle: {color: 'white'},
		legendTextStyle: {color: 'white'},
		title: 'Mining Pool Shares'});
    }
    function drawChartWorkerShares() {
      var data = new google.visualization.DataTable();
      data.addColumn('string', 'Worker');
      data.addColumn('number', 'Shares');
      data.addRows(<?php echo $workerstatus_count ?>);
      <?php
      $idx = 0;
      foreach ($this->viewdata['worker-status'] as $row) {
         echo "data.setValue({$idx}, 0, \"{$row['worker']}\");";
         $shares = 0;
         if (isset($row['shares_last_interval'])) {
            $shares = $row['shares_last_interval'];
         }
         echo "data.setValue({$idx}, 1, $shares);";
         echo "\n";
         $idx++;
      }
      ?>
      var chart = new google.visualization.PieChart(document.getElementById('workerstatus-chart'));
      chart.draw(data, {backgroundColor: '#222', 
                width: 500, height: 220,
                titleTextStyle: {color: 'white'},
                pieSliceTextStyle: {color: 'white'},
                legendTextStyle: {color: 'white'},
                title: 'Worker Shares Distribution'});
    }
</script>

<?php
} ?>

<!-- Balance stats -->
<script type="text/javascript">

Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

var balance = new Array();
var unconfirmed = new Array();
var pool_count = <?php echo count($BALANCE_JSON) ?>;
var pools = [<?php
   $i = 0;
   foreach ($BALANCE_JSON as $key => $value) {
	echo "'$key'";
        $i++;
	if ($i < count($BALANCE_JSON)) { echo ","; }
   }
?>];
<?php
foreach ($BALANCE_JSON as $key => $value) {
   echo "pools['$key'] = new Array();\n";
   echo "pools['$key']['confirmed'] = '" . $BALANCE_JSON[$key]['confirmed'] ."';\n";
   echo "pools['$key']['unconfirmed'] = '" . $BALANCE_JSON[$key]['unconfirmed'] ."';\n";
}
?>

function createGraphNotifier() {
   var counter = <?php echo count($BALANCE_JSON) ?>;
   return function() {
      if (--counter == 0) graphBalances();
   }
}
var notifyGraph = createGraphNotifier();

function getBalance(id, balance) {
   $.ajaxSetup({timeout: 15000});
   $.getJSON('../proxy-json.php?pool='+id, function(data) {
      // confirmed
      var evalstr = "var t = data." + pools[id]['confirmed'];
      eval(evalstr);
      balance[id] = t;
      // unconfirmed
      if (pools[id]['unconfirmed']) {
         t = 0;
         evalstr = "t = data." + pools[id]['unconfirmed'];
         eval(evalstr);
         unconfirmed[id] = t;
      }
      console.log(id+ ": " + balance[id] + " / " + unconfirmed[id]);
      notifyGraph();
   });
}

function getBalances(balance) {   
   var counter = 0;
   for (var pool in pools) {
      if (pools[pool]['confirmed'])
         getBalance(pool, balance);
   }
}


<?php if ($BTC_PROXY['enable_graphs']) { ?>
function graphBalances() {
   console.log("graphBalances() " + Object.size(balance));
   function drawBalanceGraphCol() {
      if (!(balance && Object.size(balance) > 0)) { console.log("return"); return; }
      var balance_total = 0;
      var unconfirmed_total = 0;
      for (var bal in balance) {
         balance_total += parseFloat(balance[bal]);
         if (unconfirmed[bal])
                unconfirmed_total += parseFloat(unconfirmed[bal]);
      }
      balance_total = Math.round(balance_total*1000)/1000;
      unconfirmed_total = Math.round(unconfirmed_total*1000)/1000;
      var total = balance_total + unconfirmed_total;
      total = Math.round(total*1000)/1000;
      document.getElementById('balance_col_footer')
	.innerHTML = '<strong>Total:</strong> ' + total;
      var data = new google.visualization.DataTable();
      data.addColumn('string', 'Pool');
      data.addColumn('number', 'Confirmed ('+balance_total+')');
      data.addColumn('number', 'Unconfirmed ('+unconfirmed_total+')');

      data.addRows(pool_count);
      var i = 0;
      for (var bal in balance) {
         data.setValue(i, 0, bal);
         data.setValue(i, 1, parseFloat(balance[bal]));
         data.setValue(i, 2, parseFloat(unconfirmed[bal]));
         i++;
      }
      var chart = new google.visualization.ColumnChart(document.getElementById('poolchart_balance_col'));
      chart.draw(data, {width:500, height:260, 
			backgroundColor: '#222',
			titleTextStyle: {color: 'white'},
                	pieSliceTextStyle: {color: 'white'},
                	legendTextStyle: {color: 'white'},
			title: 'Mining Pool Balance(s)',
			isStacked: true, legend: 'bottom',
			hAxis: { title:' ', titleTextStyle: {color: 'white'}, textStyle: {color: 'white'} },
			vAxis: { title:'BTC', titleTextStyle: {color: 'white'}, textStyle: {color: 'white'} }
			});
   }
   drawBalanceGraphCol();
}
window.addEventListener("balanceReady", graphBalances, false);
<?php } ?>

setTimeout(getBalances(balance), 3000);

</script>


<?php
    }
}

?>
