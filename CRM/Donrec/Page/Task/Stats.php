<?php

class CRM_Donrec_Page_Task_Stats extends CRM_Core_Page {
  function run() {
    $id = empty($_REQUEST['sid'])?NULL:$_REQUEST['sid'];

    // check which button was clicked
    if(!empty($_REQUEST['donrec_abort'])) {
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
          CRM_Core_Session::setStatus(ts('The previously created snapshot has been deleted.'), ts('Warning'), 'warning');
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/search'));
        }
      }
    }elseif (!empty($_REQUEST['donrec_testrun'])) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/donrec/runner', "sid=$id&test=1")); 
    }elseif (!empty($_REQUEST['donrec_run'])) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/donrec/runner', "sid=$id"));
    }else{
      if (empty($id)) {
        $this->assign('error', ts('No snapshot id has been provided!'));
        $this->assign('url_back', CRM_Utils_System::url('civicrm/contact/search', ''));
      }else{
        $this->assign('formAction', CRM_Utils_System::url( 'civicrm/donrec/task',
                                "sid=$id",
                                false, null, false,true ));
      }
    }

    parent::run();
  }
}
