<?php

class WorkerPoolModel
{
    public $pool_id;
    public $pool_name;

    public $worker_id;
    public $worker_name;

    public $pool_username;
    public $pool_password;

    public $priority;
    public $enabled;

    function __construct($form = FALSE)
    {
        if ($form !== FALSE) {
            $this->pool_id = $form['pool_id'];
            $this->worker_id = $form['worker_id'];

            $this->pool_username = $form['pool_username'];
            $this->pool_password = $form['pool_password'];

            $this->priority = $form['priority'];
            $this->enabled = $form['enabled'];

            $this->canonize();
        }
    }

    public function canonize()
    {
        $this->pool_id = (int)$this->pool_id;
        $this->worker_id = (int)$this->worker_id;

        $this->pool_username = trim($this->pool_username);
        $this->pool_password = trim($this->pool_password);

        $this->priority = (int)$this->priority;
        $this->enabled = $this->enabled ? 1 : 0;
    }

    public function refresh()
    {
        $pdo = db_connect();

        $q = $pdo->prepare('
            SELECT
                wp.pool_username AS pool_username,
                wp.pool_password AS pool_password,
                wp.priority AS priority,
                wp.enabled AS enabled,

                p.name AS pool_name,
                w.name AS worker_name

            FROM worker w

            INNER JOIN pool p
                ON p.id = :pool_id

            LEFT OUTER JOIN worker_pool wp
                ON wp.worker_id = :worker_id
               AND wp.pool_id = :pool_id

            WHERE w.id = :worker_id
        ');

        $q->execute(array(
            ':worker_id'    => $this->worker_id,
            ':pool_id'      => $this->pool_id
        ));

        $row = $q->fetch(PDO::FETCH_ASSOC);
        $q->closeCursor();

        if ($row === FALSE) {
            return FALSE;
        }

        $this->pool_name = $row['pool_name'];
        $this->worker_name = $row['worker_name'];

        $this->pool_username = $row['pool_username'];
        $this->pool_password = $row['pool_password'];

        $this->priority = $row['priority'];
        $this->enabled = $row['enabled'];

        return TRUE;
    }

    public function save()
    {
        $pdo = db_connect();

        $q = $pdo->prepare('
            INSERT INTO worker_pool

            (pool_id, worker_id, pool_username, pool_password, priority, enabled)
                VALUES
            (:pool_id, :worker_id, :pool_username, :pool_password, :priority, :enabled)

            ON DUPLICATE KEY UPDATE
                pool_username = :pool_username,
                pool_password = :pool_password,
                priority = :priority,
                enabled = :enabled
        ');

        $result = $q->execute(array(
            ':pool_id'          => $this->pool_id,
            ':worker_id'        => $this->worker_id,
            ':pool_username'    => $this->pool_username,
            ':pool_password'    => $this->pool_password,
            ':priority'         => $this->priority,
            ':enabled'          => $this->enabled));

        return (boolean)$result;
    }

    public function validate()
    {
        $this->canonize();

        $errors = array();

        if ($this->pool_id == 0) {
            $errors[] = 'Pool ID not set.';
        }

        if ($this->worker_id == 0) {
            $errors[] = 'Worker ID not set.';
        }

        if ($this->pool_username == '') {
            $errors[] = 'Pool username is required.';
        } elseif (strpos($this->pool_username, ':') !== FALSE) {
            $errors[] = 'Pool username may not contain a colon.';
        }

        if ($this->pool_password == '') {
            $errors[] = 'Pool password is required.';
        }

        return count($errors) ? $errors : TRUE;
    }
}

?>
