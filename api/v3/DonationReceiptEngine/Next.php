<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * Execute the next chunk/step of the donation receipt run
 *
 * @param array $params
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_donation_receipt_engine_next($params) {
  // first, check if the snapshot ID is there
  if (empty($params['sid'])) {
    return civicrm_api3_create_error(ts("No 'sid' parameter given.", array('domain' => 'de.systopia.donrec')));
  }

  // Init the engine
  $sid = (int) $params['sid'];
  $engine = new CRM_Donrec_Logic_Engine();
  $engine_error = $engine->init($sid, $params);
  if ($engine_error) {
    return civicrm_api3_create_error($engine_error);
  }

  // just run the next step
  $result = $engine->nextStep();

  // and return the result
  return civicrm_api3_create_success($result);
}

/**
 * Adjust Metadata for donation receipt run
 *
 * @param array $params
 */
function _civicrm_api3_donation_receipt_engine_next_spec(&$params) {
    $params['sid']['api.required'] = 1;
}
