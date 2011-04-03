<?php

require_once('./views/master.view.php');

class AdminDashboardView
    extends MasterView
    implements IJsonView
{
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

    protected function renderBody($viewdata)
    {
?>

<div id="recent-submissions">

<h2>Recent work submissions</h2>

<table><?php $this->renderWorkTable($viewdata['recent-submissions']) ?></table>

</div>

<div id="recent-failed-submissions">

<h2>Recent failed work submissions</h2>

<table><?php $this->renderWorkTable($viewdata['recent-failed-submissions']) ?></table>

</div>

<div id="worker-status">

<h2>Worker status</h2>

<table>
    <tr>
        <th>Worker</th>
        <th>Last pool</th>
        <th>Last work request</th>
        <th>Last accepted submission</th>
    </tr>
    <?php foreach ($viewdata['worker-status'] as $row) { ?>
    <tr>
        <td><?php echo htmlspecialchars($row['worker'])                   ?></td>
        <td><?php echo htmlspecialchars($row['pool'])                     ?></td>
        <td><?php echo htmlspecialchars($row['active_time'])              ?></td>
        <td><?php
            $value = $row['last_accepted_submission'];
            if (!$value) {
                $value = 'Never';
            }

            echo htmlspecialchars($value);
        ?></td>
    </tr>
    <?php } ?>
</table>

</div>

<?php
    }
}

?>
