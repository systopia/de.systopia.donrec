<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

/**
 * Form controller class
 */
class CRM_Donrec_Form_Task_Rebook extends CRM_Core_Form {

  protected array $contribution_ids = [];

  public function preProcess(): void {
    parent::preProcess();
    CRM_Utils_System::setTitle(E::ts('Rebook'));

    $admin = CRM_Core_Permission::check('edit contributions');
    if (!$admin) {
      CRM_Core_Error::statusBounce(E::ts('You do not have the permissions required to access this page.'));
    }

    if (empty($_REQUEST['contributionIds'])) {
      die(E::ts('You need to specifiy a contribution to rebook.'));
    }

    // @todo Is contributionIds meant to contain only one ID?
    $this->contribution_ids = [(int) $_REQUEST['contributionIds']];

    // check if the contributions are all from the same contact
    CRM_Donrec_Form_Task_Rebook::checkSameContact($this->contribution_ids);
  }

  public function buildQuickForm(): void {
    $contributionIds = implode(',', $this->contribution_ids);

    $this->add('text', 'contactId', E::ts('CiviCRM ID'), NULL, $required = TRUE);
    $this->add('hidden', 'contributionIds', $contributionIds);
    $this->addDefaultButtons(E::ts('Rebook'));

    parent::buildQuickForm();
  }

  public function addRules(): void {
    $this->addFormRule(['CRM_Donrec_Form_Task_Rebook', 'rebookRules']);
  }

  public function postProcess(): void {
    $values = $this->exportValues();
    CRM_Donrec_Form_Task_Rebook::rebook($this->contribution_ids, (int) trim($values['contactId']));
    parent::postProcess();

    // finally, redirect to original contact's contribution overview
    $origin_contact_id = CRM_Donrec_Form_Task_Rebook::checkSameContact($this->contribution_ids, NULL);
    if (!empty($origin_contact_id)) {
      $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$origin_contact_id&selectedChild=contribute");
    }
    else {
      $url = CRM_Utils_System::url('civicrm', '');
    }
    CRM_Utils_System::redirect($url);
  }

  /**
   * Checks if the given contributions are of the same contact - one of the requirements for rebooking
   *
   * @param array $contribution_ids
   *   an array of contribution IDs
   *
   * @param string $redirect_url
   *
   * @return int | NULL
   *   the one contact ID or NULL
   */
  public static function checkSameContact($contribution_ids, $redirect_url = NULL) {
    $contact_ids = [];

    foreach ($contribution_ids as $contributionId) {
      $params = [
        'sequential' => 1,
        'id' => $contributionId,
      ];
      $contribution = civicrm_api3('Contribution', 'getsingle', $params);

      // contribution exists
      if (empty($contribution['is_error'])) {
        array_push($contact_ids, (int) $contribution['contact_id']);
      }
      else {
        CRM_Core_Session::setStatus(
          E::ts("At least one of the given contributions doesn't exist!"),
          E::ts('Error'),
          'error'
        );
        CRM_Utils_System::redirect($redirect_url);
        return NULL;
      }
    }

    $contact_ids = array_unique($contact_ids);
    if (count($contact_ids) > 1) {
      CRM_Core_Session::setStatus(
        E::ts('Rebooking of multiple contributions from different contacts is not allowed!'),
        E::ts('Rebooking not allowed!'),
        'error'
      );
      CRM_Utils_System::redirect($redirect_url);
      return NULL;
    }
    else {
      // @phpstan-ignore return.type
      return reset($contact_ids);
    }
  }

  /**
   * Will rebook all given contributions to the given target contact
   *
   * @param array $contribution_ids  an array of contribution IDs
   * @param int $contact_id        the target contact ID
   * @param string $redirect_url
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
   */
  public static function rebook($contribution_ids, $contact_id, $redirect_url = NULL) {
  // phpcs:enable
    $contact_id = (int) $contact_id;
    $excludeList = [
      'id',
      'contribution_id',
      'trxn_id',
      'invoice_id',
      'cancel_date',
      'cancel_reason',
      'address_id',
      'contribution_contact_id',
      'contribution_status_id',
    ];
    $cancelledStatus = CRM_Donrec_CustomData::getOptionValue('contribution_status', 'Cancelled', 'name');
    $completedStatus = CRM_Donrec_CustomData::getOptionValue('contribution_status', 'Completed', 'name');
    $contribution_fieldKeys = CRM_Contribute_DAO_Contribution::fieldKeys();
    try {
      $sepa_ooff_payment_id = CRM_Donrec_CustomData::getOptionValue('payment_instrument', 'OOFF', 'name');
    }
    catch (\Throwable $th) {
      // @ignoreException
      Civi::log()->error(E::ts('DonRec - Error getting SEPA OOFF payment instrument ID: %1', [1 => $th->getMessage()]));
    }

    // Get contribution default return properties.
    $contribution_return = CRM_Contribute_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_CONTRIBUTE);
    // Add non-default fields.
    $contribution_return = array_merge($contribution_fieldKeys, $contribution_return);

    $contribution_count = count($contribution_ids);
    $session = CRM_Core_Session::singleton();
    $rebooked = 0;

    foreach ($contribution_ids as $contributionId) {
      $params = [
        'sequential' => 1,
        'id' => $contributionId,
        'return' => array_keys($contribution_return),
      ];
      /** @var array<string, mixed> $contribution */
      $contribution = civicrm_api3('Contribution', 'getsingle', $params);

      // contribution exists
      if (empty($contribution['is_error'])) {
        // cancel contribution
        $params = [
          'contribution_status_id'  => $cancelledStatus,
          'cancel_reason'           => E::ts('Rebooked to CiviCRM ID %1', [1 => $contact_id]),
          'cancel_date'             => date('YmdHis'),
        // see ticket #1455
          'currency'                => $contribution['currency'],
          'id'                      => $contribution['id'],
        ];
        $cancelledContribution = civicrm_api3('Contribution', 'create', $params);
        if (!empty($cancelledContribution['is_error']) && !empty($cancelledContribution['error_message'])) {
          CRM_Core_Session::setStatus($cancelledContribution['error_message'], E::ts('Error'), 'error');
        }

        // Now compile $attributes, taking the exclusionList into account
        try {
          $paymentInstrument = CRM_Donrec_CustomData::getOptionValue(
            'payment_instrument',
            (string) $contribution['instrument_id'],
            'id'
          );
        }
        catch (\Throwable $th) {
          // @ignoreException
          Civi::log()->error(E::ts('DonRec - Error getting payment instrument', [1 => $th->getMessage()]));
          $paymentInstrument = NULL;
        }

        $attributes = [
          'contribution_contact_id' => $contact_id,
          'contribution_status_id'  => $completedStatus,
        // this seems to be an API bug
          'payment_instrument_id'   => $paymentInstrument,
        ];
        foreach ($contribution as $key => $value) {

          // to be sure that this keys really exists
          if (!in_array($key, $excludeList) && in_array($key, $contribution_fieldKeys)) {
            $attributes[$key] = $value;
          }

          // get custom fields
          if (strstr($key, 'custom')) {
            // load custom field spec for exception handling
            $custom_field_id = substr($key, 7);
            $custom_field = civicrm_api3('CustomField', 'getsingle', ['id' => $custom_field_id]);

            // Exception 1: dates are not properly formatted
            if ($custom_field['data_type'] == 'Date') {
              if (is_string($value) && '' !== $value) {
                $value = date('YmdHis', strtotime($value) ?: 0);
              }
            }
            $attributes[$key] = $value;
          }
        }

        // create new contribution
        $newContribution = civicrm_api3('Contribution', 'create', $attributes);
        if (!empty($newContribution['is_error']) && !empty($newContribution['error_message'])) {
          CRM_Core_Session::setStatus($newContribution['error_message'], E::ts('Error'), 'error');
        }

        // Exception handling for SEPA OOFF payments (org.project60.sepa extension)
        if (!empty($sepa_ooff_payment_id) && $attributes['payment_instrument_id'] == $sepa_ooff_payment_id) {
          CRM_Donrec_Form_Task_Rebook::fixOOFFMandate($contribution, $newContribution['id']);
        }

        // create rebook note
        $params = [
          'sequential' => 1,
          'note' => E::ts('Rebooked from CiviCRM ID %1', [1 => $contribution['contact_id']]),
          'entity_table' => 'civicrm_contribution',
          'entity_id' => $newContribution['id'],
        ];
        $result = civicrm_api3('Note', 'create', $params);

        // move all notes from the old contribution
        $notes = civicrm_api3(
          'Note',
          'get',
          ['entity_id' => $contributionId, 'entity_table' => 'civicrm_contribution']
        );
        if (!empty($notes['is_error'])) {
          Civi::log()->debug('de.systopia.donrec: Error while reading notes: ' . $notes['error_message']);
        }
        else {
          foreach ($notes['values'] as $note) {
            $dao = new CRM_Core_DAO_Note();
            $dao->id = $note['id'];
            $dao->entity_id = $newContribution['id'];
            $dao->save();
          }
        }

        $rebooked += 1;
      }
    }

    if ($rebooked == $contribution_count) {
      CRM_Core_Session::setStatus(
        E::ts('%1 contribution(s) successfully rebooked!', [1 => $contribution_count]),
        E::ts('Successfully rebooked!'),
        'success');
    }
    else {
      Civi::log()->debug(
        "de.systopia.donrec: Only $rebooked of $contribution_count contributions rebooked.",
        ['domain' => 'de.systopia.donrec']
      );
      CRM_Core_Session::setStatus(ts('Please check your data and try again', [1 => $contribution_count]),
        E::ts('Nothing rebooked!'),
        'warning');
      CRM_Utils_System::redirect($redirect_url);
    }
  }

  /**
   * Rule set for the rebooking forms
   * @param array $values
   * @return array|bool
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
   */
  public static function rebookRules($values) {
  // phpcs:enable Generic.Metrics.CyclomaticComplexity.TooHigh
    $errors = [];
    $contactId = trim($values['contactId']);
    $contributionIds = $values['contributionIds'];

    // check if is int
    if (!preg_match('/^\d+$/', $contactId)) {
      $errors['contactId'] = E::ts('Please enter a CiviCRM ID!');
      return $errors;
    }

    $contactId = (int) $contactId;

    // validation for contact
    $contact = new CRM_Contact_BAO_Contact();
    $contact->id = $contactId;

    if (!$contact->find(TRUE)) {
      $errors['contactId'] = E::ts('A contact with CiviCRM ID %1 doesn\'t exist!', [1 => $contactId]);
      return $errors;
    }

    // Der Kontakt, auf den umgebucht wird, darf kein Haushalt sein.
    $contactType = $contact->getContactType($contactId);
    if (!empty($contactType) && $contactType == 'Household') {
      $errors['contactId'] = E::ts('The target contact can not be a household!');
      return $errors;
    }

    // Der Kontakt, auf den umgebucht wird, darf nicht im Papierkorb sein.
    $contactIsDeleted = $contact->is_deleted;
    if ($contactIsDeleted == 1) {
      $errors['contactId'] = E::ts('The target contact can not be in trash!');
      return $errors;
    }

    // Check contributions
    $completed = CRM_Donrec_CustomData::getOptionValue('contribution_status', 'Completed', 'name');
    $arr = explode(',', $contributionIds);
    foreach ($arr as $contributionId) {
      $contributionId = (int) $contributionId;
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->id = $contributionId;
      if ($contribution->find(TRUE)) {
        // only 'completed' contributions can be rebooked
        if ($contribution->contribution_status_id != $completed) {
          $errors['contactId'] = E::ts('The contribution with ID %1 is not completed!', [1 => $contributionId]);
          return $errors;
        }

        // receipted contributions can NOT be rebooked
        if (CRM_Donrec_Logic_Receipt::isContributionLocked($contributionId)) {
          $errors['contactId'] = E::ts(
            'The contribution with ID %1 cannot be rebooked, because it has a valid contribution receipt.',
            [1 => $contributionId]
          );
          return $errors;
        }
      }
    }
    return TRUE;
  }

  /**
   * Fixes the problem, that the cloned contribution does not have a mandate.
   *
   * Approach is:
   *  1) move old (valid) mandate to new contribution
   *  2) create new (invalid) mandate and attach to old contribution
   *
   * See org.project60.sepa extension.
   *
   * @param array $old_contribution
   * @param int $new_contribution_id
   */
  public static function fixOOFFMandate($old_contribution, $new_contribution_id) {
    $old_mandate = civicrm_api3(
      'SepaMandate',
      'getsingle',
      ['entity_id' => $old_contribution['id'], 'entity_table' => 'civicrm_contribution']
    );
    if (!empty($old_mandate['is_error'])) {
      CRM_Core_Session::setStatus($old_mandate['error_message'], E::ts('Error'), 'error');
      return;
    }

    // find a new, unused, derived mandate reference to mark the old one
    $new_reference_pattern = $old_mandate['reference'] . 'REB%02d';
    $new_reference = '';
    for ($i = 1; $i <= 100; $i++) {
      $new_reference = sprintf($new_reference_pattern, $i);
      if (strlen($new_reference) > 35) {
        CRM_Core_Session::setStatus(
          E::ts('Cannot find a new mandate reference, exceeds 35 characters.'),
          E::ts('Error'),
          'error'
        );
        return;
      }

      // see if this reference already exists
      $exists = civicrm_api3('SepaMandate', 'getsingle', ['reference' => $new_reference]);
      if (empty($exists['is_error'])) {
        // found -> it exists -> damn -> keep looking...
        if ($i == 100) {
          // that's it, we tried... maybe something else is wrong
          CRM_Core_Session::setStatus(E::ts('Cannot find a new mandate reference'), E::ts('Error'), 'error');
          break;
        }
        else {
          // keep looking!
          continue;
        }
      }
      else {
        // we found a reference
        break;
      }
    }

    // create an invalid clone of the mandate
    $new_mandate_data = [
      'entity_id'             => $old_contribution['id'],
      'entity_table'          => 'civicrm_contribution',
      'status'                => 'INVALID',
      'reference'             => $new_reference,
      'source'                => $old_mandate['source'],
      'date'                  => date('YmdHis', strtotime($old_mandate['date'])),
      'validation_date'       => date('YmdHis', strtotime($old_mandate['validation_date'])),
      'creation_date'         => date('YmdHis', strtotime($old_mandate['creation_date'])),
      'first_contribution_id'
      => empty($old_mandate['first_contribution_id']) ? '' : $old_mandate['first_contribution_id'],
      'type'                  => $old_mandate['type'],
      'contact_id'            => $old_mandate['contact_id'],
      'iban'                  => $old_mandate['iban'],
      'bic'                   => $old_mandate['bic'],
    ];
    $create_clone = civicrm_api3('SepaMandate', 'create', $new_mandate_data);
    if (!empty($create_clone['is_error'])) {
      CRM_Core_Session::setStatus($create_clone['error_message'], E::ts('Error'), 'error');
      return;
    }

    // set old (original) mandate to new contribution
    $result = civicrm_api3('SepaMandate', 'create', ['id' => $old_mandate['id'], 'entity_id' => $new_contribution_id]);
    if (!empty($result['is_error'])) {
      CRM_Core_Session::setStatus($result['error_message'], E::ts('Error'), 'error');
      return;
    }

    // modify new mandate's (invalid clone's) reference, in case it got overridden
    $result = civicrm_api3('SepaMandate', 'create', ['id' => $create_clone['id'], 'reference' => $new_reference]);
    if (!empty($result['is_error'])) {
      CRM_Core_Session::setStatus($result['error_message'], E::ts('Error'), 'error');
      return;
    }
  }

}
