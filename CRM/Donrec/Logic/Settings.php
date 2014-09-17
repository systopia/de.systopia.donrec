<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)       |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
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
  * @return bool
  */
  public static function saveOriginalPDF() {
    return CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'store_original_pdf');
  }

  /**
  * @return id
  */
  public static function getDefaultTemplate() {
    return CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'default_template');
  }

  public static function setDefaultTemplate($id) {
    CRM_Core_BAO_Setting::setItem($id,'Donation Receipt Settings', 'default_template');
  }
}
