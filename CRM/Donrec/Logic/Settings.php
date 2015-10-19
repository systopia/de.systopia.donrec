<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2015 SYSTOPIA                       |
| Author: N. Bochan (bochan -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This class holds all settings related functions
 */
class CRM_Donrec_Logic_Settings {

  /**
  * Returns all financial type ids that should be used by the donation receipt generation engine.
  * @return array
  */
  public static function getContributionTypes() {
    // get settings
    $raw_string = CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'contribution_types');
    $get_all = ($raw_string == 'all');

    if (!$get_all) {
      $id_array = explode(',', $raw_string);
      if ($id_array[0] == NULL) {
        unset($id_array[0]);
      }
    }else{
      $id_array = array();
    }

    // get all deductible ids
    $financial_type_ids = array((int)$get_all);
    $query = "SELECT `id`, `name`, `is_deductible` FROM `civicrm_financial_type` WHERE `is_active` = 1;";
    $results = CRM_Core_DAO::executeQuery($query);
    while ($results->fetch()) {
      $tmp = array($results->id, $results->name, $results->is_deductible, 0);
      // select all ids that are either deductible or part of our settings array
      if(($get_all && $results->is_deductible) || in_array($results->id, $id_array)) {
        $tmp[3] = 1;
      }
      $financial_type_ids[] = $tmp;
    }
    return $financial_type_ids;
  }

  /**
   * Similar to ::getContributionTypes(), but will
   * construct and return an SQL clause
   * that works in the WHERE clause on 
   * civicrm_contribution queries.
   *
   * @return SQL clause 
   * @author N. Bochan / B. Endres
   */
  static function getContributionTypesClause() {
    // get all valid financial type ids
    $financialTypeIds = array();
    $validContribTypes = CRM_Donrec_Logic_Settings::getContributionTypes();
    for($i=1;$i<count($validContribTypes);$i++) {
      // this type is valid if the flag is set
      if($validContribTypes[$i][3] == 1) {
        $financialTypeIds[] = $validContribTypes[$i][0];
      }
    }

    // construct the SQL clause
    if (empty($financialTypeIds)) {
      // no contribution types allowed
      return 'FALSE';
    } else {
      // otherwise the clause consists of the financial type IDs
      $financialTypeIdsString = implode(',', $financialTypeIds);
      return "`financial_type_id` IN ($financialTypeIdsString)";
    }
  }

  /**
  * Returns location_types used for the legal- and postal-address and their fallbacks.
  * @return array
  */
  public static function getLocationTypes() {
    $location_types['legal']['address'] = CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'legal_address');
    $location_types['legal']['fallback'] = CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'legal_address_fallback');
    $location_types['postal']['address'] = CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'postal_address');
    $location_types['postal']['fallback'] = CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'postal_address_fallback');
    return $location_types;
  }

  /**
   * get the setting on whether to save the original PDF file
   *
   * @return bool
   */
  public static function saveOriginalPDF() {
    return CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'store_original_pdf');
  }

  /**
   * get the default template ID
   *
   * @return int
   */
  public static function getDefaultTemplate() {
    return CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'default_template');
  }

  /**
   * set the default template ID
   */
  public static function setDefaultTemplate($id) {
    CRM_Core_BAO_Setting::setItem($id,'Donation Receipt Settings', 'default_template');
  }

  /**
   * get the chunk size
   *
   * @return int
   */
  public static function getChunkSize() {
    $packet_size = (int) CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'packet_size');
    if ($packet_size >= 1) {
      return $packet_size;
    } else {
      return 1;
    }
  }

  /**
   * Retrieve contact id of the logged in user
   * @return integer | NULL contact ID of logged in user
   */
  static function getLoggedInContactID() {
    $session = CRM_Core_Session::singleton();
    if (!is_numeric($session->get('userID'))) {
      return NULL;
    }
    return $session->get('userID');
  }
}
