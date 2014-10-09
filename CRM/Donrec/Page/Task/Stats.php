<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/


/**
* This class handles form input for the contribution creation task
*/
class CRM_Donrec_Page_Task_Stats extends CRM_Core_Page {
  function run() {
    $id = empty($_REQUEST['sid'])?NULL:$_REQUEST['sid'];

    // check which button was clicked

    // called when the 'abort' button was selected
    if(!empty($_REQUEST['donrec_abort']) || !empty($_REQUEST['donrec_abort_by_admin'])) {
      $by_admin = !empty($_REQUEST['donrec_abort_by_admin']);
      $return_id = $_REQUEST['return_to'];

      // we need a (valid) snapshot id here
      if (empty($id)) {
        $this->assign('error', ts('No snapshot id has been provided!'));
        $this->assign('url_back', CRM_Utils_System::url('civicrm/donrec/task'));
      }else{
        $snapshot = CRM_Donrec_Logic_Snapshot::get($id);
        if (empty($snapshot)) {
          $this->assign('error', ts('Invalid snapshot id!'));
          $this->assign('url_back', CRM_Utils_System::url('civicrm/donrec/task'));
        }else{
          // delete the snapshot and redirect to search form
          $snapshot->delete();
          if ($by_admin) {
            CRM_Core_Session::setStatus(ts('The older snapshot has been deleted. You can now proceed.'), ts('Warning'), 'warning');
            CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/donrec/task', "sid=$return_id"));
          }else{
            CRM_Core_Session::setStatus(ts('The previously created snapshot has been deleted.'), ts('Warning'), 'warning');
            CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/search'));
          }
        }
      }
    }elseif (!empty($_REQUEST['donrec_testrun'])) {
      // called when the 'test run' button was selected
      $bulk = (int)($_REQUEST['donrec_type'] == "2");
      $exporters = $_REQUEST['result_type'];
      // at least one exporter has to be selected
      if (empty($exporters)) {
        $this->assign('error', ts('Missing exporter!'));
        $this->assign('url_back', CRM_Utils_System::url('civicrm/donrec/task'));
      }else{
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/donrec/runner', "sid=$id&bulk=$bulk&exporters=$exporters")); 
      }
    }elseif (!empty($_REQUEST['donrec_run'])) {
      // issue donation receipts case
      $bulk = (int)($_REQUEST['donrec_type'] == "2");
      $exporters = $_REQUEST['result_type'];
      // at least one exporter has to be selected
      if (empty($exporters)) {
        $this->assign('error', ts('Missing exporter!'));
        $this->assign('url_back', CRM_Utils_System::url('civicrm/donrec/task'));
      }else{
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/donrec/runner', "sid=$id&bulk=$bulk&final=1&exporters=$exporters"));
      }
    }elseif (!empty($_REQUEST['conflict'])) {
      // called when a snapshot conflict has been detected
      $conflict = CRM_Donrec_Logic_Snapshot::hasIntersections();
      if (!$conflict) {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/donrec/task', "sid=$id"));
      }

      $this->assign('conflict_error', $conflict[1]);
      $this->assign('url_back', CRM_Utils_System::url('civicrm/contact/search'));

      if(CRM_Core_Permission::check('administer CiviCRM')) {
        $this->assign('is_admin', CRM_Utils_System::url('civicrm/contact/search'));
        $this->assign('return_to', $conflict[2][0]);
        $this->assign('formAction', CRM_Utils_System::url( 'civicrm/donrec/task',
                                "sid=" . $conflict[1][0],
                                false, null, false,true ));
      }
    }else{
      if (empty($id)) {
        $this->assign('error', ts('No snapshot id has been provided!'));
        $this->assign('url_back', CRM_Utils_System::url('civicrm/contact/search', ''));
      }else{
        // get supported exporters
        $exp_array = array();
        $exporters = CRM_Donrec_Logic_Exporter::listExporters();
        foreach ($exporters as $exporter) {
          $classname = CRM_Donrec_Logic_Exporter::getClassForExporter($exporter);
          $exp_array[] = array($exporter, $classname::name(), $classname::htmlOptions());
        }

        $snapshot = CRM_Donrec_Logic_Snapshot::get($id);
        $this->assign('statistic', $snapshot->getStatistic());
        $this->assign('exporters', $exp_array);
        $this->assign('formAction', CRM_Utils_System::url( 'civicrm/donrec/task',
                                "sid=$id",
                                false, null, false,true ));
      }
    }

    parent::run();
  }
}
