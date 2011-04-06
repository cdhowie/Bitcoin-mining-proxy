<?php

require_once(dirname(__FILE__) . '/../mvc.inc.php');

abstract class MasterView
    extends ViewBase
    implements IHtmlView
{
    private static $menuitems = array(
        array('dashboard', 'Dashboard', '/admin/'),
        array('pools',     'Pools',     '/admin/pool.php'),
        array('workers',   'Workers',   '/admin/workers.php')
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
                if ($parts[0] == 'application/xhtml+xml') {
                    header('Content-Type: application/xhtml+xml');
                    return;
                }
            }

            header('Content-Type: text/html');
            return;
        }

        header('Content-Type: application/xhtml+xml');
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
        echo '<?xml version="1.0" encoding="UTF-8" ?>';

        if (!isset($this->viewdata['title'])) {
            $title = $this->getTitle();
        } else {
            $title = $this->viewdata['title'];
        }

        $menuid = $this->getMenuId();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title><?php echo_html($title) ?></title>
        <link rel="stylesheet" type="text/css" href="<?php echo_html(make_url('/assets/style.css')) ?>" />
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
            bitcoin-mining-proxy &copy; 2011 <a href="http://www.chrishowie.com">Chris Howie</a>.

            This software may be distributed or hosted under the terms of the
            <a href="http://www.gnu.org/licenses/agpl.html">GNU Affero General Public License</a>, version 3 or later.
        </div>
    </body>
</html>

<?php
    }
}
