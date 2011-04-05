<?php

require_once(dirname(__FILE__) . '/../master.view.php');

class AdminWorkerPoolView
    extends MasterView
    implements IJsonView
{
    protected function getTitle()
    {
        return "Worker pool management - " . $this->viewdata['worker-name'];
    }

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
        <td>
            <?php
                $indicator = $row['enabled'] ? 'flag_green.png' : 'flag_red.png';
                $newstatus = $row['enabled'] ? 0 : 1;
            ?>
            <form action="<?php echo_html(make_url('/admin/worker-pool.php')) ?>" method="POST">
                <input type="hidden" name="id" value="<?php echo_html($this->viewdata['worker-id']) ?>" />
                <input type="hidden" name="pool-id" value="<?php echo_html($row['pool-id']) ?>" />
                <input type="hidden" name="action" value="setEnabled" />
                <input type="hidden" name="enabled" value="<?php echo_html($newstatus) ?>" />
                <input type="image" src="<?php echo_html(make_url("/assets/icons/$indicator")) ?>" />
            </form>
        </td>
        <td>
            <?php
                $indicator = $row['pool-enabled'] ? 'flag_green.png' : 'flag_red.png';
            ?>
            <img src="<?php echo_html(make_url("/assets/icons/$indicator")) ?>" />
        </td>
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
