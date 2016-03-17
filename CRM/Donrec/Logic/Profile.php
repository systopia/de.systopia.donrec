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
 * Profiles wrap a set of configuration values
 * for the donation receipt generation, e.g.
 * financial_types, template, copy/draft texts, address types, etc.
 */
class CRM_Donrec_Logic_Profile {

  protected static $SETTINGS_PROFILE_GROUP = "Donation Receipt Profiles";

  /** ID if the setting entity, where the values are stored */
  protected $profile_name = NULL;
  protected $data = NULL;

  /** 
   * load setting entity with given ID
   */
  public function __construct($profile_name) {
    $data = CRM_Core_BAO_Setting::getItem(self::$SETTINGS_PROFILE_GROUP, $profile_name);
    if ($data==NULL || !is_array($data)) {
      // this setting doesn't exist yet or is malformed
      $this->data = array(
        // TODO: add default values
        );
    } else {
      $this->data = $data;
    }
    $this->profile_name = $profile_name;
  }

  /**
   * update the internal data with the given set
   * @param $data array(key => value)
   */
  public function update($data) {
    $this->data = $data;
  }

  /**
   * stores the setting as the unterlying settings entity
   */
  public function save() {
    CRM_Core_BAO_Setting::setItem($this->data, self::$SETTINGS_PROFILE_GROUP, $this->profile_name);
  }

  /**
   * get the key's value
   */
  public function get($key) {
    return CRM_Utils_Array::value($key, $this->data, NULL);
  }

  /**
   * get the data object
   */
  public function getData() {
    return $this->data;
  } 

  /**
   * return all existing profiles
   * 
   * @return array(name => name)
   */
  public static function getAllNames() {
    $allProfiles = array('Default' => 'Default');

    // FIXME: is there a better way than a SQL query?
    $sql = "SELECT name FROM civicrm_setting WHERE group_name = %1;";
    $sql_params = array(1 => array(self::$SETTINGS_PROFILE_GROUP, 'String'));
    $query = CRM_Core_DAO::executeQuery($sql, $sql_params);
    while ($query->fetch()) {
      $allProfiles[$query->name] = $query->name;
    }

    // TODO: remove
    return $allProfiles;
  }

  /**
   * return all existing profiles
   * 
   * @return array(name => profile)
   */
  public static function getAll() {
    $allProfileNames = self::getAllNames();
    $allProfiles = array();

    foreach ($allProfileNames as $profile_name) {
      $allProfiles[$profile_name] = new CRM_Donrec_Logic_Profile($profile_name);
    }

    return $allProfiles;
  }

  /**
   * return all existing data sets
   * 
   * @return array(name => array(profile_data))
   */
  public static function getAllData() {
    $profiles = self::getAll();
    $profile2data = array();
    foreach ($profiles as $profile_name => $profile) {
      $profile2data[$profile_name] = $profile->getData();
    }
    return $profile2data;
  }


  /**
   * Returns all financial type ids that should be used by the donation receipt generation engine.
   * @return array
   */
  public function getContributionTypes() {
    // get settings
    $raw_string = $this->get('contribution_types');
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
  public function getContributionTypesClause() {
    // get all valid financial type ids
    $financialTypeIds = array();
    $validContribTypes = $this->getContributionTypes();
    for($contribution_type_id=1; $contribution_type_id < count($validContribTypes); $contribution_type_id++) {
      // this type is valid if the flag is set
      if($validContribTypes[$contribution_type_id][3] == 1) {
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
  public function getLocationTypes() {
    $location_types['legal']['address'] = $this->get('legal_address');
    $location_types['legal']['fallback'] = $this->get('legal_address_fallback');
    $location_types['postal']['address'] = $this->get('postal_address');
    $location_types['postal']['fallback'] = $this->get('postal_address_fallback');
    return $location_types;
  }

  /**
   * get the setting on whether to save the original PDF file
   *
   * @return bool
   */
  public function saveOriginalPDF() {
    return $this->get('store_original_pdf');
  }

  /**
   * get the default template ID
   *
   * @return int
   */
  public function getTemplate() {
    return $this->get('template');
  }

}
