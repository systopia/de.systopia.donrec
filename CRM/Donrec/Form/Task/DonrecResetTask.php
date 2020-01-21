<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

require_once 'CRM/Core/Form.php';

use CRM_Donrec_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Donrec_Form_Task_DonrecResetTask extends CRM_Contact_Form_Task {

  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Withdraw Donation Receipts', array('domain' => 'de.systopia.donrec')));

    $this->add(
        'datepicker',
        'from_date',
        E::ts("From Date"),
        [],
        TRUE);

    $this->add(
        'datepicker',
        'to_date',
        E::ts("To Date"),
        [],
        TRUE);

    // get some stats
    $count = $this->getCount();
    $duplicate_count = $this->getDuplicateCount();
    if ($duplicate_count) {
      $this->assign('duplicate_warning', E::ts("Caution: %1 contact(s) will have more than one donation receipt withdrawn!", [1 => $duplicate_count]));
    }

    $this->addButtons(array(
        array(
            'type' => 'submit',
            'name' => E::ts('Find'),
            'isDefault' => FALSE,
        ),
        array(
            'type' => 'next',
            'name' => E::ts('Withdraw %1 Receipts', [1 => $count]),
            'isDefault' => TRUE,
        ),
    ));
  }

  /**
   * Process user confirmation/update
   */
  function postProcess() {
    if ($this->controller->_actionName[1] == 'next') {
      // WITHDRAW!!
      $success_counter = $error_counter = 0;
      $receipt_query = CRM_Core_DAO::executeQuery("SELECT receipt.id AS receipt_id " . $this->getBaseSQL());
      while ($receipt_query->fetch()) {
        try {
          civicrm_api3('DonationReceipt', 'withdraw', ['rid' => $receipt_query->receipt_id]);
          $success_counter += 1;
        } catch(CiviCRM_API3_Exception $ex) {
          $error_counter += 1;
        }
      }

      // show message
      CRM_Core_Session::setStatus(E::ts("%1 donation receipts have been withdrawn.", [1 => $success_counter]), E::ts('Receipts Withdrawn'), 'info');
      if ($error_counter) {
        CRM_Core_Session::setStatus(E::ts("%1 donation receipts have not been withdrawn and encountered an error.", [1 => $error_counter]), E::ts('Error'), 'error');
      }
    }
  }


  /**
   * Calculate the amount of donation receipts that would be withdrawn
   */
  protected function getCount() {
    return CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) " . $this->getBaseSQL());
  }

  /**
   * Calculate the amount of donation receipts that would be withdrawn
   */
  protected function getDuplicateCount() {
    // a little hack, but it should work...
    return CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM (SELECT COUNT(*) AS count_per_contact " . $this->getBaseSQL('GROUP BY entity_id HAVING count_per_contact > 1) temp'));
  }

  /**
   * Generate a basic SQL statement without SELECT clause
   *
   * @param string $modifiers
   *
   * @return string
   */
  protected function getBaseSQL($modifiers = '') {
    $where_clauses = [];
    $receipt_fields = CRM_Donrec_DataStructure::getCustomFields('zwb_donation_receipt');

    // add contact clause
    $contacts = implode(',', $this->_contactIds);
    $where_clauses[] = "receipt.entity_id IN ({$contacts})";

    // add status clause
    $status_field_name = $receipt_fields['status'];
    $where_clauses[] = "receipt.{$status_field_name} = 'ORIGINAL'";

    // add date clauses
    $issued_on_field_name = $receipt_fields['issued_on'];
    if (!empty($this->_submitValues['from_date'])) {
      $where_clauses[] = "receipt.{$issued_on_field_name} >= '{$this->_submitValues['from_date']}'";
    }
    if (!empty($this->_submitValues['to_date'])) {
      $where_clauses[] = "receipt.{$issued_on_field_name} <= '{$this->_submitValues['to_date']}'";
    }

    // compile query
    $table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $where = implode(') AND (', $where_clauses);
    return "FROM {$table} receipt WHERE ({$where}) {$modifiers}";
  }
}
