<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: P. Batroff (batroff@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

/**
 * DonationReceipt.Handlebounce API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_donation_receipt_Handlebounce_spec(&$spec) {
  $spec['contact_id']['api.required'] = 1;
  $spec['contribution_id']['api.required'] = 1;
  $spec['timestamp']['api.required'] = 1;
  $spec['profile_id']['api.required'] = 1;
}

/**
 * DonationReceipt.Handlebounce API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws CRM_Core_Exception
 */
function civicrm_api3_donation_receipt_Handlebounce($params) {
  $config_data = get_config_data($params['profile_id']);
  try {
    $email_processor = new CRM_Donrec_Logic_EmailReturnProcessor($config_data, TRUE);
    [$contact_id, $receipt_id]= $email_processor->get_receipt_id($params['contribution_id'], $params['timestamp'], $params['contact_id']);
    if ($email_processor->processBounce($contact_id, $receipt_id, get_activity_source_id($params['profile_id']))) {
      return civicrm_api3_create_success("Parsed Bounce event for Contact {$contact_id} with Contribution {$params['contribution_id']}");
    }
  } catch (Exception $e) {
    CRM_Core_Error::debug_log_message("[de.systopia.donrec] Failed to process bounce message for contact {$params['contact_id']}. Error Message {$e->getMessage()}");
    civicrm_api3_create_error ("Failed to parse Bounce Message. Reason: {$e->getMessage()}");
  }

}

/**
 * Helper function to get configured activity_source_id for given profile
 * @param $profile_id
 *
 * @return mixed
 */
function get_activity_source_id($profile_id) {
  return CRM_Donrec_Logic_Profile::getProfile($profile_id)->getDataAttribute('special_mail_activity_contact_id');
}

/**
 * helper function to get configuration data for specified profile
 * @param $profile_id
 *
 * @return array
 */
function get_config_data($profile_id) {
  $config_params = [];
  $config_params['activity_type_id'] = CRM_Donrec_Logic_Profile::getProfile($profile_id)->getDataAttribute('special_mail_activity_id');
  $config_params['withdraw'] = CRM_Donrec_Logic_Profile::getProfile($profile_id)->getDataAttribute('special_mail_withdraw_receipt');
  $config_params['activity_subject'] = CRM_Donrec_Logic_Profile::getProfile($profile_id)->getDataAttribute('special_mail_activity_subject');
  return $config_params;
}
