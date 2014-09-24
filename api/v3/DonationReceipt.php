<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N. Bochan (bochan -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * Withdraw an original donation receipt
 */
function civicrm_api3_donation_receipt_withdraw($params) {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get($params['rid']);

  if(!empty($receipt)) {
    $result = $receipt->markWithdrawn();
  }else{
    return civicrm_api3_create_error(sprintf(ts("Receipt with id %d does not exist."), $params['rid']));
  }
  // and return the result
  return civicrm_api3_create_success($result);
}
  
/**
 * Adjust Metadata for donation receipt withdraw
 */
function _civicrm_api3_donation_receipt_withdraw_spec(&$params) {
    $params['rid']['api.required'] = 1;
}

/**
 * Copy an original donation receipt
 */
function civicrm_api3_donation_receipt_copy($params) {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get($params['rid']);

  if(!empty($receipt)) {
    if($receipt->isOriginal()) {
      $result = $receipt->createCopy();
    }else{
      return civicrm_api3_create_error(sprintf(ts("Only original donation receipts can be copied."), $params['rid']));
    }
  }else{
    return civicrm_api3_create_error(sprintf(ts("Receipt with id %d does not exist."), $params['rid']));
  }
  // and return the result
  return civicrm_api3_create_success($result);
}
  
/**
 * Adjust Metadata for donation receipt copy
 */
function _civicrm_api3_donation_receipt_copy_spec(&$params) {
    $params['rid']['api.required'] = 1;
}

/**
 * Delete an donation receipt
 */
function civicrm_api3_donation_receipt_delete($params) {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get($params['rid']);

  if(!empty($receipt)) {
    $delete_params = array();
    $result = $receipt->delete($delete_params);
  }else{
    return civicrm_api3_create_error(sprintf(ts("Receipt with id %d does not exist."), $params['rid']));
  }
  // and return the result
  return civicrm_api3_create_success($result);
}
  
/**
 * Adjust Metadata for donation receipt delete
 */
function _civicrm_api3_donation_receipt_delete_spec(&$params) {
    $params['rid']['api.required'] = 1;
}
