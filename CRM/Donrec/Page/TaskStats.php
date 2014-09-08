<?php

require_once 'CRM/Core/Page.php';

class CRM_Donrec_Form_Task_Stats extends CRM_Core_Form {
  function preProcess() {
      parent::preProcess();
    }

    function buildQuickForm() {
      $resultFormats = array(1 => ts('DUMMY #1'), 2 => ts('DUMMY #2'));
      $this->addRadio('result_format', ts('Result format'), $resultFormats, NULL, '<br/>');  
      //$this->setDefaults(array('donrec_type' => 1));
    }

    function run() {
      $status = empty($_REQUEST['status'])?'main':$_REQUEST['status'];

      if ($status == 'abort') {
        $this->assign('error', "Cancelled");
      }else{
        $this->assign('url_testrun', CRM_Utils_System::url('civicrm/donrec/testrun'));
        $this->assign('url_run', CRM_Utils_System::url('civicrm/donrec/run'));
        $this->assign('url_abort', CRM_Utils_System::url('civicrm/donrec/task', 'status=abort'));
      }
      parent::run();
    }
}
