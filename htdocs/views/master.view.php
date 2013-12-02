<?php

/*
 * ./htdocs/views/master.view.php
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

require_once(dirname(__FILE__) . '/../mvc.inc.php');

abstract class MasterView
    extends ViewBase
    implements IHtmlView
{
    private static $menuitems = array(
        array('dashboard', 'Dashboard', '/admin/'),
        array('pools',     'Pools',     '/admin/pool.php'),
        array('workers',   'Workers',   '/admin/workers.php'),
        array('about',     'About',     '/admin/about.php')
    );

    protected function getMenuId()
    {
        return "";
    }

    protected abstract function renderBody();

    protected function getTitle()
    {
        die('No page title set.');
    }

    public function renderHtmlHeaders()
    {
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $types = explode(',', $_SERVER['HTTP_ACCEPT']);

            foreach ($types as $type) {
                $parts = explode(';', $type);
                if ($parts[0] == 'application/xhtml') {
                    header('Content-Type: application/xhtml');
                    return;
                }
            }

            header('Content-Type: text/html');
            return;
        }

        header('Content-Type: application/xhtml+xml');
    }

    protected function renderImageButton($action, $cssClass, $text, $title = FALSE)
    {
        if ($title === FALSE) {
            $title = $text;
        }

        ?><button name="action" value="<?php echo_html($action) ?>" title="<?php echo_html($title) ?>" class="image-button image-button-<?php echo_html($cssClass) ?>"><span><?php echo_html($text) ?></span></button> <?php
    }

    protected function displayNoticeList($key)
    {
        $notices = get_tempdata($key);

        if (count($notices) == 0) {
            return;
        }

?>
    <div class="<?php echo_html($key) ?>">
    <ul>
    <?php foreach ($notices as $notice) { ?>
        <li><?php echo_html($notice) ?></li>
    <?php } ?>
    </ul>
    </div>
<?php

    }

    public function renderHtml()
    {
	global $BTC_PROXY;
        echo '<?xml version="1.0" encoding="UTF-8" ?>';

        if (!isset($this->viewdata['title'])) {
            $title = $this->getTitle();
        } else {
            $title = $this->viewdata['title'];
        }

        $menuid = $this->getMenuId();
        if ($title == "Dashboard" && $BTC_PROXY['refresh_interval'] > 0) {
            header("Refresh: " . $BTC_PROXY['refresh_interval']);
        }
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title><?php echo_html($title) ?></title>
        <link rel="stylesheet" type="text/css" href="<?php echo_html(make_url('/assets/style.css')) ?>" />
        <script type="text/javascript" src="http://www.google.com/jsapi"></script>
        <script type="text/javascript">
            google.load('visualization', '1', {packages: ['corechart']});
        </script>
    </head>
    <body>
        <h1><?php echo_html($title) ?></h1>

        <ul id="navmenu">
            <?php foreach (self::$menuitems as $item) { ?>
            <li id="nav-<?php echo_html($item[0]) ?>"<?php if ($menuid == $item[0]) { ?> class="active"<?php } ?>><a href="<?php echo_html(make_url($item[2])) ?>"><span><?php echo_html($item[1]) ?></span></a></li>
            <?php } ?>
        </ul>

        <div id="content">

<?php
    $this->displayNoticeList('errors');
    $this->displayNoticeList('info');

    $this->renderBody();
?>

        </div>

        <div id="footer">
            <sup>*</sup> Data is based off your value of the 'average_interval' (<?php if ($this->viewdata['interval_override']) { echo "override: " . $this->viewdata['interval_override']; } else { echo $BTC_PROXY['average_interval']; } ?>) setting in the config file.<br /><br />
            bitcoin-mining-proxy &copy; 2011 <a href="http://www.chrishowie.com">Chris Howie</a>.

            This software may be distributed or hosted under the terms of the
            <a href="http://www.gnu.org/licenses/agpl.html">GNU Affero General Public License</a>, version 3 or later.
        </div>
    </body>
</html>

<?php
    }
}
