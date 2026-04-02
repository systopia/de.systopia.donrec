<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: T. Leichtfuss (leichtfuss -at- systopia.de)    |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

/**
 * Class to manage the data-structure.
 */
class CRM_Donrec_DataStructure {

  /**
   * Array to cache the database-details of custom-groups and -fields.
   */
  protected static array $_custom_groups = [];

  /**
   * Find the first available option value id
   *
   * @return int|FALSE
   *   option-value-id
   */
  public static function getFirstUsedOptionValueId() {
    $optionGroup = civicrm_api3('OptionGroup', 'getsingle', ['name' => 'donrec_status']);
    if (!empty($optionGroup['is_error'])) {
      return FALSE;
    }
    $id = civicrm_api3('OptionValue', 'get', ['option_group_id' => $optionGroup['id']]);
    if (!empty($id['is_error']) || $id['count'] < 1) {
      return FALSE;
    }
    // return first value
    $id = array_values($id['values']);
    return (int) $id[0]['id'];
  }

  /**
   * Populate $_custom_groups with all the relevant data - if not already done.
   *
   * @param string $group_name
   */
  protected static function _getCustomGroupData($group_name) {
    if (!isset(self::$_custom_groups[$group_name])) {
      $params = [
        'name' => $group_name,
      ];
      $group = civicrm_api3('CustomGroup', 'getsingle', $params);
      self::$_custom_groups[$group_name]['id'] = $group['id'];
      self::$_custom_groups[$group_name]['table_name'] = $group['table_name'];

      $params = [
        'custom_group_id' => $group['id'],
        'option.limit'    => 999,
      ];
      $fields = civicrm_api3('CustomField', 'get', $params);
      foreach ($fields['values'] as $field) {
        self::$_custom_groups[$group_name]['fields'][$field['name']] = $field['column_name'];
      }
    }
  }

  /**
   * Returns an array with field-names to their column-names of $group_name
   *
   * @param string $group_name
   *
   * @return array
   */
  public static function getCustomFields($group_name) {
    self::_getCustomGroupData($group_name);
    return self::$_custom_groups[$group_name]['fields'];
  }

  /**
   * Returns the table-name of the custom-group $group_name
   *
   * @param string $group_name
   *
   * @return string
   */
  public static function getTableName($group_name) {
    self::_getCustomGroupData($group_name);
    return self::$_custom_groups[$group_name]['table_name'];
  }

}
