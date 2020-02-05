<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

/**
* This class handles form input for the contribution creation task
*/
class CRM_Donrec_Page_Staging extends CRM_Core_Page {

  function run() {
    CRM_Utils_System::setTitle(E::ts('Issue Donation Receipts'));

    // Since 4.6 the css-class crm-summary-row lives in contactSummary.css
    // instead of civicrm.css
    if (version_compare(CRM_Utils_System::version(), '4.6', '>=')) {
      CRM_Core_Resources::singleton()->addStyleFile('civicrm', 'css/contactSummary.css');
    }

    $id = empty($_REQUEST['sid'])?NULL:$_REQUEST['sid'];
    $ccount = empty($_REQUEST['ccount'])?NULL:$_REQUEST['ccount'];
    $selected_exporter = empty($_REQUEST['exporters'])?NULL:$_REQUEST['exporters'];
    $origin = empty($_REQUEST['origin'])?NULL:$_REQUEST['origin'];

    // working on a test-snapshot
    $from_test = empty($_REQUEST['from_test'])?NULL:$_REQUEST['from_test'];
    $this->assign('from_test', $from_test);

    // add statistic
    if (!empty($id)) {
      $statistic = CRM_Donrec_Logic_Snapshot::getStatistic($id);
      $statistic['requested_contacts'] = $ccount;
      $this->assign('statistic', $statistic);
    }

    if (!empty($selected_exporter)) {
      $this->assign('selected_exporter', $selected_exporter);
    }

    // check which button was clicked
    // called when the 'abort' button was selected
    if(!empty($_REQUEST['donrec_abort']) || !empty($_REQUEST['donrec_abort_by_admin'])) {
      $by_admin = !empty($_REQUEST['donrec_abort_by_admin']);

      // we need a (valid) snapshot id here
      if (empty($id)) {
        $this->assign('error', E::ts('No snapshot id has been provided!'));
        $this->assign('url_back', CRM_Utils_System::url('civicrm/donrec/task', NULL, FALSE, NULL, TRUE, FALSE, TRUE));
      }else{
        $snapshot = CRM_Donrec_Logic_Snapshot::get($id);
        if (empty($snapshot)) {
          $this->assign('error', E::ts('Invalid snapshot id!'));
          $this->assign('url_back', CRM_Utils_System::url('civicrm/donrec/task', NULL, FALSE, NULL, TRUE, FALSE, TRUE));
        }else{
          // delete the snapshot and redirect to search form
          $snapshot->delete();
          if ($by_admin) {
            $return_id = $_REQUEST['return_to'];
            CRM_Core_Session::setStatus(E::ts('The older snapshot has been deleted. You can now proceed.'), E::ts('Warning'), 'warning');
            CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/donrec/task', "sid=$return_id&ccount=$ccount", FALSE, NULL, TRUE, FALSE, TRUE));
          }else{
            CRM_Core_Session::setStatus(E::ts('The previously created snapshot has been deleted.'), E::ts('Warning'), 'warning');
            if (!empty($_REQUEST['origin'])) {
              CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$origin&selectedChild=donation_receipts", FALSE, NULL, TRUE, FALSE, TRUE));
             }else{
               CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/search', NULL, FALSE, NULL, TRUE, FALSE, TRUE));
             }
          }
        }
      }
    }elseif (!empty($_REQUEST['donrec_testrun'])) {
      // called when the 'test run' button was selected
      $bulk = (int)($_REQUEST['donrec_type'] == "2");
      $exporters = $_REQUEST['result_type'];
      // at least one exporter has to be selected
      if (empty($exporters)) {
        $this->assign('error', E::ts('Missing exporter!'));
        $this->assign('url_back', CRM_Utils_System::url('civicrm/donrec/task', NULL, FALSE, NULL, TRUE, FALSE, TRUE));
      }else{
        //on testrun we want to return to the stats-page instead of the contact-search-page
        //but we must not overwrite the url_back-var
        $session = CRM_Core_Session::singleton();
        $session->set('url_back_test', CRM_Utils_System::url('civicrm/donrec/task', "sid=$id&ccount=$ccount&from_test=1&origin=$origin", FALSE, NULL, TRUE, FALSE, TRUE));

        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/donrec/runner', "sid=$id&bulk=$bulk&exporters=$exporters", FALSE, NULL, TRUE, FALSE, TRUE));
      }
    }elseif (!empty($_REQUEST['donrec_run'])) {
      // issue donation receipts case
      $bulk = (int)($_REQUEST['donrec_type'] == "2");
      $exporters = $_REQUEST['result_type'];
      // at least one exporter has to be selected
      if (empty($exporters)) {
        $this->assign('error', E::ts('Missing exporter!'));
        $this->assign('url_back', CRM_Utils_System::url('civicrm/donrec/task', NULL, FALSE, NULL, TRUE, FALSE, TRUE));
      }else{
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/donrec/runner', "sid=$id&bulk=$bulk&final=1&exporters=$exporters", FALSE, NULL, TRUE, FALSE, TRUE));
      }
    }elseif (!empty($_REQUEST['conflict'])) {
      // called when a snapshot conflict has been detected
      $conflict = CRM_Donrec_Logic_Snapshot::hasIntersections();
      if (!$conflict) {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/donrec/task', "sid=$id&ccount=$ccount", FALSE, NULL, TRUE, FALSE, TRUE));
      }

      $this->assign('conflict_error', $conflict[1]);
      $this->assign('url_back', CRM_Utils_System::url('civicrm/contact/search', NULL, FALSE, NULL, TRUE, FALSE, TRUE));

      if(CRM_Core_Permission::check('delete receipts')) {
        $this->assign('is_admin', 1);
        $this->assign('return_to', $conflict[2][0]);
        $this->assign('formAction', CRM_Utils_System::url( 'civicrm/donrec/task',
                                "sid=" . $conflict[1][0] . "&ccount=$ccount",
                                false, null, false, FALSE, TRUE ));
      }
    }else{
      if (empty($id)) {
        $this->assign('error', E::ts('No snapshot id has been provided!'));
        $this->assign('url_back', CRM_Utils_System::url('civicrm/contact/search', '', FALSE, NULL, TRUE, FALSE, TRUE));
      }else{
        // get supported exporters
        $exp_array = array();
        $exporters = CRM_Donrec_Logic_Exporter::listExporters();
        foreach ($exporters as $exporter) {
          $classname = CRM_Donrec_Logic_Exporter::getClassForExporter($exporter);
          // check requirements
          $instance = new $classname();
          $result = $instance->checkRequirements(CRM_Donrec_Logic_Snapshot::get($id)->getProfile());
          $is_usable = TRUE;
          $error_msg = "";
          $info_msg = "";

          if ($result['is_error']) {
            $is_usable = FALSE;
            $error_msg = $result['message'];
          }else if(!empty($result['message'])){
            $info_msg = $result['message'];
          }

          $exp_array[] = array($exporter, $classname::name(), $classname::htmlOptions(), $is_usable, $error_msg, $info_msg);
        }

        $this->assign('exporters', $exp_array);
        $this->assign('formAction', CRM_Utils_System::url( 'civicrm/donrec/task',
                                "sid=$id&ccount=$ccount&origin=$origin",
                                false, null, false, FALSE, TRUE ));
      }
    }

    parent::run();
  }
}
