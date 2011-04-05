<?php

require_once(dirname(__FILE__) . '/../common.inc.php');
require_once(dirname(__FILE__) . '/../admin/controller.inc.php');
require_once(dirname(__FILE__) . '/../models/worker.inc.php');
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
        return new AdminWorkerNewEditView(array('worker' => new WorkerModel()));
    }

    public function newPostView()
    {
        $worker = new WorkerModel($_POST);

        $result = $worker->validate();
        $valid = $result === TRUE;

        if ($valid) {
            $pdo = db_connect();

            if ($worker->id) {
                $q = $pdo->prepare('
                    UPDATE worker

                    SET name = :name,
                        password = :password

                    WHERE id = :id
                ');

                $q_args = array(
                    ':name'     => $worker->name,
                    ':password' => $worker->password,
                    ':id'       => $worker->id
                );
            } else {
                $q = $pdo->prepare('
                    INSERT INTO worker

                    (name, password)
                        VALUES
                    (:name, :password)
                ');

                $q_args = array(
                    ':name'     => $worker->name,
                    ':password' => $worker->password
                );
            }

            $result = $q->execute($q_args);

            if (!$result) {
                $_SESSION['tempdata']['errors'][] =
                    sprintf('Unable to %s worker.  A worker with the same name probably exists.',
                        $worker->id ? 'modify' : 'create');

                $valid = false;
            }
        } else {
            $_SESSION['tempdata']['errors'] =
                array_merge((array)$_SESSION['tempdata']['errors'], $result);
        }

        if (!$valid) {
            return new AdminWorkerNewEditView(array('worker' => $worker));
        }

        $_SESSION['tempdata']['info'][] = $worker->id ? 'Changes saved.' : 'Worker created.';

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

    public function editGetView()
    {
        $id = (int)$_GET['id'];

        if ($id == 0) {
            return new RedirectView('/admin/workers.php');
        }

        $pdo = db_connect();

        $q = $pdo->prepare('
            SELECT id, name, password

            FROM worker

            WHERE id = :worker_id
        ');

        $q->execute(array(':worker_id' => $id));

        $row = $q->fetch(PDO::FETCH_ASSOC);

        if ($row === FALSE) {
            $_SESSION['tempdata']['errors'][] = 'Worker not found.';
            return new RedirectView('/admin/workers.php');
        }

        return new AdminWorkerNewEditView(array('worker' => new WorkerModel($row)));
    }

    public function editPostView()
    {
        return $this->newPostView();
    }
}

MvcEngine::run(new AdminWorkersController());

?>
