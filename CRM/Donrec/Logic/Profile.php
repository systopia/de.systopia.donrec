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
      $this->data = self::defaultProfileData();

      // if this is 'Default', take over the old config values (if present)
      if ($profile_name == 'Default') {
        foreach (array_keys($this->data) as $field_name) {
          $legacy_value = CRM_Core_BAO_Setting::getItem(CRM_Donrec_Logic_Settings::$SETTINGS_GROUP, $field_name);
          if ($legacy_value !== NULL) {
            $this->data[$field_name] = $legacy_value;
          }
        }
        $legacy_contribution_types = CRM_Core_BAO_Setting::getItem(CRM_Donrec_Logic_Settings::$SETTINGS_GROUP, 'contribution_types');
        if ($legacy_contribution_types !== NULL && $legacy_contribution_types != 'all') {
          $this->data['financial_types'] = explode(',', $legacy_contribution_types);
        }
      }

    } else {
      $this->data = $data;
    }

    $this->profile_name = $profile_name;
  }

  /**
   * Get the profile for the given name,
   * Falling back to 'Default' if that profile doesn't exist
   */
  public static function getProfile($profile_name, $warn = FALSE) {

    if (empty($profile_name)) {
      if ($warn) {
        // TODO: MESSAGE?
      }
      $profile_name = 'Default';
    } elseif (!self::exists($profile_name)) {
      if ($warn) {
        // TODO: MESSAGE?
      }
      $profile_name = 'Default';
    }

    return new CRM_Donrec_Logic_Profile($profile_name);
  }

  /**
   * update the internal data with the given set
   * @param $data array(key => value)
   */
  public function update($data) {
    // verify that copy_text and draft_text are set
    if (empty($data['copy_text'])) $data['copy_text']   = ts('COPY',  array('domain' => 'de.systopia.donrec'));
    if (empty($data['draft_text'])) $data['draft_text'] = ts('DRAFT', array('domain' => 'de.systopia.donrec'));

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
   * check if a profile of the given name exists
   */
  public static function exists($profile_name) {
    return CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_setting WHERE group_name = %1 AND name = %2",
      array( 1 => array(self::$SETTINGS_PROFILE_GROUP, 'String'),
             2 => array($profile_name, 'String')));
  }

  /**
   * return all existing profiles
   *
   * @return array(name => name)
   */
  public static function getAllNames() {
    $allProfiles = array();

    // FIXME: is there a better way than a SQL query?
    $sql = "SELECT name FROM civicrm_setting WHERE group_name = %1;";
    $sql_params = array(1 => array(self::$SETTINGS_PROFILE_GROUP, 'String'));
    $query = CRM_Core_DAO::executeQuery($sql, $sql_params);
    while ($query->fetch()) {
      $allProfiles[$query->name] = $query->name;
    }

    if (empty($allProfiles)) {
      $allProfiles['Default'] = 'Default';
    }

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

    if (empty($allProfiles)) {
      $profile_data['Default'] = new CRM_Donrec_Logic_Profile('Default');
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
   * adjust the existing profiles given the data (as produced by self::getAllData())
   *
   * this method will also create and delete new and obsolete profiles respectively
   */
  public static function syncProfileData($data) {
    $old_profiles = self::getAll();

    // first update/create all the new ones
    foreach ($data as $profile_name => $profile_data) {
      if (empty($profile_name)) continue; // just to be sure...
      $profile = new CRM_Donrec_Logic_Profile($profile_name);
      $profile->update($profile_data);
      $profile->save();
      unset($old_profiles[$profile_name]);
    }

    // the old profiles left over can be deleted
    foreach ($old_profiles as $profile_name => $profile_data) {
      self::deleteProfile($profile_name);
    }
  }


  /**
   * Returns all financial type ids that should be used by the donation receipt generation engine.
   *
   * @return array
   */
  public function getContributionTypes() {
    // get settings
    $financial_types = $this->get('financial_types');

    if (empty($financial_types)) {
      // empty means 'all deductible'
      $financial_types = array();

      $query = "SELECT `id`, `name`, `is_deductible` FROM `civicrm_financial_type` WHERE `is_active` = 1;";
      $results = CRM_Core_DAO::executeQuery($query);
      while ($results->fetch()) {
        if ($results->is_deductible) {
          $financial_types[] = $results->id;
        }
      }
    }

    return $financial_types;
  }

  /**
   * Similar to ::getContributionTypes(), but will
   * construct and return an SQL clause
   * that works in the WHERE clause on
   * civicrm_contribution queries.
   *
   * @return SQL clause
   * @author B. Endres
   */
  public function getContributionTypesClause() {
    // get all valid financial type IDs

    $financialTypeIds = $this->getContributionTypes();

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
    return CRM_Donrec_Logic_Template::getTemplate($this->get('template'));
  }


  /**
   * Deletes the given profile
   */
  public static function deleteProfile($profile_name) {
    $bao = new CRM_Core_BAO_Setting();
    $bao->group_name = self::$SETTINGS_PROFILE_GROUP;
    $bao->name = $profile_name;
    $bao->find(TRUE);
    $bao->delete();
  }

  /**
   * create a default profile data
   */
  public static function defaultProfileData() {
    return array(
      'financial_types'         => array(),
      'store_original_pdf'      => FALSE,
      'template'                => CRM_Donrec_Logic_Settings::getDefaultTemplate(),
      'draft_text'              => ts('DRAFT', array('domain' => 'de.systopia.donrec')),
      'copy_text'               => ts('COPY',  array('domain' => 'de.systopia.donrec')),
      'id_pattern'              => '{issue_year}-{serial}',
      'legal_address'           => array('0'),  // '0' is the primary address
      'postal_address'          => array('0'),
      'legal_address_fallback'  => array('0'),
      'postal_address_fallback' => array('0'),
      );
  }
}
