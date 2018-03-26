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

  protected static $SETTINGS_PROFILE_SETTING = "donrec_profiles";

  /** ID if the setting entity, where the values are stored */
  protected $profile_name = NULL;
  protected $data = NULL;

  /**
   * load setting entity with given ID
   */
  public function __construct($profile_name) {
    $all_data = self::getAllData();
    $data     = CRM_Utils_Array::value($profile_name, $all_data, NULL);

    if ($data==NULL || !is_array($data)) {
      // this setting doesn't exist yet or is malformed
      $this->data = self::defaultProfileData();
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
    $profile_data = self::getAllData();
    $profile_data[$this->profile_name] = $this->data;
    self::setAllData($profile_data);
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
    $allNames = self::getAllNames();
    return isset($allNames[$profile_name]);
  }

  /**
   * return all existing profiles
   *
   * @return array(name => name)
   */
  public static function getAllNames() {
    $allNames = array();
    $allProfiles = self::getAllData();
    foreach ($allProfiles as $profile_name => $profile) {
      $allNames[$profile_name] = $profile_name;
    }

    if (!in_array('Default', $allNames)) {
      $allNames['Default'] = 'Default';
    }

    return $allNames;
  }

  /**
   * return all existing profiles
   *
   * @return array(name => profile)
   */
  public static function getAll() {
    $allProfiles   = array();
    $profile_names = self::getAllNames();

    foreach ($profile_names as $profile_name) {
      $allProfiles[$profile_name] = new CRM_Donrec_Logic_Profile($profile_name);
    }

    if (empty($allProfiles)) {
      $allProfiles['Default'] = new CRM_Donrec_Logic_Profile('Default');
    }

    return $allProfiles;
  }

  /**
   * return all existing data sets
   *
   * @return array(name => array(profile_data))
   */
  public static function getAllData() {
    $profiles = civicrm_api3('Setting', 'getvalue', array('name' => self::$SETTINGS_PROFILE_SETTING));
    if (!is_array($profiles)) {
      // initialise
      $profiles = array();
    }

    if (empty($profiles['Default'])) {
      // inject default data if not set
      $profiles['Default'] = self::defaultProfileData();
    }
    return $profiles;
  }

  /**
   * set/overwrite profiles
   * caution: no type check
   *
   * @param $profile_data array(name => profile)
   */
  public static function setAllData($profile_data) {
    civicrm_api3('Setting', 'create', array(self::$SETTINGS_PROFILE_SETTING => $profile_data));
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
      return self::getAllDeductibleFinancialTypes();
    }

    return $financial_types;
  }

  /**
   * get a list of all deductible financial types
   */
  protected static function getAllDeductibleFinancialTypes() {
    $financial_types = array();

    $query = "SELECT `id`, `name`, `is_deductible` FROM `civicrm_financial_type` WHERE `is_active` = 1;";
    $results = CRM_Core_DAO::executeQuery($query);
    while ($results->fetch()) {
      if ($results->is_deductible) {
        $financial_types[] = $results->id;
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
   * Returns "From" e-mail addresses configured within CiviCRM.
   *
   * @param bool $default
   *   Whether to return only default addresses.
   */
  public function getFromEmailAddresses($default = FALSE) {
    if ($default) {
      $condition = ' AND is_default = 1';
    }
    else {
      $condition = NULL;
    }
    return key(CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, $condition));
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
    $profiles = self::getAllData();
    if (isset($profiles[$profile_name])) {
      unset($profiles[$profile_name]);
      self::setAllData($profiles);
    }
  }

  /**
   * create a default profile data
   */
  public static function defaultProfileData() {

    return array(
      'financial_types'         => self::getAllDeductibleFinancialTypes(),
      'store_original_pdf'      => FALSE,
      'template'                => CRM_Donrec_Logic_Template::getDefaultTemplateID(),
      'draft_text'              => ts('DRAFT', array('domain' => 'de.systopia.donrec')),
      'copy_text'               => ts('COPY',  array('domain' => 'de.systopia.donrec')),
      'id_pattern'              => '{issue_year}-{serial}',
      'legal_address'           => array('0'),  // '0' is the primary address
      'postal_address'          => array('0'),
      'legal_address_fallback'  => array('0'),
      'postal_address_fallback' => array('0'),
      'donrec_from_email'       => CRM_Donrec_Logic_Profile::getFromEmailAddresses(TRUE),
      );
  }
}
