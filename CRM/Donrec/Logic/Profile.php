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

  /**
   * @var int
   *   The profile ID.
   */
  protected $id = NULL;

  /**
   * @var string
   *   The profile name.
   */
  protected $name = NULL;

  /**
   * @var array
   *   The settings.
   */
  protected $data = array();

  /**
   * @var array
   *   The additional Smarty variables passed to the receipt template.
   */
  protected $variables = array();

  /**
   * @var string
   *   The Smarty template used for rendering donation receipts.
   */
  protected $template = NULL;

  /**
   * @var null
   *  The ID of the PDF format to use for rendering the Smarty template.
   */
  protected $template_pdf_format_id = NULL;

  /**
   * @var bool
   *   Whether the profile is the default profile.
   */
  protected $is_default = 0;

  /**
   * @var bool
   *   Whether the profile is active for creating receipts.
   */
  protected $is_active = 1;

  /**
   * @var bool
   *   Whether the profile is locked (has already been used for issueing
   *   receipts).
   */
  protected $is_locked = 0;

  /**
   * load setting entity with given ID
   */
  public function __construct($profile_id = NULL) {
    $all_profiles = self::getAllData();
    if (isset($all_profiles[$profile_id])) {
      $profile_data = $all_profiles[$profile_id];
    }
    else {
      $profile_data = CRM_Donrec_Logic_Profile::defaultProfileData();
    }

    $this->name = $profile_data['name'];
    $this->id = $profile_data['id'];
    $this->data = $profile_data['data'];
    $this->variables = $profile_data['variables'];
    $this->template = $profile_data['template'];
    $this->template_pdf_format_id = $profile_data['template_pdf_format_id'];
    $this->is_default = $profile_data['is_default'];
    $this->is_active = $profile_data['is_active'];
    $this->is_locked = $profile_data['is_locked'];
  }

  public static function getProfile($profile_id) {
    return new self($profile_id);
  }

  /**
   * Get the profile for the given name.
   *
   * @return self
   */
  public static function getProfileByName($profile_name, $warn = FALSE) {
    $profile_names = self::getAllNames();
    if (in_array($profile_name, $profile_names)) {
      return new self(array_search($profile_name, $profile_names));
    }
    else {
      throw new Exception(E::ts('Profile with name %1 does not exist.', array(
        1 => $profile_name,
      )));
    }

    return new self($profile_name);
  }

  /**
   * Retrieves the current default profile.
   *
   * @return self
   *
   * @throws \Exception
   *   When no default profile could be found.
   */
  public static function getDefaultProfile() {
    foreach (self::getAll() as $profile) {
      if ($profile->isDefault()) {
        return $profile;
      }
    }

    throw new Exception(E::ts('Could not find a default profile.'));
  }

  public static function copyProfile($profile_id) {
    $profile = self::getProfile($profile_id);
    $profile->name .= '_copy';
    unset($profile->id);

    return $profile;
  }

  /**
   * update the internal data with the given set
   * @param $data array(key => value)
   *
   * @deprecated Since 2.0
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
    $values_query = "
        SET
          `name` = %1,
          `data` = %2,
          `variables` = %3,
          `template` = %4,
          `template_pdf_format_id` = %5,
          `is_default` = %6,
          `is_active` = %7,
          `is_locked` = %8
      ";

    if ($this->id) {
      $query = "
          UPDATE
            `donrec_profile`
          $values_query
          WHERE
            `id` = $this->id
        ;";
    }
    else {
      $query = "
          INSERT INTO
            `donrec_profile`
          $values_query
        ;";
    }

    $query_params = array(
      1 => array($this->name, 'String'),
      2 => array(serialize($this->data), 'String'),
      3 => array(serialize($this->variables), 'String'),
      4 => array($this->template, 'String'),
      5 => array($this->template_pdf_format_id, 'Int'),
      6 => array($this->is_default, 'Int'),
      7 => array($this->is_active, 'Int'),
      8 => array($this->is_locked, 'Int'),
    );

    CRM_Core_DAO::executeQuery($query, $query_params);
  }

  /**
   * Returns whether the profile is active.
   *
   * @return bool
   *   Whether the profile is active.
   */
  public function isActive() {
    return (bool) $this->is_active;
  }

  /**
   * Returns whether the profile is locked, i.e. has already been used for
   * issueing receipts.
   *
   * @return bool
   *   Whether the profile is locked.
   */
  public function isLocked() {
    return (bool) $this->is_locked;
  }

  /**
   * Returns whether the profile is the default profile.
   *
   * @return bool
   *   Whether the profile is the default profile.
   */
  public function isDefault() {
    return (bool) $this->is_default;
  }

  /**
   * Retrieves the profile name.
   *
   * @return string
   *   The profile name.
   */
  public function getName() {
    return $this->name;
  }

//  /**
//   * Retrieves the profile's template.
//   *
//   * @return string
//   *   The template stored within the profile.
//   */
//  public function getTemplate() {
//    return $this->template;
//  }

  /**
   * Retrieves the given profile data property.
   *
   * @param string $key
   *   The name of the profile data property.
   *
   * @return mixed
   *   The value of the profile data property.
   */
  public function get($key) {
    return CRM_Utils_Array::value($key, $this->data, NULL);
  }

  /**
   * Retrieves all profile data properties.
   *
   * @return array
   *   The profile data array.
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
   * @return string[]
   *   An array of names of Donation Receipts profiles, keyed by their IDs.
   */
  public static function getAllNames() {
    $allNames = array();
    $allProfiles = self::getAllData();
    foreach ($allProfiles as $profile_id => $profile) {
      $allNames[$profile_id] = $profile['name'];
    }

    return $allNames;
  }

  /**
   * return all existing profiles
   *
   * @return \CRM_Donrec_Logic_Profile[]
   *   An array with Donation Receipts profiles, keyed by their IDs.
   */
  public static function getAll() {
    $profiles = array();

    foreach (self::getAllData() as $profile_id => $profile_data) {
      $profiles[$profile_id] = self::getProfile($profile_id);
    }

    return $allProfiles;
  }

  /**
   * return all existing data sets
   *
   * @return array
   */
  public static function getAllData() {
    $profiles = array();
    $query = "SELECT * FROM `donrec_profile`";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $profiles[$dao->id] = $dao->toArray();
      $profiles[$dao->id]['data'] = unserialize($profiles[$dao->id]['data']);
      $profiles[$dao->id]['variables'] = unserialize($profiles[$dao->id]['variables']);
    }

    return $profiles;
  }

  public function setName($profile_name) {
    $this->name = $profile_name;
  }

  public function setDefault($status = TRUE) {
    $this->is_default = (int) $status;
    $this->save();
  }

  public function activate($status = TRUE) {
    $this->is_active = (int) $status;
    $this->save();
  }

  /**
   * Sets the data property or one of its attributes.
   *
   * @param $attribute
   *   The name of the data attribute to set.
   * @param $value
   *   The value to set the data attribute to.
   */
  public function setDataAttribute($attribute, $value) {
    $this->data[$attribute] = $value;
  }

  /**
   * Set a property on the profile object.
   *
   * @param $property
   * @param $value
   */
  public function set($property, $value) {
    if (property_exists(self::class, $property)) {
      $this->$property = $value;
    }
    else {
      throw new Exception(E::ts('Property %1 does not exist in class %2', array(
        1 => $property,
        2 => self::class,
      )));
    }
  }

  /**
   * set/overwrite profiles
   * caution: no type check
   *
   * @param $profile_data array(name => profile)
   *
   * @deprecated Since 2.0
   */
  public static function setAllData($profiles) {
    foreach ($profile_data as $profile_id => $profile_data) {
      $values_query = "
        SET
          `name` = {$profile_data['name']},
          `data` = {$profile_data['data']},
          `variables` = {$profile_data['variables']},
          `template` = {$profile_data['template']},
          `is_default` = {$profile_data['is_default']},
          `is_active` = {$profile_data['is_active']},
          `is_locked` = {$profile_data['is_locked']}
      ";

      if (is_numeric($profile_id)) {
        $query = "
          UPDATE
            `donrec_profile`
          $values_query
          WHERE
            `id` = $profile_id
        ;";
      }
      else {
        $query = "
          INSERT INTO
            `donrec_profile`
          $values_query
        ;";
      }
      CRM_Core_DAO::executeQuery($query);
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
   * @return \CRM_Donrec_Logic_Template
   */
  public function getTemplate() {
    return CRM_Donrec_Logic_Template::getTemplate($this->template, $this->template_pdf_format_id);
  }


  /**
   * Deletes the given profile
   */
  public static function deleteProfile($profile_id) {
    $query = "
      DELETE FROM
        `donrec_profile`
      WHERE
        `id `= $profile_id;
    ";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Returns a profile array with default values.
   *
   * @return array
   *   An array with profile defaults.
   */
  public static function defaultProfileData() {
    return array(
      'id' => 0, // 0 = new profile.
      'name' => NULL,
      'data' => array(
        'financial_types'            => self::getAllDeductibleFinancialTypes(),
        'store_original_pdf'         => FALSE,
        'draft_text'                 => ts('DRAFT', array('domain' => 'de.systopia.donrec')),
        'copy_text'                  => ts('COPY',  array('domain' => 'de.systopia.donrec')),
        'id_pattern'                 => '{issue_year}-{serial}',
        'legal_address'              => array('0'),  // '0' is the primary address
        'postal_address'             => array('0'),
        'legal_address_fallback'     => array('0'),
        'postal_address_fallback'    => array('0'),
        'donrec_from_email'          => CRM_Donrec_Logic_Profile::getFromEmailAddresses(TRUE),
        // TODO: Set correct defaults for formerly global settings here.
        'email_template'             => NULL,
        'bcc_email'                  => NULL,
        'return_path_email'          => NULL,
        'watermark_preset'           => CRM_Donrec_Logic_WatermarkPreset::getDefaultWatermarkPresetName(),
        'language'                   => (method_exists('CRM_Core_I18n', 'getLocale') ? CRM_Core_I18n::getLocale() : 'en_US'),
        'contribution_unlock_mode'   => 'unlock_none',
        'contribution_unlock_fields' => array(),
      ),
      'variables' => array(),
      'template' => CRM_Donrec_Logic_Template::getDefaultTemplateHTML(),
      'template_pdf_format_id' => CRM_Core_BAO_PdfFormat::getPdfFormat('is_default', 1)['id'],
      'is_default' => 0,
      'is_locked' => 0,
      'is_active' => 1,
    );
  }
}
