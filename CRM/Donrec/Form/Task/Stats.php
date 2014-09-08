<?php

class CRM_Donrec_Form_Task_Stats extends CRM_Core_Form {
  function preProcess() {
    $status = empty($_REQUEST['status'])?'main':$_REQUEST['status'];
    $id = empty($_REQUEST['sid'])?NULL:$_REQUEST['sid'];

      if ($status == 'abort') {
        if (empty($id)) {
          $this->assign('error', ts('No snapshot id has been provided!'));
          $this->assign('url_back', CRM_Utils_System::url('civicrm/donrec/task', 'status=main'));
        }else{
          $snapshot = CRM_Donrec_Logic_Snapshot::get($id);
          if (empty($snapshot)) {
            $this->assign('error', ts('Invalid snapshot id!'));
            $this->assign('url_back', CRM_Utils_System::url('civicrm/donrec/task', 'status=main'));
          }else{
            // delete the snapshot and redirect to search form
            $snapshot->delete();
            CRM_Core_Session::setStatus(ts('The previously created snapshot has been deleted.'), ts('Warning'), 'warning');
            CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/search'));
          }
        }
      }else{
        if (empty($id)) {
          $this->assign('error', ts('No snapshot id has been provided!'));
          $this->assign('url_back', CRM_Utils_System::url('civicrm/contact/search', ''));
        }else{
          $donrecTypes = array(1 => ts('single'), 2 => ts('multi'));
          $this->addRadio('donrec_type', ts('Donation receipt type'), $donrecTypes);
          $this->assign('url_testrun', CRM_Utils_System::url('civicrm/donrec/runner' , "sid=$id&test=true"));
          $this->assign('url_run', CRM_Utils_System::url('civicrm/donrec/runner', "sid=$id"));
          $this->assign('url_abort', CRM_Utils_System::url('civicrm/donrec/task', "status=abort&sid=$id"));
          $this->setDefaults(array('result_format' => 1, 'donrec_type' => 1));
        }
      }

  }

  function postProcess() {
    $values = $this->exportValues();
    error_log(print_r($values, TRUE));
  }

  function buildQuickForm() {
    $resultFormats = array(1 => ts('DUMMY #1'), 2 => ts('DUMMY #2'));
    $this->addRadio('result_format', ts('Result format'), $resultFormats, NULL, '<br/>'); 
    $donrecTypes = array(1 => ts('single'), 2 => ts('multi'));
    $this->addRadio('donrec_type', ts('Donation receipt type'), $donrecTypes); 
    $this->addElement('submit', 'donrec_run', ts('Issue Donation Receipt'));
    $this->addElement('submit', 'donrec_testrun', ts('Test Run'));
    $this->addElement('submit', 'donrec_abort', ts('Abort'));

  }

  function addRules() {
    //$this->addRule('donrec_type', ts('Please select a donation receipt type format'), 'required');
    //$this->addRule('result_format', ts('Please select a result format'), 'required');
  }

}
