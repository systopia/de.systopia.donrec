<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This class holds helper functions
 */
class CRM_Utils_DonrecHelper
{
  /**
  * @param number any number that should be converted to words
  * @param lang language - a factory is implemented, so any class that matches with CRM_Utils_Lang_XX_XX could be called
  *                      - Be aware of proper classnames and folders, for example for es_ES language the proper class should be
  *                      - Es/Es.php (CRM_Utils_Lang_Es_Es)
  */
  public static function convert_number_to_words($number, $lang='de_DE', $params=array()) {
    $helper = CRM_Utils_DonrecLang::factory($lang);
    $string = $helper::toWords($number);
    return $string;
  }

  /**
  * Converts a string to unix timestamp
  * @param string Raw date string (i.e. '10/15/2014')
  * @param int If $clamp is less than 0 the function will return a unix timestamp
  *            set to 00:00 of the given date. If it is greater than 1, it will
  *            return a value clamped to 23:59:59 of the same day.
  * @param format
  * @return string timestamp
  */
  public static function convertDate($raw_date, $clamp=0, $format = 'm/d/Y') {
    $date = FALSE;
    if (!empty($raw_date)) {
      $date_object = DateTime::createFromFormat($format, $raw_date, new DateTimeZone('Europe/Berlin'));
      if ($date_object) {
        if($clamp < 0) {
          // set to [date format] 00:00:00
          $date = strtotime(sprintf("%s-%s-%s 00:00:00", $date_object->format('Y'), $date_object->format('m'), $date_object->format('d')));
        }else if($clamp > 0) {
          // set to [date format] 23:59:59
          $date = strtotime(sprintf("%s-%s-%s 23:59:59", $date_object->format('Y'), $date_object->format('m'), $date_object->format('d')));
        }
      }
    }
    return $date;
  }
  /**
  * Calls die() with a pretty template (WIP)
  *
  */
  public static function exitWithMessage($error_message) {
    $smarty = CRM_Core_Smarty::singleton();
    $template = file_get_contents(dirname(__DIR__) . '../../templates/fatal_error.tpl');

    // assign values
    $smarty->assign('title', ts('Error', array('domain' => 'de.systopia.donrec')));
    $smarty->assign('headline', ts('Error', array('domain' => 'de.systopia.donrec')));
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
   * @return lock object. check if it ->isAcquired() before use
   */
  public static function getLock($type, $id) {
    if ($type=='') {
      // for the 'next' lock, we calculate the lock timeout as follows
      $max_lock_timeout = ini_get('max_execution_time');
      if (empty($max_lock_timeout)) {
        $max_lock_timeout = 30 * 60; // 30 minutes
      }

      // calculate based on chunk size (max 1min/item)
      $calculation_time = CRM_Donrec_Logic_Settings::getChunkSize() * 60;
      $timeout = min($calculation_time, $max_lock_timeout);

    } else {
      // default timeout for other locks
      $timeout = 600.0; // 10mins, TODO: do we need a setting here?
    }

    //CRM_Core_Error::debug_log_message("de.systopia.donrec.$type".'-'.$id." timeout $timeout created.");
    return CRM_Utils_DonrecSafeLock::acquireLock("de.systopia.donrec.$type".'-'.$id, $timeout);
  }

  /**
   * extracts the field ID from the field set provided in the format:
   * <field_name> => <column_name>
   *
   * @return (int) field_id  or 0 if not found
   */
  public static function getFieldID($fields, $field_name) {
    if (!empty($fields[$field_name])) {
      // TODO: more efficient way?
      $inv_column = strrev($fields[$field_name]);
      $inv_id = substr($inv_column, 0, strpos($inv_column, '_'));
      return (int) strrev($inv_id);
    } else {
      return 0;
    }
  }

  /**
   * removes a field from a form - if it exists
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
          throw new CRM_Core_Exception("Cannot generate SQL. \"mysql_{real_}escape_string\" is missing. Have you installed PHP \"mysql\" extension?");
        }
      }

      $_dao = new CRM_Core_DAO();
    }

    return $_dao->escape($string);
  }
}
