<?php

require_once(dirname(__FILE__) . '/master.view.php');

class NotFoundView
    extends MasterView
{
    function __construct($requested_type = FALSE)
    {
        parent::__construct(array(), $requested_type);
    }

    public function getTitle()
    {
        return '404 Not Found';
    }

    public function renderHtmlHeaders()
    {
        parent::renderHtmlHeaders();

        header('HTTP/1.0 404 Not Found');
    }

    public function renderBody()
    {
?>

<p style="text-align:center;">This is not the page you're looking for.</p>

<?php
    }
}

?>
