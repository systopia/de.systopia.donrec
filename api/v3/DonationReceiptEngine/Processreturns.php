<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * Process email returns:
 *  - create activity (if requested)
 *  - withdraw receipt
 */
function civicrm_api3_donation_receipt_engine_processreturns($params) {
  if (!function_exists('imap_open')) {
    throw new Exception("php-imap module not installed/activated.");
  }

  // run it:
  $processor = new CRM_Donrec_Logic_EmailReturnProcessor($params);
  $result = $processor->run();
  return civicrm_api3_create_success($result);
}

/**
 * Adjust Metadata for donation receipt run
 */
function _civicrm_api3_donation_receipt_engine_processreturns_spec(&$params) {
  $params['limit'] = array(
      'name'         => 'limit',
      'api.default'  => 100,
      'type'         => CRM_Utils_Type::T_INT,
      'title'        => 'Processing Limit',
      'description'  => 'Process only this amount at a time. Beware of timeouts.',
  );
  $params['hostname'] = array(
      'name'         => 'hostname',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'IMAP Hostname',
      'description'  => 'Hostname of the IMAP mailbox containing the returns',
  );
  $params['username'] = array(
      'name'         => 'username',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'IMAP User Name',
      'description'  => 'User Name of the IMAP mailbox containing the returns',
  );
  $params['password'] = array(
      'name'         => 'password',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'IMAP Password',
      'description'  => 'Password of the IMAP mailbox containing the returns',
  );
  $params['activity_id'] = array(
      'name'         => 'activity_id',
      'api.required' => 0,
      'type'         => CRM_Utils_Type::T_INT,
      'title'        => 'Create Activity (ID)',
      'description'  => 'If set, creates an activity for each processed return',
  );
  $params['activity_subject'] = array(
      'name'         => 'activity_subject',
      'api.required' => 0,
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'Create Activity (Subject)',
      'description'  => 'Subject of the activity to be created (only if activity_id set)',
  );
  $params['withdraw'] = array(
      'name'         => 'withdraw',
      'api.required' => 0,
      'type'         => CRM_Utils_Type::T_INT,
      'title'        => 'Withdraw',
      'description'  => 'If set, the donation receipt will be withdrawn, if identified.',
  );
  $params['pattern'] = array(
      'name'         => 'pattern',
      'api.required' => 0,
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'Withdraw',
      'description'  => 'Use a custom code pattern. Should contain {contact_id} and {receipt_id}',
  );
}
