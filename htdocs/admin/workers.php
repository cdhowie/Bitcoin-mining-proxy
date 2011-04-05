<?php

require_once(dirname(__FILE__) . '/../common.inc.php');
require_once(dirname(__FILE__) . '/../admin/controller.inc.php');
require_once(dirname(__FILE__) . '/../views/admin/workers.view.php');

class AdminWorkersController extends AdminController
{
    public function indexDefaultView()
    {
        $viewdata = array();

        $pdo = db_connect();

        $viewdata['workers'] = db_query($pdo, '
            SELECT
                w.id AS id,
                w.name AS name,
                w.password AS password,
                COUNT(wp.pool_id) AS pools

            FROM worker w

            LEFT OUTER JOIN worker_pool wp
            ON w.id = wp.worker_id

            GROUP BY w.id

            ORDER BY name
        ');

        return new AdminWorkersView($viewdata);
    }

    public function newGetView()
    {
        return new AdminWorkersNewView(array());
    }

    public function newPostView()
    {
        $viewdata = array(
            'form'  => $_POST
        );

        $name = trim($_POST['name']);
        $password = trim($_POST['password']);

        $valid = true;

        if ($name == '') {
            $_SESSION['tempdata']['errors'][] = 'Worker name is required.';
            $valid = false;
        } elseif (strpos($name, ':') !== FALSE) {
            $_SESSION['tempdata']['errors'][] = 'Worker name may not contain a colon.';
            $valid = false;
        }

        if ($password == '') {
            $_SESSION['tempdata']['errors'][] = 'Worker password is required.';
            $valid = false;
        }

        if ($valid) {
            $pdo = db_connect();

            $q = $pdo->prepare('
                INSERT INTO worker

                (name, password)
                    VALUES
                (:name, :password)
            ');

            $result = $q->execute(array(
                ':name'     => $name,
                ':password' => $password
            ));

            if (!$result) {
                $_SESSION['tempdata']['errors'][] = 'Unable to create worker.  A worker with the same name probably exists.';
                $valid = false;
            }
        }

        if (!$valid) {
            return new AdminWorkersNewView($viewdata);
        }

        $_SESSION['tempdata']['info'][] = 'Worker created.';

        return new RedirectView('/admin/workers.php');
    }

    public function deletePostView()
    {
        $id = (int)$_POST['id'];

        if ($id == 0) {
            $_SESSION['tempdata']['errors'][] = 'Invalid worker ID.';

            return new RedirectView('/admin/workers.php');
        }

        $pdo = db_connect();

        $q = $pdo->prepare('
            SELECT COUNT(*) AS count

            FROM worker_pool wp

            WHERE worker_id = :worker_id
        ');

        $q->execute(array(':worker_id' => $id));

        if ($q->fetchColumn() != 0) {
            $_SESSION['tempdata']['errors'][] = 'Worker must be removed from all pools before it can be deleted.';
            return new RedirectView('/admin/workers.php');
        }

        $q->closeCursor();

        $q = $pdo->prepare('
            DELETE FROM worker WHERE id = :worker_id
        ');

        $q->execute(array(':worker_id' => $id));

        if (!$q->rowCount()) {
            $_SESSION['tempdata']['errors'][] = 'Worker not found.';
            return new RedirectView('/admin/workers.php');
        }

        $q = $pdo->prepare('
            DELETE FROM submitted_work WHERE worker_id = :worker_id
        ');

        $q->execute(array(':worker_id' => $id));

        $q = $pdo->prepare('
            DELETE FROM work_data WHERE worker_id = :worker_id
        ');

        $q->execute(array(':worker_id' => $id));

        $_SESSION['tempdata']['info'][] = 'Worker deleted.';
        return new RedirectView('/admin/workers.php');
    }
}

MvcEngine::run(new AdminWorkersController());

?>
