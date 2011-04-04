<?php

require_once(dirname(__FILE__) . '/../master.view.php');

class AdminWorkerPoolView
    extends MasterView
    implements IJsonView
{
    protected function renderBody()
    {
?>

<div id="worker-pools">

<table class="data">
    <tr>
        <th>Pool</th>
        <th>Priority</th>
        <th>Enabled</th>
        <th>Pool enabled</th>
        <th>Pool username</th>
        <th>Pool password</th>
    </tr>
    <?php foreach ($this->viewdata['worker-pools'] as $row) { ?>
    <tr class="<?php if (!$row['enabled'] || !$row['pool-enabled']) { echo 'disabled'; } ?>">
        <td><?php echo_html($row['pool'])     ?></td>
        <td><?php echo_html($row['priority']) ?></td>
        <td><?php echo($row['enabled'] ? 'Yes' : 'No') ?></td>
        <td><?php echo($row['pool-enabled'] ? 'Yes' : 'No') ?></td>
        <td><?php echo_html($row['username']) ?></td>
        <td><?php echo_html($row['password']) ?></td>
    </tr>
    <?php } ?>
</table>

</div>

<?php
    }
}

?>
