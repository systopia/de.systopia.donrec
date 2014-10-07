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
  function preProcess() {
    parent::preProcess();
  }

  function buildQuickForm() {
    $this->addDateRange('donrec_contribution_horizon', '_from', '_to', ts('From:'), 'searchDate', FALSE, FALSE);  
    $this->addDefaultButtons(ts('Continue'));  
  }
  
  function postProcess() {
    // process form values and try to build a snapshot with all contributions
    // that match the specified criteria (i.e. contributions which have been
    // created between two specific dates)
    $values = $this->exportValues();
    $contactIds = implode(', ', $this->_contactIds);

    // prepare timestamps
    $raw_from_ts = $values['donrec_contribution_horizon_from'];
    $raw_to_ts = $values['donrec_contribution_horizon_to'];
    
    $date_from = CRM_Utils_DonrecHelper::convertDate($raw_from_ts);
    $date_to = CRM_Utils_DonrecHelper::convertDate($raw_to_ts);

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
          AND (`non_deductible_amount` < `total_amount` OR non_deductible_amount IS NULL)
          AND `contribution_status_id` = 1
          AND (`b1`.`id` IS NULL 
          OR `b1`.`$status_column` NOT IN ('ORIGINAL', 'COPY'))
          ";
    
    // execute the query
    $result = CRM_Core_DAO::executeQuery($query);

    // build array
    $contributionIds = array();
    while ($result->fetch()) {
      $contributionIds[] = $result->id;
    }

    // try to create a snapshot and redirect depending on the result (conflict)
    $result = CRM_Donrec_Logic_Snapshot::create($contributionIds, CRM_Core_Session::getLoggedInContactID());

    if (!empty($result['intersection_error'])) {
      CRM_Core_Session::singleton()->pushUserContext(
        CRM_Utils_System::url('civicrm/donrec/task', 'conflict=1'));
    }elseif (empty($result['snapshot'])) {
      CRM_Core_Session::setStatus(ts('There are no contributions for this contact that can be used to issue donation receipts.'), ts('Warning'), 'warning');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$contactId"));
    }else{
      CRM_Core_Session::singleton()->pushUserContext(
        CRM_Utils_System::url('civicrm/donrec/task', 'sid=' . $result['snapshot']->getId()));
    }
  }
}
