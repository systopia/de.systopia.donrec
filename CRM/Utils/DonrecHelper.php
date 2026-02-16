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

use CRM_Donrec_ExtensionUtil as E;

/**
 * This class holds helper functions
 */
class CRM_Utils_DonrecHelper {

  /**
   * Converts a string to unix timestamp
   * @param string $raw_date Raw date string (i.e. '10/15/2014')
   * @param int $clamp If $clamp is less than 0 the function will return a unix timestamp
   *            set to 00:00 of the given date. If it is greater than 1, it will
   *            return a value clamped to 23:59:59 of the same day.
   * @param string $format
   * @return int|FALSE timestamp
   */
  public static function convertDate($raw_date, $clamp = 0, $format = 'm/d/Y') {
    $date = FALSE;
    if (!empty($raw_date)) {
      $date_object = DateTime::createFromFormat($format, $raw_date, new DateTimeZone('Europe/Berlin'));
      if ($date_object) {
        if ($clamp < 0) {
          // set to [date format] 00:00:00
          $date = strtotime(sprintf(
            '%s-%s-%s 00:00:00',
            $date_object->format('Y'),
            $date_object->format('m'),
            $date_object->format('d')
          ));
        }
        elseif ($clamp > 0) {
          // set to [date format] 23:59:59
          $date = strtotime(sprintf(
            '%s-%s-%s 23:59:59',
            $date_object->format('Y'),
            $date_object->format('m'),
            $date_object->format('d')
          ));
        }
      }
    }
    return $date;
  }

  /**
   * Calls die() with a pretty template (WIP)
   *
   * @param string $error_message
   *
   * @deprecated 1.8 No longer used by internal code and not recommended.
   */
  public static function exitWithMessage($error_message) {
    $smarty = CRM_Core_Smarty::singleton();
    $template = file_get_contents(dirname(__DIR__) . '../../templates/fatal_error.tpl');

    // assign values
    $smarty->assign('title', E::ts('Error'));
    $smarty->assign('headline', E::ts('Error'));
    $smarty->assign('description', $error_message);

    $html = $smarty->fetch("string:$template");
    exit($html);
  }

  /**
   * Get a batching lock
   *
   * the lock is needed so that only one relevant process can access the
   * payment/statment data structures at a time
   *
   * @param $type
   *
   * @param $id
   *
   * @return \CRM_Utils_DonrecSafeLock | NULL
   *   lock object. check if it ->isAcquired() before use
   */
  public static function getLock($type, $id) {
    if ($type == '') {
      // for the 'next' lock, we calculate the lock timeout as follows
      $max_lock_timeout = (int) ini_get('max_execution_time');
      if (0 === $max_lock_timeout) {
        // 30 minutes
        $max_lock_timeout = 30 * 60;
      }

      // calculate based on chunk size (max 1min/item)
      $calculation_time = CRM_Donrec_Logic_Settings::getChunkSize() * 60;
      $timeout = min($calculation_time, $max_lock_timeout);

    }
    else {
      // default timeout for other locks
      // 10mins, TODO: do we need a setting here?
      $timeout = 600;
    }

    return CRM_Utils_DonrecSafeLock::acquireLock("de.systopia.donrec.$type" . '-' . $id, $timeout);
  }

  /**
   * extracts the field ID from the field set provided in the format:
   * <field_name> => <column_name>
   *
   * @param array $fields
   *
   * @param string $field_name
   *
   * @return int
   *   field_id  or 0 if not found
   */
  public static function getFieldID($fields, $field_name) {
    if (!empty($fields[$field_name])) {
      // TODO: more efficient way?
      $inv_column = strrev($fields[$field_name]);
      $inv_id = substr($inv_column, 0, strpos($inv_column, '_'));
      return (int) strrev($inv_id);
    }
    else {
      return 0;
    }
  }

  /**
   * removes a field from a form - if it exists
   *
   * @param \CRM_Core_Form $form
   *
   * @param array $fields
   *
   * @param string $field_name
   */
  public static function removeFromForm(&$form, $fields, $field_name) {
    $field_id = self::getFieldID($fields, $field_name);
    if ($field_id) {
      if ($form->elementExists("custom_{$field_id}")) {
        $form->removeElement("custom_{$field_id}");
      }
    }
  }

  /**
   * updates a date field's labels - if it exists
   *
   * @param $form
   * @param $fields
   * @param $field_name
   * @param $from_label
   * @param $to_label
   */
  public static function relabelDateField(&$form, $fields, $field_name, $from_label, $to_label) {
    $field_id = self::getFieldID($fields, $field_name);
    if ($field_id) {
      if ($form->elementExists("custom_{$field_id}_from")) {
        $form->getElement("custom_{$field_id}_from")->setLabel($from_label);
      }
      if ($form->elementExists("custom_{$field_id}_to")) {
        $form->getElement("custom_{$field_id}_to")->setLabel($to_label);
      }
    }
  }

  /**
   * SQL-escape the given string
   * (slightly abridged version of CRM_Core_DAO::escapeString)
   *
   * @param string $string
   *
   * @return false|string
   * @throws \CRM_Core_Exception
   * @see CRM_Core_DAO::escapeString
   */
  public static function escapeString($string) {
    static $_dao = NULL;

    if (!$_dao) {
      if (!defined('CIVICRM_DSN')) {
        if (function_exists('mysql_real_escape_string')) {
          return mysql_real_escape_string($string);
        }
        elseif (function_exists('mysql_escape_string')) {
          return mysql_escape_string($string);
        }
        else {
          throw new CRM_Core_Exception(
            'Cannot generate SQL. "mysql_{real_}escape_string" is missing. Have you installed PHP "mysql" extension?'
          );
        }
      }

      $_dao = new CRM_Core_DAO();
    }

    return $_dao->escape($string);
  }

}
