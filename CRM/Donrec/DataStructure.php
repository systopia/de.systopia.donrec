<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: T. Leichtfuss (leichtfuss -at- systopia.de)    |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

require_once __DIR__ . "/../Utils/DonrecHelper.php";

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

    /***** receipt *****/
    /*receipt-specific*/
    array(
      'name' => 'receipt_id',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'Receipt ID',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'profile',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'Profile',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
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
      'data_type' => 'ContactReference',
      'html_type' => 'Autocomplete-Select',
    ),
    array(
      'name' => 'original_file',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'original_file',
      'data_type' => 'Int',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'date_from',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'date_from',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
    ),
    array(
      'name' => 'date_to',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'date_to',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
    ),
    /*contact-specific*/
    array(
      'name' => 'display_name',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'Display Name',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'contact_type',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'Contact Type',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'gender',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'Gender',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'prefix',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'Prefix',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'postal_greeting_display',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'Postal Greeting Display',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'email_greeting_display',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'Email Greeting Display',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    /*address-specific*/
    array(
      'name' => 'addressee_display',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'Addressee Display',
      'data_type' => 'String',
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
      'name' => 'postal_code',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'postal_code',
      'data_type' => 'String',
      'html_type' => 'Text',
      'text_length' => 12,
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
    /*shipping-address-specific*/
    array(
      'name' => 'shipping_addressee_display',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'Shipping Addressee Display',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'shipping_street_address',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'shipping_street_address',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'shipping_supplemental_address_1',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'shipping_supplemental_address_1',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'shipping_supplemental_address_2',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'shipping_supplemental_address_2',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'shipping_postal_code',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'shipping_postal_code',
      'data_type' => 'String',
      'html_type' => 'Text',
      'text_length' => 12,
    ),
    array(
      'name' => 'shipping_city',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'shipping_city',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'shipping_country',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'shipping_country',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'exporters',
      'custom_group_name' => 'zwb_donation_receipt',
      'label' => 'exporters',
      'data_type' => 'String',
      'html_type' => 'Text',
    ),

    /***** receipt-item *****/
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
      'name' => 'receipt_id',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'Receipt ID',
      'data_type' => 'String',
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
      'data_type' => 'ContactReference',
      'html_type' => 'Autocomplete-Select',
    ),
    array(
      'name' => 'total_amount',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'total_amount',
      'data_type' => 'Money',
      'html_type' => 'Text',
    ),
    array(
      'name' => 'financial_type_id',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'financial_type_id',
      'data_type' => 'Int',
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
    array(
      'name' => 'exporters',
      'custom_group_name' => 'zwb_donation_receipt_item',
      'label' => 'exporters',
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
      'name' => 'COPY',
      'option_group_name' => 'donrec_status',
      'label' => 'copy',
      'value' => 'COPY',
    ),
    array(
      'name' => 'WITHDRAWN',
      'option_group_name' => 'donrec_status',
      'label' => 'withdrawn',
      'value' => 'WITHDRAWN',
    ),
    array(
      'name' => 'WITHDRAWN_COPY',
      'option_group_name' => 'donrec_status',
      'label' => 'withdrawn_copy',
      'value' => 'WITHDRAWN_COPY',
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
   * Array to cache the database-details of custom-groups and -fields.
   */
  protected static $_custom_groups = array(
    'zwb_donation_receipt' => array(
      'id' => NULL,
      'table_name' => NULL,
      'fields' => array()
    ),
    'zwb_donation_receipt_item' => array(
      'id' => NULL,
      'table_name' => NULL,
      'fields' => array()
    )
  );

  /**
   * Create all custom-groups and -fields if they don't exist.
   */
  public static function update() {
    self::updateOptionGroups();
    self::updateOptionValues();
    self::updateCustomGroups();
    self::updateCustomFields();
    self::fixCustomGroupType();
    self::upgrade_1_3();
  }

  /**
   * 4.6+ workaround: Update the type of our custom
   * groups to '0' after installation to prevent them
   * from being shown in the contact dashboard (#2541)
   */
   protected static function fixCustomGroupType() {
       CRM_Core_DAO::singleValueQuery("UPDATE `civicrm_custom_group`
                                          SET `style` = 0
                                        WHERE (`name` = 'zwb_donation_receipt'
                                           OR  `name` = 'zwb_donation_receipt_item')
                                          AND `style` = 'Inline';
                                     ");
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
      // DISABLED! THERE'S HARDCODED TABLE NAMES EVERYWHERE:
      //$params['title'] = ts($params['title'], array('domain' => 'de.systopia.donrec'));
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
      //$params['label'] = ts($params['label']);   // localize group names
      unset($params['custom_group_name']);
      $get_params['name'] = $params['name'];
      $get_params['custom_group_id'] = $params['custom_group_id'];

      // We use createOrUpdateEntity instead of createIfNotExists.
      // Issue #1725: the field-declarations for postal-code-fields
      // has been changed.
      self::createOrUpdateEntity  ('CustomField', $params, $get_params);
    }
  }

  /**
  * Find the first available option value id
  *
  * @return option-value-id
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

  /**
   * This is a workaround for the problem that using the translated title right away makes the
   * table names change.
   *
   * FIXME: we should not be working with static table names
   */
  public static function translateCustomGroups() {
    try {
      // TRANSLATE zwb_donation_receipt title
      $custom_group_receipt = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'zwb_donation_receipt'));
      // since the API is not reliable here, we do this via SQL
      $new_title = CRM_Utils_DonrecHelper::escapeString(ts('Donation Receipt', array('domain' => 'de.systopia.donrec')));
      $custom_group_receipt_id = (int) $custom_group_receipt['id'];
      CRM_Core_DAO::executeQuery("UPDATE `civicrm_custom_group` SET title='$new_title' WHERE id=$custom_group_receipt_id;");

      // TRANSLATE zwb_donation_receipt_item title
      $custom_group_receipt_item = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'zwb_donation_receipt_item'));
      // since the API is not reliable here, we do this via SQL
      $new_title = CRM_Utils_DonrecHelper::escapeString(ts('Donation Receipt Item', array('domain' => 'de.systopia.donrec')));
      $custom_group_receipt_item_id = (int) $custom_group_receipt_item['id'];
      CRM_Core_DAO::executeQuery("UPDATE `civicrm_custom_group` SET title='$new_title' WHERE id=$custom_group_receipt_item_id;");

    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('de.systopia.donrec - Error translating custom groups: '.$e->getMessage());
    }
  }

  /**
   * Create an entity if it does not exist.
   *
   * @param $entity string, name of the entity used with the api
   * @param $params array, parameters the entity will be created with
   * @param $get_params array, parameters to identify already existing entities,
   *        if missing $params will be used.
   */
  protected static function createIfNotExists($entity, $params, $get_params = Null) {
    $get_params = $get_params ? $get_params : $params;
    $get = civicrm_api3($entity, 'get', $get_params);
    if ($get['count'] == 0) {
      civicrm_api3($entity, 'create', $params);
    } elseif ($get['count'] > 1) {
      CRM_Core_Error::debug_log_message("de.systopia.donrec: warning: $entity exists multiple times: " . print_r($get_params, True));
    }
  }

  /**
   * Create or Update an Entity. We do a lookup for an entity. If it exists it
   * will be updated, otherwise it will be created
   *
   * @param $entity string, name of the entity used with the api
   * @param $params array, parameters the entity will be created with
   * @param $get_params array, parameters to identify already existing entities,
   *        if missing $params will be used.
   */
  protected static function createOrUpdateEntity($entity, $params, $get_params = Null) {
    $get_params = $get_params ? $get_params : $params;
    $get = civicrm_api3($entity, 'get', $get_params);
    if ($get['count'] > 1) {
      CRM_Core_Error::debug_log_message("de.systopia.donrec: warning: tried to update $entity, but got multiple entities: " . print_r($get_params, True));
    } elseif ($get['count'] == 0) {
      civicrm_api3($entity, 'create', $params);
    } elseif ($get['count'] == 1) {
      $params['id'] = $get['id'];
      civicrm_api3($entity, 'create', $params);
    }
  }


  /**
  * Populate $_custom_groups with all the relevant data - if not already done.
  */
  protected static function _getCustomGroupData($group_name) {
    if (!self::$_custom_groups[$group_name]['id']) {
      $params = array(
        'version'  => 3,
        'name'     => $group_name,
      );
      $group = civicrm_api('CustomGroup', 'getsingle', $params);
      self::$_custom_groups[$group_name]['id'] = $group['id'];
      self::$_custom_groups[$group_name]['table_name'] = $group['table_name'];

      $params = array(
        'version'         => 3,
        'custom_group_id' => $group['id'],
        'option.limit'    => 999
      );
      $fields = civicrm_api('CustomField', 'get', $params);
      foreach ($fields['values'] as $field) {
        self::$_custom_groups[$group_name]['fields'][$field['name']] = $field['column_name'];
      }
    }
  }

  /**
  * Returns an array with field-names to their column-names of $group_name
  */
  public static function getCustomFields($group_name) {
    self::_getCustomGroupData($group_name);
    return self::$_custom_groups[$group_name]['fields'];
  }

  /**
  * Returns the table-name of the custom-group $group_name
  */
  public static function getTableName($group_name) {
    self::_getCustomGroupData($group_name);
    return self::$_custom_groups[$group_name]['table_name'];
  }

  /**
   * Upgrade POST-Script for upgrades to 1.3
   */
  protected static function upgrade_1_3() {
    // fill the new receipt_id field in the receipt item
    $receipt_table       = self::getTableName('zwb_donation_receipt');
    $receipt_fields      = self::getCustomFields('zwb_donation_receipt');
    $receipt_item_table  = self::getTableName('zwb_donation_receipt_item');
    $receipt_item_fields = self::getCustomFields('zwb_donation_receipt_item');

    $sql = "UPDATE {$receipt_item_table}
            SET `{$receipt_item_fields['receipt_id']}` = (SELECT COALESCE(`{$receipt_fields['receipt_id']}`, `id`) FROM {$receipt_table} WHERE `id` = `{$receipt_item_table}`.`id`)
            WHERE `{$receipt_item_fields['receipt_id']}` IS NULL";
    CRM_Core_DAO::executeQuery($sql);
  }
}
