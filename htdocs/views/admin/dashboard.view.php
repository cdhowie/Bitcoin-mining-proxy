<?php

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

<div id="recent-submissions">

<h2>Recent work submissions</h2>

<table class="data"><?php $this->renderWorkTable($this->viewdata['recent-submissions']) ?></table>

</div>

<div id="recent-failed-submissions">

<h2>Recent failed work submissions</h2>

<table class="data"><?php $this->renderWorkTable($this->viewdata['recent-failed-submissions']) ?></table>

</div>

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
        <td><?php echo htmlspecialchars($row['worker']) ?></td>
        <td>At <?php echo_html($row['active_time']) ?> from <?php echo_html($row['active_pool']) ?></td>
        <td><?php
            $value = $row['last_accepted_submission'];
            if (!$value) {
                $value = 'Never';
            }

            $value = "At $value to " . $row['last_accepted_pool'];

            echo_html($value);
        ?></td>
    </tr>
    <?php } ?>
</table>

</div>

<?php
    }
}

?>
