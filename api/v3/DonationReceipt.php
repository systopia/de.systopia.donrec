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
 * Withdraw an original donation receipt
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_donation_receipt_withdraw($params) {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(E::ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get($params['rid']);

  if(!empty($receipt)) {
    if($receipt->isOriginal()) {
      // TODO: error-handling...
      // TODO: define status centrally
      $copies = $receipt->getCopies();
      $result = $receipt->setStatus('WITHDRAWN', $params);
      $deleted = $receipt->deleteOriginalFile();
      foreach ($copies as $copy) {
        $result = $copy->setStatus('WITHDRAWN_COPY', $params);
        $deleted = $copy->deleteOriginalFile();
      }
    }else{
      return civicrm_api3_create_error(sprintf(E::ts("Only original donation receipts can be withdrawn."), $params['rid']));
    }
  }else{
    return civicrm_api3_create_error(sprintf(E::ts("Receipt with id %d does not exist."), $params['rid']));
  }
  // and return the result
  return civicrm_api3_create_success($result);
}

/**
 * Adjust Metadata for donation receipt withdraw
 *
 * @param array $params
 */
function _civicrm_api3_donation_receipt_withdraw_spec(&$params) {
    $params['rid']['api.required'] = 1;
}

/**
 * Copy an original donation receipt
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_donation_receipt_copy($params) {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(E::ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get($params['rid']);

  if(!empty($receipt)) {
    if($receipt->isOriginal()) {
      $result = $receipt->createCopy();
    }else{
      return civicrm_api3_create_error(sprintf(E::ts("Only original donation receipts can be copied."), $params['rid']));
    }
  }else{
    return civicrm_api3_create_error(sprintf(E::ts("Receipt with id %d does not exist."), $params['rid']));
  }
  // and return the result
  return civicrm_api3_create_success($result);
}

/**
 * Adjust Metadata for donation receipt copy
 *
 * @param array $params
 */
function _civicrm_api3_donation_receipt_copy_spec(&$params) {
    $params['rid']['api.required'] = 1;
}

/**
 * Delete an donation receipt
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_donation_receipt_delete($params) {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(E::ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get($params['rid']);

  if(!empty($receipt)) {
    $deleted = $receipt->deleteOriginalFile();
    $delete_params = array();
    $result = $receipt->delete($delete_params);
  }else{
    return civicrm_api3_create_error(sprintf(E::ts("Receipt with id %d does not exist."), $params['rid']));
  }
  // and return the result
  return civicrm_api3_create_success($result);
}

/**
 * Adjust Metadata for donation receipt delete
 *
 * @param array $params
 */
function _civicrm_api3_donation_receipt_delete_spec(&$params) {
    $params['rid']['api.required'] = 1;
}

/**
 * View Receipts
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_donation_receipt_view($params) {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(E::ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get($params['rid']);

  if(empty($receipt)) {
    return civicrm_api3_create_error(sprintf(E::ts("Receipt with id %d does not exist."), $params['rid']));
  }
  if (empty($params['name'])) {
    $name = 'View.pdf';
  } else {
    $name = $params['name'];
  }
  $values = $receipt->getAllProperties();
  $profile = $receipt->getProfile();

  // mark this as DRAFT id ORIGINAL
  if (empty($values['watermark'])) {
    $values['status'] = 'DRAFT';
    $values['watermark'] = $profile->getDataAttribute('draft_text');
  }

  $pdf = $profile->getTemplate()->generatePDF($values, $params, $profile);
  $url = CRM_Donrec_Logic_File::createTemporaryFile($pdf, $name);

  // and return the result
  return civicrm_api3_create_success($url);
}

/**
 * Adjust Metadata for donation receipt view
 *
 * @param array $params
 */
function _civicrm_api3_donation_receipt_view_spec(&$params) {
    $params['rid']['api.required'] = 1;
}

/**
 * View Receipts
 *
 * @param array $params
 *
 * @return array
 * @deprecated
 */
// TODO: Thomas: kann das nicht weg?
function civicrm_api3_donation_receipt_details($params) {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(E::ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get($params['rid']);

  if(!empty($receipt)) {
    $details = $receipt->getDetails();
  }else{
    return civicrm_api3_create_error(sprintf(E::ts("Receipt with id %d does not exist."), $params['rid']));
  }
  // and return the result
  return civicrm_api3_create_success($details);
}

/**
 * Adjust Metadata for donation receipt view
 *
 * @param array $params
 */
function _civicrm_api3_donation_receipt_details_spec(&$params) {
    $params['rid']['api.required'] = 1;
}
