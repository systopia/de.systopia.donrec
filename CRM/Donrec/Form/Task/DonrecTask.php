<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Donrec_Form_Task_DonrecTask extends CRM_Contact_Form_Task {

  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Issue Donation Receipts'));

    $this->addElement('hidden', 'rsid');
    // TODO: instead of 'last year',.. we should have '2013', '2012', '2011'
    $options = array(
       'current_year' => ts('current year'),
       'last_year' => ts('last year'),
       'customized_period' => ts('specify period')
    );
    $this->addElement('select', 'time_period', 'Time Period:', $options);
    $this->addDateRange('donrec_contribution_horizon', '_from', '_to', ts('From:'), 'searchDate', FALSE, FALSE);

    // call the (overwritten) Form's method, so the continue button is on the right...
    CRM_Core_Form::addDefaultButtons(ts('Continue'));
  }

  function setDefaultValues() {
    $uid = CRM_Donrec_Logic_Settings::getLoggedInContactID();
    $remaining_snapshots = CRM_Donrec_Logic_Snapshot::getUserSnapshots($uid);
    if (!empty($remaining_snapshots)) {
      $remaining_snapshot = array_pop($remaining_snapshots);
      $this->getElement('rsid')->setValue($remaining_snapshot);
      $this->assign('statistic', CRM_Donrec_Logic_Snapshot::getStatistic($remaining_snapshot));
      $this->assign('remaining_snapshot', TRUE);
    }
  }

  function postProcess() {

    // process remaining snapshots if exsisting
    $rsid = empty($_REQUEST['rsid']) ? NULL : $_REQUEST['rsid'];
    if (!empty($rsid)) {

      //work on with a remaining snapshot...
      $use_remaining_snapshot = CRM_Utils_Array::value('use_remaining_snapshot', $_REQUEST, NULL);
      if (!empty($use_remaining_snapshot)) {
        CRM_Core_Session::singleton()->pushUserContext(
          CRM_Utils_System::url('civicrm/donrec/task', 'sid=' . $rsid)
        );
        return;

      // or delete all remaining snapshots of this user
      } else {
        $uid = CRM_Donrec_Logic_Settings::getLoggedInContactID();
        CRM_Donrec_Logic_Snapshot::deleteUserSnapshots($uid);
      }
    }

    // process form values and try to build a snapshot with all contributions
    // that match the specified criteria (i.e. contributions which have been
    // created between two specific dates)
    $values = $this->exportValues();
    $contactIds = implode(', ', $this->_contactIds);

    if (empty($contactIds)) {
      error_log("de.systopia.donrec: error: contact ids is empty!");
      return;
    }

    // prepare timestamps
    $raw_from_ts = $values['donrec_contribution_horizon_from'];
    $raw_to_ts = $values['donrec_contribution_horizon_to'];

    $date_from = CRM_Utils_DonrecHelper::convertDate($raw_from_ts, -1);
    $date_to = CRM_Utils_DonrecHelper::convertDate($raw_to_ts, 1);

    $query_date_limit = "";
    if ($date_from) {
      $query_date_limit .= "AND UNIX_TIMESTAMP(`receive_date`) >= $date_from";
    }
    if ($date_to) {
      $query_date_limit .= " AND UNIX_TIMESTAMP(`receive_date`) <= $date_to";
    }

    // get table- and column name
    $table_query = "SELECT `cg`.`table_name`,
                 `cf`.`column_name`
              FROM `civicrm_custom_group` AS cg,
                   `civicrm_custom_field` AS cf
              WHERE `cg`.`name` = 'zwb_donation_receipt_item'
              AND `cf`.`custom_group_id` = `cg`.`id`
              AND `cf`.`name` = 'status'";

    $results = CRM_Core_DAO::executeQuery($table_query);

    $custom_group_table = NULL;
    $status_column = NULL;
    while ($results->fetch()) {
      $custom_group_table = $results->table_name;
      $status_column = $results->column_name;
    }

    if ($custom_group_table == NULL || $status_column == NULL) {
      // something went wrong here
      error_log("de.systopia.donrec: error: custom_group_table or status_column is empty!");
        return;
    }

    // map contact ids to contributions
    $query = "SELECT `civicrm_contribution`.`id`
          FROM (`civicrm_contribution`)
          LEFT JOIN `$custom_group_table` AS b1 ON `civicrm_contribution`.`id` = `b1`.`entity_id`
          WHERE `contact_id` IN ($contactIds)
          $query_date_limit
          AND (`non_deductible_amount` < `total_amount` OR `non_deductible_amount` IS NULL)
          AND `contribution_status_id` = 1
          AND `is_test` = 0
          AND `currency` = 'EUR'
          AND (`b1`.`id` IS NULL
          OR `b1`.`$status_column` != 'ORIGINAL')
          ";
    // TODO: this query is hardcoded to EUR. Should we change this?
    // execute the query
    $result = CRM_Core_DAO::executeQuery($query);

    // build array
    $contributionIds = array();
    while ($result->fetch()) {
      $contributionIds[] = $result->id;
    }

    //set url_back as session-variable
    $session = CRM_Core_Session::singleton();
    $session->set('url_back', CRM_Utils_System::url('civicrm/contact/search', "reset=1"));

    // try to create a snapshot and redirect depending on the result (conflict)
    $result = CRM_Donrec_Logic_Snapshot::create($contributionIds, CRM_Donrec_Logic_Settings::getLoggedInContactID());

    if (!empty($result['intersection_error'])) {
      CRM_Core_Session::singleton()->pushUserContext(
        CRM_Utils_System::url('civicrm/donrec/task', 'conflict=1' . 'sid=' . $result['snapshot']->getId() . '&ccount=' . count($this->_contactIds)));
    }elseif (empty($result['snapshot'])) {
      CRM_Core_Session::setStatus(ts('There are no selectable contributions for these contacts in the selected time period.'), ts('Warning'), 'warning');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/search', 'reset=1'));
    }else{
      CRM_Core_Session::singleton()->pushUserContext(
        CRM_Utils_System::url('civicrm/donrec/task', 'sid=' . $result['snapshot']->getId() . '&ccount=' . count($this->_contactIds))
      );
    }
  }
}
