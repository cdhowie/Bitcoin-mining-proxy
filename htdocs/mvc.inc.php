<?php

/*
 * ./htdocs/mvc.inc.php
 *
 * Copyright (C) 2011  Chris Howie <me@chrishowie.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

abstract class ViewBase
{
    private $renderMethod;
    private $renderHeadersMethod;

    protected $viewdata;

    private static $viewTypes = array(
        array('Json', FALSE),
        array('Html', TRUE)
    );

    function __construct($viewdata, $requested_type = FALSE) {
        $this->viewdata = $viewdata;

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

    private function renderHeaders()
    {
        $method = $this->renderHeadersMethod;
        $this->$method();
    }

    public function render()
    {
        $this->renderHeaders();

        $method = $this->renderMethod;
        $this->$method();
    }

    # Provide a default implementation for JSON.  A subclass must still
    # implement IJsonView for JSON to be supported.
    public function renderJsonHeaders()
    {
        header('Content-Type: application/json');
    }

    public function renderJson()
    {
        echo json_encode($this->viewdata);
    }
}

interface IHtmlView
{
    public function renderHtmlHeaders();
    public function renderHtml();
}

interface IJsonView
{
    public function renderJsonHeaders();
    public function renderJson();
}

abstract class Controller
{
    public function preExecute()
    {
    }

    public function execute()
    {
        $action =
            isset($_GET['action']) ? $_GET['action'] :
            (isset($_POST['action']) ? $_POST['action'] :
            'index');

        $specificAction = "{$action}{$_SERVER['REQUEST_METHOD']}View";
        $genericAction = "{$action}DefaultView";

        $class = new ReflectionClass($this);
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $argCount = $method->getNumberOfRequiredParameters();

            if ($argCount > 1)
                continue;

            $name = $method->name;

            if (strcasecmp($specificAction, $name) == 0) {
                if ($argCount == 1) {
                    $request = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;

                    $args = $method->getParameters();
                    $argClass = $args[0]->getClass();
                    if ($argClass) {
                        $request = $argClass->newInstance($request);
                    }

                    return $method->invoke($this, $request);
                } else {
                    return $method->invoke($this);
                }
            } elseif (strcasecmp($genericAction, $name) == 0) {
                if ($argCount == 1) {
                    $request = array_merge($_GET, $_POST);

                    $args = $method->getParameters();
                    $argClass = $args[0]->getClass();
                    if ($argClass) {
                        $request = $argClass->newInstance($request);
                    }

                    return $method->invoke($this, $request);
                } else {
                    return $method->invoke($this);
                }
            }
        }

        return $this->notFoundView();
    }

    public function notFoundView()
    {
        die('View method not found.');
    }
}

class MvcEngine
{
    public static function run($controller)
    {
        $controller->preExecute();

        $view = $controller->execute();
        $view->render();
    }
}

class RedirectView extends ViewBase
    implements IHtmlView, IJsonView
{
    public function renderHtmlHeaders()
    {
        header('Location: ' . make_absolute_url($this->viewdata));
    }

    public function renderJsonHeaders()
    {
        $this->renderHtmlHeaders();
    }

    public function renderHtml()
    {
    }

    public function renderJson()
    {
    }
}

?>
