<?php

require_once(dirname(__FILE__) . '/../common.inc.php');

class PoolModel
{
    public $id;

    public $name;
    public $url;
    public $enabled;

    function __construct($form = FALSE)
    {
        if ($form !== FALSE) {
            $this->id = $form['id'];
            $this->name = $form['name'];
            $this->url = $form['url'];
            $this->enabled = $form['enabled'];

            $this->canonize();
        }
    }

    public function canonize()
    {
        $this->id = (int)$this->id;
        $this->name = trim($this->name);
        $this->url = trim($this->url);
        $this->enabled = $this->enabled ? 1 : 0;
    }

    public function toggleEnabled()
    {
        $this->canonize();

        if ($this->id == 0) {
            return false;
        }
        
        $pdo = db_connect();

        $q = $pdo->prepare('
            UPDATE pool

            SET enabled = NOT enabled

            WHERE id = :pool_id
        ');

        if ($q->execute(array(':pool_id' => $this->id))) {
            $this->enabled = $this->enabled ? 0 : 1;
            return TRUE;
        }

        return FALSE;
    }

    public function refresh()
    {
        $id = (int)$this->id;

        if ($id == 0) {
            return FALSE;
        }

        $pdo = db_connect();

        $q = $pdo->prepare('
            SELECT name, enabled, url

            FROM pool

            WHERE id = :pool_id
        ');

        if (!$q->execute(array(':pool_id' => $id))) {
            return FALSE;
        }

        $row = $q->fetch();
        $q->closeCursor();

        if ($row === FALSE) {
            return FALSE;
        }

        $this->id = $id;
        $this->name = $row['name'];
        $this->url = $row['url'];
        $this->enabled = $row['enabled'];

        return TRUE;
    }

    public function validate()
    {
        $errors = array();

        $this->canonize();

        if ($this->name == '') {
            $errors[] = 'Pool name is required.';
        }

        if ($this->url == '') {
            $errors[] = 'Pool URL is required.';
        }

        return count($errors) ? $errors : TRUE;
    }
}

?>
