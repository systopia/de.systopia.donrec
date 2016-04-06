<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2015 SYSTOPIA                       |
| Author: N. Bochan (bochan -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This class holds all settings related functions
 */
class CRM_Donrec_Logic_Settings {

  public static $SETTINGS_GROUP = "Donation Receipt Settings";

  /**
   * get the default template ID
   *
   * @return int
   */
  public static function getDefaultTemplate() {
    return CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'default_template');
  }

  /**
   * get all eligable(?) templates
   *
   * @return array
   */
  public static function getAllTemplates() {
    $relevant_templates = array();
    $all_templates = civicrm_api3('MessageTemplate', 'get', array(
      'is_active'    => 1,
      'is_reserved'  => 0,
      'option.limit' => 9999));
    foreach ($all_templates['values'] as $template) {
      // TODO: filter?
      $relevant_templates[$template['id']] = $template['msg_title'];
    }

    // add default, if not yet in there
    $default_template_id = self::getDefaultTemplate();
    if (empty($relevant_templates[$default_template_id])) {
      $default_template = civicrm_api3('MessageTemplate', 'getsingle', array('id' => $default_template_id));
      $relevant_templates[$default_template_id] = $default_template['msg_title'];
    }

    return $relevant_templates;
  }

  /**
   * set the default template ID
   */
  public static function setDefaultTemplate($id) {
    CRM_Core_BAO_Setting::setItem($id,'Donation Receipt Settings', 'default_template');
  }

  /**
   * get the chunk size
   *
   * @return int
   */
  public static function getChunkSize() {
    $packet_size = (int) CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'packet_size');
    if ($packet_size >= 1) {
      return $packet_size;
    } else {
      return 1;
    }
  }

  /**
   * Retrieve contact id of the logged in user
   * @return integer | NULL contact ID of logged in user
   */
  static function getLoggedInContactID() {
    $session = CRM_Core_Session::singleton();
    if (!is_numeric($session->get('userID'))) {
      return NULL;
    }
    return $session->get('userID');
  }
}
