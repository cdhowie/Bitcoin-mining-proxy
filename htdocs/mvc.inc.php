<?php

abstract class ViewBase
{
    private $renderMethod;
    private $renderHeadersMethod;

    private static $viewTypes = array(
        array('Json', FALSE),
        array('Html', TRUE)
    );

    function __construct($requested_type = FALSE) {
        if ($requested_type === FALSE) {
            $requested_type = $_GET['format'];
        }

        foreach (self::$viewTypes as $type) {
            if ($this->checkViewType($requested_type, $type[0], $type[1])) {
                return;
            }
        }

        die("No usable view type implemented on " . get_class($this));
    }

    private function checkViewType($requested_type, $type, $default = FALSE)
    {
        if (!$default && strcasecmp($requested_type, $type) != 0) {
            return FALSE;
        }

        if (!is_a($this, "I{$type}View")) {
            return FALSE;
        }

        $this->renderMethod = "render{$type}";
        $this->renderHeadersMethod = "render{$type}Headers";

        return TRUE;
    }

    private function renderHeaders($viewdata)
    {
        $method = $this->renderHeadersMethod;
        $this->$method($viewdata);
    }

    public function render($viewdata)
    {
        $this->renderHeaders($viewdata);

        $method = $this->renderMethod;
        $this->$method($viewdata);
    }

    # Provide a default implementation for JSON.  A subclass must still
    # implement IJsonView for JSON to be supported.
    public function renderJsonHeaders($viewdata)
    {
        header('Content-Type: application/json');
    }

    public function renderJson($viewdata)
    {
        echo json_encode($viewdata);
    }
}

interface IHtmlView
{
    public function renderHtmlHeaders($viewdata);
    public function renderHtml($viewdata);
}

interface IJsonView
{
    public function renderJsonHeaders($viewdata);
    public function renderJson($viewdata);
}

?>
