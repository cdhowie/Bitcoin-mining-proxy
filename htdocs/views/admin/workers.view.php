<?php

require_once(dirname(__FILE__) . '/../master.view.php');

abstract class WorkersView
    extends MasterView
{
    protected function getMenuId()
    {
        return "workers";
    }
}

class AdminWorkersView
    extends WorkersView
    implements IJsonView
{
    protected function getTitle()
    {
        return "Worker management";
    }

    protected function renderBody()
    {
?>

<div id="workers">

<table class="data centered">
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
                <fieldset>
                    <input type="hidden" name="id" value="<?php echo_html($row['id']) ?>" />
                    <input type="image" title="Manage pools" alt="Manage pools"
                        src="<?php echo_html(make_url('/assets/icons/server_go.png')) ?>" />
                </fieldset>
            </form>
            <form action="<?php echo_html(make_url('/admin/workers.php')) ?>" method="get">
                <fieldset>
                    <input type="hidden" name="id" value="<?php echo_html($row['id']) ?>" />
                    <input type="hidden" name="action" value="edit" />
                    <input type="image" title="Edit worker" alt="Edit worker"
                        src="<?php echo_html(make_url('/assets/icons/cog_edit.png')) ?>" />
                </fieldset>
            </form>
        <?php
            if ($row['pools'] == 0) {
?>
            <form action="<?php echo_html(make_url('/admin/workers.php')) ?>" method="post">
                <fieldset>
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?php echo_html($row['id']) ?>" />
                    <input type="image" title="Delete worker" alt="Delete worker"
                        src="<?php echo_html(make_url('/assets/icons/cog_delete.png')) ?>" />
                </fieldset>
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
                <fieldset>
                    <input type="hidden" name="action" value="new" />
                    <input type="image" title="New worker" alt="New worker"
                        src="<?php echo_html(make_url('/assets/icons/cog_add.png')) ?>" />
                </fieldset>
            </form>
        </td>
    </tr>
</table>

</div>

<?php
    }
}

class AdminWorkerNewEditView
    extends WorkersView
{
    protected function getTitle()
    {
        return $this->viewdata['worker']->id ? 'Edit worker' : 'New worker';
    }

    protected function getDivId()
    {
        return $this->viewdata['worker']->id ? 'edit-worker' : 'new-worker';
    }

    protected function getAction()
    {
        return $this->viewdata['worker']->id ? 'edit' : 'new';
    }

    protected function getSubmitValue()
    {
        return $this->viewdata['worker']->id ? 'Save changes' : 'Create worker';
    }

    protected function renderBody()
    {
?>

<div id="<?php echo $this->getDivId() ?>">

<form action="<?php echo_html(make_url('/admin/workers.php')) ?>" method="post">

<fieldset>
<input type="hidden" name="action" value="<?php echo $this->getAction() ?>" />
<?php if ($this->viewdata['worker']->id) { ?>
<input type="hidden" name="id" value="<?php echo_html($this->viewdata['worker']->id) ?>" />
<?php } ?>
</fieldset>

<table class="entry centered">
    <tr>
        <th><label for="name">Name:</label></th>
        <td><input name="name" id="name" size="25" value="<?php echo_html($this->viewdata['worker']->name) ?>" /></td>
    </tr>
    <tr>
        <th><label for="password">Password:</label></th>
        <td><input name="password" id="password" size="25" value="<?php echo_html($this->viewdata['worker']->password) ?>" /></td>
    </tr>
    <tr class="submit">
        <td>&nbsp;</td>
        <td><input type="submit" value="<?php echo $this->getSubmitValue() ?>" /></td>
    </tr>
</table>

</form>

</div>

<?php
    }
}

?>
