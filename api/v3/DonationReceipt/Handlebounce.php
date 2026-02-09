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

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

/**
 * DonationReceipt.Handlebounce API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array<string, array<string, mixed>> $spec description of fields supported by this API call
 */
function _civicrm_api3_donation_receipt_Handlebounce_spec(array &$spec): void {
  $spec['contact_id']['api.required'] = 1;
  $spec['contribution_id']['api.required'] = 1;
  $spec['timestamp']['api.required'] = 1;
  $spec['profile_id']['api.required'] = 1;
}

/**
 * DonationReceipt.Handlebounce API
 *
 * @param array<string, mixed> $params
 *
 * @return array<string, mixed>
 */
function civicrm_api3_donation_receipt_Handlebounce(array $params): array {
  $config_data = get_config_data((int) $params['profile_id']);
  try {
    $email_processor = new CRM_Donrec_Logic_EmailReturnProcessor($config_data, TRUE);
    [$contact_id, $receipt_id] = $email_processor->get_receipt_id(
      (int) $params['contribution_id'],
      $params['timestamp'],
      (int) $params['contact_id']
    );
    if ($email_processor->processBounce(
      $contact_id,
      $receipt_id,
      get_activity_source_id((int) $params['profile_id'])
    )) {
      return civicrm_api3_create_success(
        "Parsed Bounce event for Contact {$contact_id} with Contribution {$params['contribution_id']}"
      );
    }

    return civicrm_api3_create_error('Failed to process bounce.');
  }
  catch (Exception $e) {
    // @ignoreException
    Civi::log()->debug(
      '[de.systopia.donrec] Failed to process bounce message for contact ' . $params['contact_id'] .
      '. Error Message ' . $e->getMessage(),
      ['exception' => $e]
    );
    return civicrm_api3_create_error("Failed to parse Bounce Message. Reason: {$e->getMessage()}");
  }

}

/**
 * Helper function to get configured activity_source_id for given profile
 *
 * @return int|null
 */
function get_activity_source_id(int $profile_id) {
  $id = CRM_Donrec_Logic_Profile::getProfile($profile_id)->getDataAttribute('special_mail_activity_contact_id');

  return is_numeric($id) ? (int) $id : NULL;
}

/**
 * helper function to get configuration data for specified profile
 *
 * @return array<string, mixed>
 */
function get_config_data(int $profile_id): array {
  $config_params = [];
  $config_params['activity_type_id'] = CRM_Donrec_Logic_Profile::getProfile($profile_id)
    ->getDataAttribute('special_mail_activity_id');
  $config_params['withdraw'] = CRM_Donrec_Logic_Profile::getProfile($profile_id)
    ->getDataAttribute('special_mail_withdraw_receipt');
  $config_params['activity_subject'] = CRM_Donrec_Logic_Profile::getProfile($profile_id)
    ->getDataAttribute('special_mail_activity_subject');
  return $config_params;
}
