<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

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
  protected int $id;

  /**
   * @var string
   *   The profile name.
   */
  protected string $name;

  /**
   * @var array
   *   The settings.
   */
  protected array $data;

  /**
   * @var array
   *   The additional Smarty variables passed to the receipt template.
   */
  protected array $variables;

  /**
   * @var string
   *   The Smarty template used for rendering donation receipts.
   */
  protected string $template;

  /**
   * @var int|null
   *  The ID of the PDF format to use for rendering the Smarty template.
   */
  protected $template_pdf_format_id = NULL;

  /**
   * @var bool
   *   Whether the profile is the default profile.
   */
  protected $is_default;

  /**
   * @var bool
   *   Whether the profile is active for creating receipts.
   */
  protected $is_active;

  /**
   * @var bool
   *   Whether the profile is locked (has already been used for issueing
   *   receipts).
   */
  protected $is_locked;

  /**
   * load setting entity with given ID
   *
   * @param int $profile_id
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
    $this->id = (int) $profile_data['id'];
    $this->data = $profile_data['data'] ?? [];
    $this->variables = $profile_data['variables'] ?? [];
    $this->template = $profile_data['template'] ?? '';
    if (isset($profile_data['template_pdf_format_id'])) {
      $this->template_pdf_format_id = (int) $profile_data['template_pdf_format_id'];
    }
    $this->is_default = (bool) $profile_data['is_default'];
    $this->is_active = (bool) $profile_data['is_active'];
    $this->is_locked = (bool) $profile_data['is_locked'];
  }

  /**
   * @param int $profile_id
   *
   * @return \CRM_Donrec_Logic_Profile
   */
  public static function getProfile($profile_id) {
    return new self($profile_id);
  }

  /**
   * Get the profile for the given name.
   *
   * @param string $profile_name
   * @return self
   * @throws \Exception
   *
   * @deprecated Since 2.0
   */
  public static function getProfileByName($profile_name) {
    $profile_names = self::getAllNames();
    $profile_id = array_search($profile_name, $profile_names);
    if (FALSE !== $profile_id) {
      return new self($profile_id);
    }
    else {
      throw new Exception(E::ts('Profile with name %1 does not exist.', [
        1 => $profile_name,
      ]));
    }
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
    $profile->id = 0;

    return $profile;
  }

  /**
   * update the internal data with the given set
   * @param array $data array(key => value)
   *
   * @deprecated Since 2.0
   */
  public function update($data) {
    // verify that copy_text and draft_text are set
    if (empty($data['copy_text'])) {
      $data['copy_text'] = E::ts('COPY');
    }
    if (empty($data['draft_text'])) {
      $data['draft_text'] = E::ts('DRAFT');
    }

    $this->data = $data;
  }

  /**
   * stores the setting as the unterlying settings entity
   */
  public function save() {
    $values_query = '
        SET
          `name` = %1,
          `data` = %2,
          `variables` = %3,
          `template` = %4,
          `is_default` = %5,
          `is_active` = %6,
          `is_locked` = %7
      ';

    $query_params = [
      1 => [$this->name, 'String'],
      2 => [serialize($this->data), 'String'],
      3 => [serialize($this->variables), 'String'],
      4 => [$this->template, 'String'],
      5 => [(int) $this->is_default, 'Int'],
      6 => [(int) $this->is_active, 'Int'],
      7 => [(int) $this->is_locked, 'Int'],
    ];

    if (!empty(($this->template_pdf_format_id))) {
      $values_query .= '
        ,`template_pdf_format_id` = %8
        ';
      $query_params[8] = [$this->template_pdf_format_id, 'Int'];
    }

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

    /** @var \CRM_Core_DAO $result */
    $result = CRM_Core_DAO::executeQuery($query, $query_params);

    if (!$this->id) {
      $this->id = (int) CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
    }
  }

  /**
   * Returns the profile ID.
   *
   * @return int
   *   The profile ID.
   */
  public function getId() {
    return $this->id;
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
   * Retrieves the date this profile was last used for issueing a receipt.
   *
   * @return \DateTime | NULL
   *   A DateTime object representing the last usage, or NULL when the profile
   *   has never been used for issueing receipts.
   */
  public function getLastUsage() {
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $receipt_fields = CRM_Donrec_DataStructure::getCustomFields('zwb_donation_receipt');

    $query = "
      SELECT
        MAX(`{$receipt_fields['issued_on']}`)
      FROM
        {$receipt_table}
      WHERE
        {$receipt_fields['profile_id']} = {$this->id}
    ;";
    $max_date = CRM_Core_DAO::singleValueQuery($query);

    if (NULL !== $max_date) {
      $max_date = date_create_from_format('Y-m-d H:i:s', $max_date);
    }

    return $max_date;
  }

  /**
   * Retrieves the date this profile was first used for issueing a receipt.
   *
   * @return \DateTime | NULL
   *   A DateTime object representing the first usage, or NULL when the profile
   *   has never been used for issueing receipts.
   */
  public function getFirstUsage() {
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $receipt_fields = CRM_Donrec_DataStructure::getCustomFields('zwb_donation_receipt');

    $query = "
      SELECT
        MIN(`{$receipt_fields['issued_on']}`)
      FROM
        {$receipt_table}
      WHERE
        {$receipt_fields['profile_id']} = {$this->id}
    ;";
    $min_date = CRM_Core_DAO::singleValueQuery($query);

    if (NULL !== $min_date) {
      $min_date = date_create_from_format('Y-m-d H:i:s', $min_date);
    }

    return $min_date;
  }

  /**
   * Retrieves the number of receipts issued with this profile.
   *
   * @return int
   *   The number of receipts issued with this profile.
   */
  public function getUsageCount() {
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $receipt_fields = CRM_Donrec_DataStructure::getCustomFields('zwb_donation_receipt');

    $query = "
      SELECT
        COUNT(`id`)
      FROM
        {$receipt_table}
      WHERE
        {$receipt_fields['profile_id']} = {$this->id}
    ;";
    return (int) CRM_Core_DAO::singleValueQuery($query);
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

  /**
   * Retrieves the given profile data property.
   *
   * @param string $key
   *   The name of the profile data property.
   *
   * @return mixed
   *   The value of the profile data property.
   */
  public function getDataAttribute($key) {
    return $this->data[$key] ?? NULL;
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
   * Retrieves all profile variables.
   *
   * @return array<string, mixed>
   *   The variables for this profile.
   */
  public function getVariables() {
    return $this->variables;
  }

  /**
   * @return string
   */
  public function getTemplateHTML() {
    return $this->template;
  }

  /**
   * @return int|null
   */
  public function getTemplatePDFFormatId() {
    return $this->template_pdf_format_id;
  }

  /**
   * check if a profile of the given name exists
   * @param string $profile_name
   * @return bool
   */
  public static function exists($profile_name) {
    $allNames = self::getAllNames();
    return isset($allNames[$profile_name]);
  }

  /**
   * return all existing profiles
   *
   * @return array<int, string>
   *   An array of names of Donation Receipts profiles, keyed by their IDs.
   */
  public static function getAllNames(string $sort = 'id', string $sort_order = 'ASC') {
    $allNames = [];
    $allProfiles = self::getAllData($sort, $sort_order);
    foreach ($allProfiles as $profile_id => $profile) {
      $allNames[$profile_id] = $profile['name'];
    }

    return $allNames;
  }

  /**
   * Retrieves the names of all active profiles, keyed by their IDs.
   *
   * @return array<int, string>
   *   The names of all active profiles, keyed by their IDs.
   */
  public static function getAllActiveNames(string $sort = 'id', string $sort_order = 'ASC') {
    return array_filter(self::getAllNames($sort, $sort_order), function($profile_name, $profile_id) {
      return self::getProfile($profile_id)->isActive();
    }, ARRAY_FILTER_USE_BOTH);
  }

  /**
   * return all existing profiles
   *
   * @return \CRM_Donrec_Logic_Profile[]
   *   An array with Donation Receipts profiles, keyed by their IDs.
   */
  public static function getAll(string $sort = 'id', string $sort_order = 'ASC') {
    $profiles = [];

    foreach (self::getAllData($sort, $sort_order) as $profile_id => $profile_data) {
      $profiles[$profile_id] = self::getProfile($profile_id);
    }

    return $profiles;
  }

  /**
   * return all existing data sets
   *
   * @return array<int, array<string, mixed>>
   */
  public static function getAllData(string $sort = 'id', string $sort_order = 'ASC') {
    $profiles = [];
    $query = "SELECT * FROM `donrec_profile` ORDER BY `{$sort}` {$sort_order}";
    /** @var \CRM_Core_DAO $dao */
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $profiles[$dao->id] = $dao->toArray();
      $profiles[$dao->id]['data'] = unserialize($profiles[$dao->id]['data']);
      $profiles[$dao->id]['variables'] = unserialize($profiles[$dao->id]['variables']);
    }

    return $profiles;
  }

  /**
   * @param string $profile_name
   */
  public function setName($profile_name) {
    $this->name = $profile_name;
  }

  /**
   * @param bool $status
   */
  public function setDefault($status = TRUE) {
    $this->is_default = (bool) $status;
    $this->save();
  }

  /**
   * @param bool $status
   */
  public function activate($status = TRUE) {
    $this->is_active = (bool) $status;
    $this->save();
  }

  /**
   * @param string $name
   * @param mixed $value
   */
  public function addVariable($name = NULL, $value = NULL) {
    if ($name) {
      $this->variables[$name] = $value;
    }
    else {
      $this->variables[] = $value;
    }
  }

  /**
   * @param int $new_default_profile_id
   *
   * @throws \Exception
   */
  public static function changeDefaultProfile($new_default_profile_id) {
    try {
      self::getDefaultProfile()->setDefault(FALSE);
    }
    catch (Exception $exception) {
      // @ignoreException
      // There is no profile currently set as default, nothing to do.
    }
    self::getProfile($new_default_profile_id)->setDefault();
  }

  /**
   * Sets the data property or one of its attributes.
   *
   * @param string $attribute
   *   The name of the data attribute to set.
   * @param mixed $value
   *   The value to set the data attribute to.
   */
  public function setDataAttribute($attribute, $value) {
    $this->data[$attribute] = $value;
  }

  /**
   * Set a property on the profile object.
   *
   * @param string $property
   * @param mixed $value
   */
  public function set($property, $value) {
    if (property_exists(self::class, $property)) {
      $this->$property = $value;
    }
    else {
      throw new Exception(E::ts('Property %1 does not exist in class %2', [
        1 => $property,
        2 => self::class,
      ]));
    }
  }

  /**
   * Lock the profile (mark as used for issueing receipt).
   *
   * @param bool $status
   */
  public function lock($status = TRUE) {
    if ($status && !$this->isLocked()) {
      $this->set('is_locked', (bool) $status);
      $this->save();
    }
    elseif (!$status && $this->isLocked()) {
      $this->set('is_locked', (bool) $status);
      $this->save();
    }
  }

  /**
   * set/overwrite profiles
   * caution: no type check
   *
   * @param array<string, array<string, mixed>> $profile_data array(name => profile)
   *
   * @deprecated Since 2.0
   */
  public static function setAllData($profile_data) {
    civicrm_api3('Setting', 'create', ['donrec_profiles' => $profile_data]);
  }

  /**
   * Returns all financial type ids that should be used by the donation receipt generation engine.
   *
   * @return array
   */
  public function getContributionTypes() {
    // get settings
    /** @var array|null $financial_types */
    $financial_types = $this->getDataAttribute('financial_types');

    if (empty($financial_types)) {
      // empty means 'all deductible'.
      return self::getAllDeductibleFinancialTypes();
    }

    return $financial_types;
  }

  /**
   * get a list of all deductible financial types
   *
   * @return list<int>
   */
  protected static function getAllDeductibleFinancialTypes() {
    $financial_types = [];

    $query = 'SELECT `id`, `name`, `is_deductible` FROM `civicrm_financial_type` WHERE `is_active` = 1;';
    /** @var \CRM_Core_DAO $results */
    $results = CRM_Core_DAO::executeQuery($query);
    while ($results->fetch()) {
      if ($results->is_deductible) {
        $financial_types[] = (int) $results->id;
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
   * @return string
   *   SQL clause
   * @author B. Endres
   */
  public function getContributionTypesClause() {
    // get all valid financial type IDs

    $financialTypeIds = $this->getContributionTypes();

    // construct the SQL clause
    if (empty($financialTypeIds)) {
      // no contribution types allowed
      return 'FALSE';
    }
    else {
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
    $location_types['legal']['address'] = $this->getDataAttribute('legal_address');
    $location_types['legal']['fallback'] = $this->getDataAttribute('legal_address_fallback');
    $location_types['postal']['address'] = $this->getDataAttribute('postal_address');
    $location_types['postal']['fallback'] = $this->getDataAttribute('postal_address_fallback');
    return $location_types;
  }

  /**
   * Returns "From" e-mail addresses configured within CiviCRM.
   *
   * @param bool $default
   *   Whether to return only default addresses.
   *
   * @return string
   */
  public static function getFromEmailAddresses($default = FALSE) {
    if ($default) {
      $condition = ' AND is_default = 1';
    }
    else {
      $condition = NULL;
    }
    return key(CRM_Core_OptionGroup::values('from_email_address', FALSE, FALSE, FALSE, $condition));
  }

  /**
   * get the setting on whether to save the original PDF file
   *
   * @return bool
   */
  public function saveOriginalPDF() {
    return (bool) $this->getDataAttribute('store_original_pdf');
  }

  /**
   * get the default template ID
   *
   * @return \CRM_Donrec_Logic_Template | NULL
   */
  public function getTemplate() {
    return CRM_Donrec_Logic_Template::getTemplate($this);
  }

  /**
   * Deletes the given profile
   * @param int $profile_id
   */
  public static function deleteProfile($profile_id) {
    $query = "
      DELETE FROM
        `donrec_profile`
      WHERE
        `id`= $profile_id;
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
    return [
    // 0 = new profile.
      'id' => 0,
      'name' => 'Default',
      'data' => [
        'financial_types'            => self::getAllDeductibleFinancialTypes(),
        'store_original_pdf'         => FALSE,
        'draft_text'                 => E::ts('DRAFT'),
        'copy_text'                  => E::ts('COPY'),
        'id_pattern'                 => '{issue_year}-{serial}',
    // '0' is the primary address
        'legal_address'              => ['0'],
        'postal_address'             => ['0'],
        'legal_address_fallback'     => ['0'],
        'postal_address_fallback'    => ['0'],
        'from_email'                 => CRM_Donrec_Logic_Profile::getFromEmailAddresses(TRUE),
        // TODO: Set correct defaults for formerly global settings here.
        'email_template'             => NULL,
        'bcc_email'                  => NULL,
        'return_path_email'          => NULL,
        'special_mail_handling'      => NULL,
        'special_mail_header'        => NULL,
        'special_mail_activity_id'                => NULL,
        'special_mail_activity_subject'           => NULL,
        'special_mail_withdraw_receipt'           => NULL,
        'special_mail_activity_contact_id'        => NULL,
        'watermark_preset'           => CRM_Donrec_Logic_WatermarkPreset::getDefaultWatermarkPresetName(),
        'language'                   => CRM_Core_I18n::getLocale(),
        'contribution_unlock_mode'   => 'unlock_none',
        'contribution_unlock_fields' => [],
        'enable_encryption' => FALSE,
      ],
      'variables' => [],
      'template' => CRM_Donrec_Logic_Template::getDefaultTemplateHTML(),
      'template_pdf_format_id' => CRM_Core_BAO_PdfFormat::getPdfFormat('is_default', 1)['id'],
      'is_default' => 0,
      'is_locked' => 0,
      'is_active' => 1,
    ];
  }

}
