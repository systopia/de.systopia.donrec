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
 * this function should get called when the module gets enabled.
 * It will make sure, that the customs groups necessary will be created.
 */
function donrec_lifecycle_updateCustomGroups() {

  //Field-Definitions
  $customGroupDefaults = array(
    'style' => 'Inline',
    'collapse_display' => 1,
    'is_active' => 1
  );
  $customGroups = array(
    array(
      'name' => 'zwb_donation_receipt',
      'title' => 'donation-receipt',
      'extends' => 'Contact',
    ),
    array(
      'name' => 'zwb_donation_receipt_item',
      'title' => 'donation-receipt-item',
      'extends' => 'Contact',
    )
  );
  $customFieldDefaults = array(
    'is_searchable' => 1,
    'is_active' => 1,
    'is_required' => 1,
  );
  $customFields = array(
    array(
      'name' => 'status',
      'custom_group_name' => 'zwb_donation_receipt',
      'option_group_name' => 'donrec_status',
      'label' => 'status',
      'data_type' => 'String',
      'html_type' => 'Select',
    ),
    array(
      'name' => 'type',
      'custom_group_name' => 'zwb_donation_receipt',
      'option_group_name' => 'donrec_type',
      'label' => 'type',
      'data_type' => 'String',
      'html_type' => 'Select',
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
      'name' => 'status',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'option_group_name' => 'donrec_status',
      'label' => 'status',
      'data_type' => 'String',
      'html_type' => 'Select',
    ),
    array(
      'name' => 'type',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'option_group_name' => 'donrec_type',
      'label' => 'type',
      'data_type' => 'String',
      'html_type' => 'Select',
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
  $optionGroupDefaults = array(
    'is_reserved' => 1,
    'is_active' => 1,
  );
  $optionGroups = array(
    array(
      'name' => 'donrec_status',
      'title' => 'status',
    ),
    array(
      'name' => 'donrec_type',
      'title' => 'type',
    ),
  );
  $optionValueDefaults = array(
    'is_active' => 1,
  );
  $optionValues = array(
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

  donrec_lifecycle_createOptionGroups($optionGroups, $optionGroupDefaults);
  donrec_lifecycle_createOptionValues($optionValues, $optionValueDefaults);
  donrec_lifecycle_createCustomGroups($customGroups, $customGroupDefaults);
  donrec_lifecycle_createCustomFields($customFields, $customFieldDefaults);
}

/**
 * Create OptionGroups if not already exists.
 */
function donrec_lifecycle_createOptionGroups($optionGroups, $optionGroupDefaults = array()) {
  foreach ($optionGroups as $optionGroup) {
    $params = array_merge($optionGroup, $optionGroupDefaults);
    donrec_lifecycle_createIfNotExists('OptionGroup', $params);
  }
}

/**
 * Create OptionValues if not already exists.
 */
function donrec_lifecycle_createOptionValues($optionValues, $optionValueDefauls = array()) {
  foreach ($optionValues as $optionValue) {
    $params = array_merge($optionValue, $optionValueDefauls);
    $optionGroup = civicrm_api3('OptionGroup', 'getsingle', array('name' => $params['option_group_name']));
    $params['option_group_id'] = $optionGroup['id'];
    unset($params['option_group_name']);
    donrec_lifecycle_createIfNotExists('OptionValue', $params);
  }
}

/**
 * Create CustomGroups if not already exists.
 */
function donrec_lifecycle_createCustomGroups($customGroups, $customGroupDefaults = array()) {
  foreach ($customGroups as $customGroup) {
    $params = array_merge($customGroup, $customGroupDefaults);
    donrec_lifecycle_createIfNotExists('CustomGroup', $params);
  }
}

/**
 * Create CustomFields if not already exists.
 */
function donrec_lifecycle_createCustomFields($customFields, $customFieldDefaults = array()) {
  foreach ($customFields as $customField) {
    $params = array_merge($customField, $customFieldDefaults);
    if (!empty($params['option_group_name'])) {
      $optionGroup = civicrm_api3('OptionGroup', 'getsingle', array('name' => $params['option_group_name']));
      $params['option_group_id'] = $optionGroup['id'];
      unset($params['option_group_name']);
    }
    $customGroup = civicrm_api3('CustomGroup', 'getsingle', array('name' => $params['custom_group_name']));
    $params['custom_group_id'] = $customGroup['id'];
    unset($params['custom_group_name']);
    // we need extra params for getting the entity, because the api fails on getting
    // a customField with option_group_id as parameter.
    $get_params = $params;
    unset($get_params['option_group_id']);
    donrec_lifecycle_createIfNotExists('CustomField', $params, $get_params);
  }
}

/**
 * Create if not exists.
 */
function donrec_lifecycle_createIfNotExists($entity, $params, $get_params = Null) {
  $get_params = $get_params ? $get_params : $params;
  $get = civicrm_api3($entity, 'get', $get_params);
  if ($get['count']) return;
  civicrm_api3($entity, 'create', $params);
}




