<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N. Bochan (bochan -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

/**
 * Withdraw an original donation receipt
 *
 * @param array<string, mixed> $params
 *
 * @return array<string, mixed>
 */
function civicrm_api3_donation_receipt_withdraw(array $params): array {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(E::ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get((int) $params['rid']);

  if (!empty($receipt)) {
    if ($receipt->isOriginal()) {
      // TODO: error-handling...
      // TODO: define status centrally
      $copies = $receipt->getCopies();
      $result = $receipt->setStatus('WITHDRAWN');
      $deleted = $receipt->deleteOriginalFile();
      foreach ($copies as $copy) {
        $result = $copy->setStatus('WITHDRAWN_COPY');
        $deleted = $copy->deleteOriginalFile();
      }
    }
    else {
      return civicrm_api3_create_error(E::ts('Only original donation receipts can be withdrawn.'));
    }
  }
  else {
    return civicrm_api3_create_error(E::ts('Receipt with id %d does not exist.'));
  }
  // and return the result
  return civicrm_api3_create_success($result);
}

/**
 * Adjust Metadata for donation receipt withdraw
 *
 * @param array<string, array<string, mixed>> $params
 */
function _civicrm_api3_donation_receipt_withdraw_spec(array &$params): void {
  $params['rid']['api.required'] = 1;
}

/**
 * Copy an original donation receipt
 *
 * @param array<string, mixed> $params
 *
 * @return array<string, mixed>
 */
function civicrm_api3_donation_receipt_copy(array $params): array {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(E::ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get((int) $params['rid']);

  if (!empty($receipt)) {
    if ($receipt->isOriginal()) {
      $result = $receipt->createCopy();
    }
    else {
      return civicrm_api3_create_error(E::ts('Only original donation receipts can be copied.'));
    }
  }
  else {
    return civicrm_api3_create_error(E::ts('Receipt with id %d does not exist.'));
  }
  // and return the result
  return civicrm_api3_create_success($result);
}

/**
 * Adjust Metadata for donation receipt copy
 *
 * @param array<string, array<string, mixed>> $params
 */
function _civicrm_api3_donation_receipt_copy_spec(array &$params): void {
  $params['rid']['api.required'] = 1;
}

/**
 * Delete an donation receipt
 *
 * @param array<string, mixed> $params
 *
 * @return array<string, mixed>
 */
function civicrm_api3_donation_receipt_delete(array $params): array {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(E::ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get((int) $params['rid']);

  if (!empty($receipt)) {
    $deleted = $receipt->deleteOriginalFile();
    $delete_params = [];
    $result = $receipt->delete($delete_params);
  }
  else {
    return civicrm_api3_create_error(E::ts('Receipt with id %d does not exist.'));
  }
  // and return the result
  return civicrm_api3_create_success($result);
}

/**
 * Adjust Metadata for donation receipt delete
 *
 * @param array<string, array<string, mixed>> $params
 */
function _civicrm_api3_donation_receipt_delete_spec(array &$params): void {
  $params['rid']['api.required'] = 1;
}

/**
 * View Receipts
 *
 * @param array<string, mixed> $params
 *
 * @return array<string, mixed>
 */
function civicrm_api3_donation_receipt_view(array $params): array {
  // check for missing receipt id parameter
  if (empty($params['rid'])) {
    return civicrm_api3_create_error(E::ts("No 'rid' parameter given."));
  }

  $receipt = CRM_Donrec_Logic_Receipt::get((int) $params['rid']);

  if (empty($receipt)) {
    return civicrm_api3_create_error(E::ts('Receipt with id %d does not exist.'));
  }
  if (empty($params['name'])) {
    $name = 'View.pdf';
  }
  else {
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
 * @param array<string, array<string, mixed>> $params
 */
function _civicrm_api3_donation_receipt_view_spec(array &$params): void {
  $params['rid']['api.required'] = 1;
}
