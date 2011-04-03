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

<table>
    <tr>
        <th>Name</th>
        <th>Password</th>
    </tr>
    <?php foreach ($this->viewdata['workers'] as $row) { ?>
    <tr>
        <td><?php echo_html($row['name'])     ?></td>
        <td><?php echo_html($row['password']) ?></td>
    </tr>
    <?php } ?>
</table>

</div>

<?php
    }
}

?>
