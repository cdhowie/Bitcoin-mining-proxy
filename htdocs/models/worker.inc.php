<?php

require_once(dirname(__FILE__) . '/../common.inc.php');

class WorkerModel
{
    public $id;

    public $name;
    public $password;

    function __construct($form = FALSE)
    {
        if ($form !== FALSE) {
            $this->id = $form['id'];

            $this->name = $form['name'];
            $this->password = $form['password'];
        }
    }

    function validate()
    {
        $errors = array();

        $this->name = trim($this->name);
        $this->password = trim($this->password);

        if ($this->name == '') {
            $errors[] = 'Worker name is required.';
        } elseif (strpos($this->name, ':') !== FALSE) {
            $errors[] = 'Worker name may not contain a colon.';
        }

        if ($this->password == '') {
            $errors[] = 'Worker password is required.';
        }

        return count($errors) ? $errors : TRUE;
    }
}

?>
