<?php

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
        <td>
            <?php
                $indicator = $pool->enabled ? 'flag_green.png' : 'flag_red.png';
            ?>
            <form action="<?php echo_html(make_url('/admin/pool.php')) ?>" method="post">
                <fieldset>
                    <input type="hidden" name="id" value="<?php echo_html($pool->id) ?>" />
                    <input type="hidden" name="action" value="toggleEnabled" />
                    <input type="image" title="Toggle" alt="<?php echo_html($pool->enabled ? 'Yes' : 'No') ?>" src="<?php echo_html(make_url("/assets/icons/$indicator")) ?>" />
                </fieldset>
            </form>
        </td>
        <td><?php echo_html($pool->url) ?></td>
        <td>&nbsp;</td>
    </tr>
    <?php } ?>
    <tr>
        <td colspan="3">&nbsp;</td>
        <td>
            <form action="<?php echo_html(make_url('/admin/pool.php')) ?>">
                <fieldset>
                    <input type="hidden" name="action" value="new" />
                    <input type="image" title="New pool" alt="New pool"
                        src="<?php echo_html(make_url('/assets/icons/server_add.png')) ?>" />
                </fieldset>
            </form>
        </td>
    </tr>
</table>

</div>

<?php
    }
}

?>
