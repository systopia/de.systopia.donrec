<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This class represents the general contribution selector
 */
class CRM_Donrec_Logic_Selector {

  /** 
   * Build and run the query to select all contributions
   * matching the criteria, and try to create a snapshot
   *
   * @return snapshot creation result/error
   */
  public static function createSnapshot($values) {
    // prepare timestamps
    $raw_from_ts = $values['donrec_contribution_horizon_from'];
    $raw_to_ts = $values['donrec_contribution_horizon_to'];

    $date_from = CRM_Utils_DonrecHelper::convertDate($raw_from_ts, -1);
    $date_to = CRM_Utils_DonrecHelper::convertDate($raw_to_ts, 1);

    $formatted_date_from = date('Y-m-d H:i:s', $date_from);
    $formatted_date_to = date('Y-m-d H:i:s', $date_to);

    $query_date_limit = "";
    if ($date_from) {
      $query_date_limit .= "AND `receive_date` >= '$formatted_date_from'";
    }
    if ($date_to) {
      $query_date_limit .= " AND `receive_date` <= '$formatted_date_to'";
    }

    $currency = $values['donrec_contribution_currency'];

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
      CRM_Core_Error::debug_log_message("de.systopia.donrec: error: custom_group_table or status_column is empty!");
      return array();
    }

    // calculate main selector clause
    if (!empty($values['contact_id'])) {
      $contact_id = (int) $values['contact_id'];
      $main_selector = "`contact_id` = $contact_id";
    } elseif (!empty($values['contact_ids'])) {
      $contact_ids = implode(',', $values['contact_ids']);
      $main_selector = "`contact_id` IN ($contact_ids)";
    } elseif (!empty($values['contribution_ids'])) {
      $contribution_ids = implode(',', $values['contribution_ids']);
      $main_selector = "`civicrm_contribution`.`id` IN ($contribution_ids)";
    } else {
      CRM_Core_Error::debug_log_message("de.systopia.donrec: error: no selector data found in params!");
      $main_selector = "FALSE";
    }

    // get financial type selector clause
    $profile = new CRM_Donrec_Logic_Profile($values['profile']);
    $financialTypeClause = $profile->getContributionTypesClause();


    // run the main query
    $query = "SELECT `civicrm_contribution`.`id`
              FROM (`civicrm_contribution`)
              LEFT JOIN `$custom_group_table` AS existing_receipt
                  ON  `civicrm_contribution`.`id` = existing_receipt.`entity_id`
                  AND existing_receipt.`$status_column` = 'ORIGINAL'
              WHERE
                  ($main_selector)
                  $query_date_limit
                  AND $financialTypeClause
                  AND (`non_deductible_amount` = 0 OR `non_deductible_amount` IS NULL)
                  AND `contribution_status_id` = 1
                  AND `is_test` = 0
                  AND `currency` = '$currency'
                  AND existing_receipt.`entity_id` IS NULL;";

    // execute the query
    $result = CRM_Core_DAO::executeQuery($query);

    // build array
    $contributionIds = array();
    while ($result->fetch()) {
      $contributionIds[] = $result->id;
    }

    // finally, build the snapshot with it
    return CRM_Donrec_Logic_Snapshot::create( $contributionIds, 
                                              CRM_Donrec_Logic_Settings::getLoggedInContactID(), 
                                              $formatted_date_from, 
                                              $formatted_date_to,
                                              $values['profile']);
  }
}
