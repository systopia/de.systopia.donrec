<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use Civi\Api4\CustomField;

/**
 * This class represents a single donation receipt item
 */
class CRM_Donrec_Logic_ReceiptItem {
  /**
   * Custom field array to map attribute names to database colums
   * i.e. self::$_custom_field['total_amount'] == 10
   */
  // TODO: set private, but add getters
  private static ?array $_custom_fields = NULL;
  public static ?int $_custom_group_id = NULL;
  public static array $_checksum_keys = [
    'contribution_id',
    'status',
    'type',
    'issued_in',
    'receipt_id',
    'issued_by',
    'total_amount',
    'non_deductible_amount',
    'currency',
    'issued_on',
    'receive_date',
  ];

  /**
   * Creates a new receipt item
   *
   * @param array $params array of parameters
   *
   * @return \CRM_Core_DAO
   *   FALSE if there was an error //TODO
   * @throws \CRM_Core_Exception
   */
  public static function create($params) {
    $fields = self::getCustomFields();
    $table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    $params['contribution_hash'] = self::calculateChecksum($params);

    // build set-string
    $set_str = "`entity_id`=$params[contribution_id]";
    foreach ($fields as $key => $field) {
      if (isset($params[$key])) {
        $value = CRM_Utils_DonrecHelper::escapeString($params[$key]);
        $set_str .= ", `$field`='$value'";
      }
    }

    // build query
    $query = "INSERT INTO `$table` SET $set_str";

    // run query
    /** @var \CRM_Core_DAO $result */
    $result = CRM_Core_DAO::executeQuery($query);
    return $result;
  }

  /**
   * Calculate sha1 checksum
   * @param array $params
   * @return string checksum
   */
  public static function calculateChecksum($params) {
    $str = '';
    foreach (self::$_checksum_keys as $key) {
      $str .= $params[$key];
    }
    return sha1($str);
  }

  /**
   * Creates a copy of all donation receipt items of a specific donation receipt
   * @param int $donation_receipt_id
   * @param int $donation_receipt_copy_id
   */
  public static function createCopyAll($donation_receipt_id, $donation_receipt_copy_id) {
    // TODO: make a generic version of this, using the fields defined in CRM_Donrec_DataStructure
    $custom_fields = self::getCustomFields();
    $receipt_item_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    // phpcs:disable Generic.Files.LineLength.TooLong
    $sha1_string = "SHA1(CONCAT(`entity_id`, 'COPY', `%s`, $donation_receipt_copy_id, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`))";
    // phpcs:enable
    $sha1_string = sprintf($sha1_string,
                          $custom_fields['type'],
                          $custom_fields['issued_in'],
                          $custom_fields['receipt_id'],
                          $custom_fields['issued_by'],
                          $custom_fields['total_amount'],
                          $custom_fields['non_deductible_amount'],
                          $custom_fields['financial_type_id'],
                          $custom_fields['currency'],
                          $custom_fields['issued_on'],
                          $custom_fields['receive_date'],
                          self::$_custom_group_id);

    $query = "INSERT INTO `$receipt_item_table`
    (`id`,
      `entity_id`,
      `%s`,
      `%s`,
      `%s`,
      `%s`,
      `%s`,
      `%s`,
      `%s`,
      `%s`,
      `%s`,
      `%s`,
      `%s`,
      `%s`)
    SELECT NULL as `id`,
    `entity_id`,
    'COPY' as `%s`,
    `%s`,
    $donation_receipt_copy_id as `%s`,
    `%s`,
    NOW() as `%s`,
    `%s`,
    `%s`,
    `%s`,
    `%s`,
    `%s`,
    `%s`,
   %s as `%s`
    FROM `$receipt_item_table`
    WHERE `%s` = %d AND `%s` = 'ORIGINAL';";
    $query = sprintf($query,
      // for spec part
      $custom_fields['status'],
      $custom_fields['type'],
      $custom_fields['issued_in'],
      $custom_fields['receipt_id'],
      $custom_fields['issued_on'],
      $custom_fields['issued_by'],
      $custom_fields['total_amount'],
      $custom_fields['non_deductible_amount'],
      $custom_fields['currency'],
      $custom_fields['financial_type_id'],
      $custom_fields['receive_date'],
      $custom_fields['contribution_hash'],
      // for VALUES part
      $custom_fields['status'],
      $custom_fields['type'],
      $custom_fields['issued_in'],
      $custom_fields['receipt_id'],
      $custom_fields['issued_on'],
      $custom_fields['issued_by'],
      $custom_fields['total_amount'],
      $custom_fields['non_deductible_amount'],
      $custom_fields['currency'],
      $custom_fields['financial_type_id'],
      $custom_fields['receive_date'],
      $sha1_string,
      $custom_fields['contribution_hash'],
      $custom_fields['issued_in'],
      $donation_receipt_id,
      $custom_fields['status']
      );
    $result = CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Deletes all contribution items for a specific donation receipt
   * @param int $donation_receipt_id
   * @param string|null $status filter by status (deletes all including copies if not specified)
   */
  public static function deleteAll($donation_receipt_id, $status = NULL) {
    $custom_fields = self::getCustomFields();
    $receipt_item_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    if (!empty($status)) {
      $statusString = sprintf(" AND `%s` = '%s'", $custom_fields['status'], $status);
    }
    else {
      $statusString = '';
    }

    $query = "DELETE FROM `$receipt_item_table` WHERE `%s` = %d%s;";
    $query = sprintf($query,
                    $custom_fields['issued_in'],
                    $donation_receipt_id,
                    $statusString);
    $result = CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Sets status of all contribution items for a specific donation receipt
   * @param int $donation_receipt_id
   * @param string $status
   */
  public static function setStatusAll($donation_receipt_id, $status = 'WITHDRAWN') {
    $custom_fields = self::getCustomFields();
    $receipt_item_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    $query = "UPDATE `$receipt_item_table` SET `%s` = %%1 WHERE `%s` = %d;";
    $query = sprintf($query,
                    $custom_fields['status'],
                    $custom_fields['issued_in'],
                    $donation_receipt_id
                    );
    $params = [1 => [$status, 'String']];
    $result = CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * @return array<string, string>
   *   Mapping of field name in CustomGroup zwb_donation_receipt_item to column
   *   name.
   *
   * @throws \CRM_Core_Exception
   */
  public static function getCustomFields(): array {
    return self::$_custom_fields ??= CustomField::get(FALSE)
      ->addSelect('name', 'column_name')
      ->addWhere('custom_group_id.name', '=', 'zwb_donation_receipt_item')
      ->execute()
      ->indexBy('name')
      ->column('column_name');
  }

  /**
   * Check if a contribution has a receipt-item with status ORIGINAL.
   *
   * return boolean or item id
   * @param int $contribution_id
   * @param bool $return_id
   * @return bool|string|null
   */
  public static function hasValidReceiptItem($contribution_id, $return_id = FALSE) {
    $contribution_id = (int) $contribution_id;
    // prevent SQL errors
    if (empty($contribution_id)) {
      return FALSE;
    }

    $receipt_item_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    $status_field = self::getCustomFields()['status'];

    $query = "
      SELECT `id`
      FROM `$receipt_item_table`
      WHERE `entity_id` = $contribution_id
      AND `$status_field` = 'ORIGINAL'";
    $result = CRM_Core_DAO::singleValueQuery($query);

    if ($return_id && NULL !== $result) {
      return $result;
    }
    else {
      return NULL !== $result;
    }
  }

}
