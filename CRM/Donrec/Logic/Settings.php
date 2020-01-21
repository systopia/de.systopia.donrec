<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N. Bochan (bochan -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

/**
 * This class holds all settings related functions
 */
class CRM_Donrec_Logic_Settings {

  // FIXME:: remove
  public static $SETTINGS_GROUP = "Donation Receipt Settings";


  /**
   * generic set setting
   *
   * @param string $name
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function get($name) {
    return civicrm_api3('Setting', 'getvalue', array('name' => $name));
  }

  /**
   * generic set setting
   * @param string $name
   * @param mixed $value
   * @throws \CiviCRM_API3_Exception
*/
  public static function set($name, $value) {
    civicrm_api3('Setting', 'create', array($name => $value));
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
      'option.limit' => 0));
    foreach ($all_templates['values'] as $template) {
      // TODO: filter?
      $relevant_templates[$template['id']] = $template['msg_title'];
    }

    return $relevant_templates;
  }

  public static function getWatermarkPresets() {
    return array(
      CRM_Donrec_Logic_WatermarkPreset_DompdfTraditional::getName() => CRM_Donrec_Logic_WatermarkPreset_DompdfTraditional::getLabel(),
      CRM_Donrec_Logic_WatermarkPreset_WkhtmltopdfTraditional::getName() => CRM_Donrec_Logic_WatermarkPreset_WkhtmltopdfTraditional::getLabel(),
      CRM_Donrec_Logic_WatermarkPreset_SvgAcrossLarge::getName() => CRM_Donrec_Logic_WatermarkPreset_SvgAcrossLarge::getLabel(),
      CRM_Donrec_Logic_WatermarkPreset_SvgAcrossSmall::getName() => CRM_Donrec_Logic_WatermarkPreset_SvgAcrossSmall::getLabel(),
      CRM_Donrec_Logic_WatermarkPreset_SvgUpperRightCorner::getName() => CRM_Donrec_Logic_WatermarkPreset_SvgUpperRightCorner::getLabel(),
      CRM_Donrec_Logic_WatermarkPreset_SvgTopCenter::getName() => CRM_Donrec_Logic_WatermarkPreset_SvgTopCenter::getLabel(),
      CRM_Donrec_Logic_WatermarkPreset_SvgHolohedral::getName() => CRM_Donrec_Logic_WatermarkPreset_SvgHolohedral::getLabel(),
    );
  }

  /**
   * get the chunk size
   *
   * @return int
   */
  public static function getChunkSize() {
    $packet_size = (int) civicrm_api3('Setting', 'getvalue', array('name' => 'donrec_packet_size'));
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

  /**
   * Get the internal ID of the selected template for sending emails
   *
   * @return int
   */
  public static function getEmailTemplateID() {
    $template_id = (int) civicrm_api3('Setting', 'getvalue', array('name' => 'donrec_email_template'));
    if ($template_id >= 1) {
      return $template_id;
    } else {
      return NULL;
    }
  }

  /**
   * Get all unlocking options for contribution field locks.
   *
   * @return array
   */
  public static function getContributionUnlockableFields() {
    return array(
      'financial_type_id' => E::ts('Financial type'),
      'campaign_id' => E::ts('Campaign'),
      'payment_instrument_id' => E::ts('Payment method'),
      'contribution_status_id' => E::ts('Contribution status'),
      'source' => E::ts('Source'),
      'receive_date' => E::ts('Receive date'),
      'total_amount' => E::ts('Total amount'),
      'currency' => E::ts('Currency'),
      'note' => E::ts('Note'),
      'custom_fields' => E::ts('Custom fields'),
    );
  }

  /**
   * @param int $contribution_id
   *   The ID of the contribution.
   * @param array $old_values
   *   An array of current values, keyed by contribution property name.
   * @param array $new_values
   *   An array of values to be set, keyed by contribution property name.
   * @param bool $throw_exception
   *   Whether to throw an exception in case of failed validation.
   *
   * @return array
   *   Error messages, keyed by contribution property name.
   * @throws \Exception
   *   When $throw_exception was passed TRUE in case of failed validation.
   */
  public static function validateContribution($contribution_id, $old_values, $new_values, $throw_exception = FALSE) {
    $errors = array();

    $receipt_id = CRM_Donrec_Logic_ReceiptItem::hasValidReceiptItem($contribution_id, TRUE);
    $snapshot_id = CRM_Donrec_Logic_Snapshot::isInOpenSnapshot($contribution_id, TRUE);

    if (!is_null($receipt_id) || !is_null($snapshot_id)) {
      // Get all locked fields.
      $unlockable_fields = array_keys(CRM_Donrec_Logic_Settings::getContributionUnlockableFields());
      // Add custom fields to array of locked fields.
      $custom_fields = array_filter(array_keys($new_values), function($field) {
        return strpos($field, 'custom_') === 0;
      });
      $unlockable_fields = array_merge($unlockable_fields, $custom_fields);
      unset($unlockable_fields[array_search('custom_fields', $unlockable_fields)]);

      // Retrieve unlock settings from profile.
      if (!is_null($snapshot_id)) {
        $unlock_mode = CRM_Donrec_Logic_Snapshot::get($snapshot_id)->getProfile()->getDataAttribute('contribution_unlock_mode');
        $unlock_fields = CRM_Donrec_Logic_Snapshot::get($snapshot_id)->getProfile()->getDataAttribute('contribution_unlock_fields');
      }
      elseif (!is_null($receipt_id)) {
        $unlock_mode = CRM_Donrec_Logic_Receipt::get($receipt_id)->getProfile()->getDataAttribute('contribution_unlock_mode');
        $unlock_fields = CRM_Donrec_Logic_Receipt::get($receipt_id)->getProfile()->getDataAttribute('contribution_unlock_fields');
      }
      switch ($unlock_mode) {
        case 'unlock_all':
          $allowed = $unlockable_fields;
          break;
        case 'unlock_none':
          $allowed = array();
          break;
        case 'unlock_selected':
          $allowed = array_keys(array_filter(
            $unlock_fields,
            function($value) {
              return $value == 1;
            }
          ));
          // Add custom fields to allowed if so configured.
          if (in_array('custom_fields', $allowed)) {
            $custom_fields = array_filter(array_keys($new_values), function($field) {
              return strpos($field, 'custom_') === 0;
            });
            $allowed = array_merge($allowed, $custom_fields);
            unset($allowed[array_search('custom_fields', $allowed)]);
          }
          break;
      }

      // Check if forbidden columns are going to be changed.
      foreach ($unlockable_fields as $col) {
        if (!in_array($col, $allowed)) {
          if (isset($new_values[$col]) && $old_values[$col] != $new_values[$col]) {

            // we need a special check for dates
            if (strpos($col, 'date')) {
              // this approach does not considers seconds!
              // (some input-formats does not allow the input of seconds at all)
              $new_date = date('d/m/Y H:i', strtotime($new_values['receive_date'] . ' ' . $new_values['receive_date_time']));
              $old_date = date('d/m/Y H:i', strtotime($old_values['receive_date']));
              if ($new_date == $old_date) {
                continue;
              }
            }

            // and another one for amounts
            if (strpos($col, 'amount')) {
              // The old amount is stored as a float-like string.
              $old_amount = floatval($old_values[$col]);

              $new_amount = CRM_Utils_Rule::cleanMoney($new_values[$col]);
              $new_amount = floatval($new_amount);

              if ($new_amount == $old_amount) {
                continue;
              }
            }

            $errors[$col] = sprintf(E::ts("A donation receipt has been issued for this contribution, or is being processed for a receipt right now. You are not allowed to change the value for '%s'."), ts($col));
            if ($throw_exception) {
              throw new Exception($errors[$col]);
            }
          }
        }
      }
    }

    return $errors;
  }
}
