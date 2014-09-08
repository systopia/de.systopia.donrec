<?php

require_once 'CRM/Core/Page.php';

class CRM_Donrec_Page_Runner extends CRM_Core_Page {
  function run() {
    // TODO: remove: create snapshot
    CRM_Core_DAO::executeQuery('TRUNCATE TABLE `civicrm_donrec_snapshot`;');
    $contributions = array(13495, 13480, 13491);
    $user_id = CRM_Core_Session::singleton()->get('userID');
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, $user_id);
    $_REQUEST['sid'] = $snapshot->getId();



    // extract the parameters
    $parameters = array();
    $parameters['test'] = empty($_REQUEST['final'])?1:0;
    $parameters['bulk'] = empty($_REQUEST['bulk'])?0:1;
    $parameters['exporters'] = empty($_REQUEST['exporters'])?array('Dummy'):explode(',', $_REQUEST['exporters']);

    // get the snapshot_id
    if (empty($_REQUEST['sid'])) {
      $this->assign('error', ts("No snapshot ID given. Please call this page from a proper selection."));
    } else {
      // Init the engine
      $sid = (int) $_REQUEST['sid'];
      $engine = new CRM_Donrec_Logic_Engine();
      $engine_error = $engine->init($sid, $parameters);
      if ($engine_error) {
        $this->assign('error', $engine_error);
      } else {
        $this->assign('sid', $sid);
        if ($parameters['test']) {
          // if this is a test-run: restart
          $engine->resetTestRun();
        }
      }
    }

    parent::run();
  }
}
