<?php

require_once(dirname(__FILE__) . '/../master.view.php');

class AdminWorkersView
    extends MasterView
    implements IJsonView
{
    protected function renderBody()
    {
?>

<div id="workers">

<table class="data">
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
                <input type="hidden" name="id" value="<?php echo_html($row['id']) ?>" />
                <input type="submit" value="Manage pools" />
            </form>

        <?php
            if ($row['pools'] == 0) {
?>
            <form action="<?php echo_html(make_url('/admin/workers.php')) ?>" method="POST">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id" value="<?php echo_html($row['id']) ?>" />
                <input type="submit" value="Delete" />
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
                <input type="hidden" name="action" value="new" />
                <input type="submit" value="New" />
            </form>
        </td>
    </tr>
</table>

</div>

<?php
    }
}

class AdminWorkersNewView
    extends MasterView
{
    protected function renderBody()
    {
?>

<div id="new-worker">

<form action="<?php echo_html($_SERVER['REQUEST_URI']) ?>" method="POST">
<table class="entry">
    <tr>
        <th><label for="name">Name:</label></th>
        <td><input name="name" id="name" size="25" value="<?php echo_html($this->viewdata['form']['name']) ?>" /></td>
    </tr>
    <tr>
        <th><label for="password">Password:</label></th>
        <td><input name="password" id="password" type="password" size="25" value="<?php echo_html($this->viewdata['form']['password']) ?>" /></td>
    </tr>
    <tr class="submit">
        <td>&nbsp;</td>
        <td><input type="submit" value="Create worker" /></td>
    </tr>
</table>
</form>

</div>

<?php
    }
}

?>
