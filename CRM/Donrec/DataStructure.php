<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: T. Leichtfuss (leichtfuss -at- systopia.de)    |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

  /**
   * Class to manage the data-structure.
   */
class CRM_Donrec_DataStructure {

  public static $customGroupDefaults = array(
    'style' => 'Inline',
    'collapse_display' => 1,
    'is_active' => 1,
    'is_multiple' => 1,
  );
  public static $customGroups = array(
    array(
      'name' => 'zwb_donation_receipt',
      'title' => 'donation-receipt',
      'extends' => 'Contact',
    ),
    array(
      'name' => 'zwb_donation_receipt_item',
      'title' => 'donation-receipt-item',
      'extends' => 'Contribution',
    )
  );
  public static $customFieldDefaults = array(
    'is_searchable' => 1,
    'is_active' => 1,
    'is_view' => 1,
  );
  public static $customFields = array(
    array(
      'name' => 'status',
      'custom_group_name' => 'zwb_donation_receipt',
      'option_group_name' => 'donrec_status',
      'label' => 'status',
      'data_type' => 'String',
      'html_type' => 'Select',
      'option_type' => 0,
      'text_length' => 255,
      'note_columns' => 60,
      'note_rows' => 4,
    ),
    array(
      'name' => 'type',
      'custom_group_name' => 'zwb_donation_receipt',
      'option_group_name' => 'donrec_type',
      'label' => 'type',
      'data_type' => 'String',
      'html_type' => 'Select',
      'option_type' => 0,
      'text_length' => 255,
      'note_columns' => 60,
      'note_rows' => 4,
    ),
    array(
      'name' => 'issued_on',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'issued_on',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
    ),
    array(
      'name' => 'issued_by',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'issued_by',
      'data_type' => 'Int',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'original_file',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'original_file',
      'data_type' => 'Int',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'street_address',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'street_address',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'supplemental_address_1',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'supplemental_address_1',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'supplemental_address_2',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'supplemental_address_2',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'supplemental_address_3',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'supplemental_address_3',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'postal_code',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'postal_code',
      'data_type' => 'Int',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'city',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'city',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'country',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'country',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'status',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'option_group_name' => 'donrec_status',
      'label' => 'status',
      'data_type' => 'String',
      'html_type' => 'Select',
      'option_type' => 0,
    ),
    array(
      'name' => 'type',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'option_group_name' => 'donrec_type',
      'label' => 'type',
      'data_type' => 'String',
      'html_type' => 'Select',
      'option_type' => 0,
    ),
    array(
      'name' => 'issued_in',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'issued_in',
      'data_type' => 'Int',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'issued_on',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'issued_on',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
    ),
    array(
      'name' => 'issued_by',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'issued_by',
      'data_type' => 'Int',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'total_amount',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'total_amount',
      'data_type' => 'Money',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'non_deductible_amount',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'non_deductible_amount',
      'data_type' => 'Money',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'currency',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'currency',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'receive_date',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'receive_date',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
    ),
    array(
      'name' => 'contribution_hash',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'contribution_hash',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
  );
  public static $optionGroupDefaults = array(
    'is_reserved' => 1,
    'is_active' => 1,
  );
  public static $optionGroups = array(
    array(
      'name' => 'donrec_status',
      'title' => 'status',
    ),
    array(
      'name' => 'donrec_type',
      'title' => 'type',
    ),
  );
  public static $optionValueDefaults = array(
    'is_active' => 1,
  );
  public static $optionValues = array(
    array(
      'name' => 'ORIGINAL',
      'option_group_name' => 'donrec_status',
      'label' => 'original',
      'value' => 'ORIGINAL',
    ),
    array(
      'name' => 'INVALID',
      'option_group_name' => 'donrec_status',
      'label' => 'invalid',
      'value' => 'INVALID',
    ),
    array(
      'name' => 'COPY',
      'option_group_name' => 'donrec_status',
      'label' => 'copy',
      'value' => 'COPY',
    ),
    array(
      'name' => 'SINGLE',
      'option_group_name' => 'donrec_type',
      'label' => 'single',
      'value' => 'SINGLE',
    ),
    array(
      'name' => 'BULK',
      'option_group_name' => 'donrec_type',
      'label' => 'bulk',
      'value' => 'BULK',
    ),
  );

  /**
   * Create all custom-groups and -fields if they don't exist.
   */
  public static function update() {
    self::updateOptionGroups();
    self::updateOptionValues();
    self::updateCustomGroups();
    self::updateCustomFields();
  }
  /**
   * Create OptionGroups if not already exists.
   */
  protected static function updateOptionGroups() {
    foreach (self::$optionGroups as $optionGroup) {
      $params = array_merge($optionGroup, self::$optionGroupDefaults);
      $get_params['name'] = $params['name'];
      self::createIfNotExists('OptionGroup', $params, $get_params);
    }
  }

  /**
   * Create OptionValues if not already exists.
   */
  protected static function updateOptionValues() {
    foreach (self::$optionValues as $optionValue) {
      $params = array_merge($optionValue, self::$optionValueDefaults);
      $optionGroup = civicrm_api3('OptionGroup', 'getsingle', array('name' => $params['option_group_name']));
      // replace option_group_name with option_group_id
      $params['option_group_id'] = $optionGroup['id'];
      unset($params['option_group_name']);
      $get_params['name'] = $params['name'];
      $get_params['option_group_id'] = $params['option_group_id'];
      self::createIfNotExists('OptionValue', $params, $get_params);
    }
  }

  /**
   * Create CustomGroups if not already exists.
   */
  protected static function updateCustomGroups() {
    foreach (self::$customGroups as $customGroup) {
      $params = array_merge($customGroup, self::$customGroupDefaults);
      $get_params['name'] = $params['name'];
      self::createIfNotExists('CustomGroup', $params, $get_params);
    }
  }

  /**
   * Create CustomFields if not already exists.
   */
  protected static function updateCustomFields() {
    foreach (self::$customFields as $customField) {
      $params = array_merge($customField, self::$customFieldDefaults);
      if (!empty($params['option_group_name'])) {
        $optionGroup = civicrm_api3('OptionGroup', 'getsingle', array('name' => $params['option_group_name']));
      // replace option_group_name with option_group_id
        $params['option_group_id'] = $optionGroup['id'];
        unset($params['option_group_name']);
      }
      $customGroup = civicrm_api3('CustomGroup', 'getsingle', array('name' => $params['custom_group_name']));
      // replace custom_group_name with custom_group_id
      $params['custom_group_id'] = $customGroup['id'];
      unset($params['custom_group_name']);
      $get_params['name'] = $params['name'];
      $get_params['custom_group_id'] = $params['custom_group_id'];
      self::createIfNotExists('CustomField', $params, $get_params);
    }
  }

  /**
   * Create if not exists.
   */
  protected static function createIfNotExists($entity, $params, $get_params = Null) {
    $get_params = $get_params ? $get_params : $params;
    $get = civicrm_api3($entity, 'get', $get_params);
    if ($get['count']) {
      if ($get['count'] > 1) error_log("de.systopia.donrec: warning: $entity exists multiple times: " . print_r($get_params, True));
      return;
    }
    civicrm_api3($entity, 'create', $params);
  }

  /**
  * Find the first available option value id
  */
  public static function getFirstUsedOptionValueId() {
    $optionGroup = civicrm_api3('OptionGroup', 'getsingle', array('name' => 'donrec_status'));
    if (!empty($optionGroup['is_error'])) {
      return FALSE;
    }
    $id = civicrm_api3('OptionValue', 'get', array('option_group_id' => $optionGroup['id']));
    if (!empty($id['is_error']) || $id['count'] < 1) {
      return FALSE;
    }
    // return first value
    $id = array_values($id['values']);
    return $id[0]['id'];
  }

}
