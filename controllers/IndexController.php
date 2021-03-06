<?php

class IndexController extends Controller {

  public function __construct() {
    parent::__construct();
    $this->model = 'TodoModel';
  }

  public function get_index() {
    $todos = null;
    $dones = null;
    if (isLoggedIn()) {
      $todos = $this->model::findAllCurrentToDos($_SESSION['user']->id);
      $dones = $this->model::findAllDoneToDos($_SESSION['user']->id);
    }
    $this->view->renderTemplate(
      "views/index.php",
      array(
        'title' => 'To Do List',
        'todos' => $todos,
        'dones' => $dones
      )
    );
  }
}
?>