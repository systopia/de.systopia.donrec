<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
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
class CRM_Donrec_Form_Task_Create extends CRM_Core_Form {

  private $availableCurrencies;

  function buildQuickForm() {
    CRM_Utils_System::setTitle(E::ts('Issue Donation Receipts'));

    $this->addElement('hidden', 'cid');
    $this->addElement('hidden', 'rsid');
    $this->addDatePickerRange(
      'donrec_contribution_horizon',
      E::ts('Time period'),
      FALSE,
      TRUE,
      E::ts('From:'),
      E::ts('To:'),
      [],
      '_to',
      '_from'
    );

    // add profile selector
    $this->addElement('select',
                      'profile',
                      E::ts('Profile'),
                      CRM_Donrec_Logic_Profile::getAllActiveNames('is_default', 'DESC'),
                      array('class' => 'crm-select2'));

    // add currency selector
    $this->availableCurrencies = array_keys(CRM_Core_OptionGroup::values('currencies_enabled'));
    $this->addElement('select', 'donrec_contribution_currency', E::ts('Currency'), $this->availableCurrencies);

    $this->addDefaultButtons(
      E::ts('Continue'),
      'next',
      'cancel'
    );
  }

  function setDefaultValues() {
    // do a cleanup here (ticket #1616)
    CRM_Donrec_Logic_Snapshot::cleanup();

    $contactId = empty($_REQUEST['cid']) ? NULL : $_REQUEST['cid'];
    $this->getElement('cid')->setValue($contactId);
    $this->assign('cid', $contactId);
    $uid = CRM_Donrec_Logic_Settings::getLoggedInContactID();

    //TODO: what if we have more than 1 remaining snapshot (what should not happen at all)?
    $remaining_snapshots = CRM_Donrec_Logic_Snapshot::getUserSnapshots($uid);
    if (!empty($remaining_snapshots)) {
      $remaining_snapshot = array_pop($remaining_snapshots);
      $this->getElement('rsid')->setValue($remaining_snapshot);
      $this->assign('statistic', CRM_Donrec_Logic_Snapshot::getStatistic($remaining_snapshot));
      $this->assign('remaining_snapshot', TRUE);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function validate() {
    // Do not require "from" and "to" fields for time period with relative date
    // selected. Custom time period has value "0".
    $selectedPeriod = $this->getElement('donrec_contribution_horizon_relative')->getValue();
    if (reset($selectedPeriod) !== "0") {
      unset($this->_required[array_search('donrec_contribution_horizon_from', $this->_required)]);
      unset($this->_required[array_search('donrec_contribution_horizon_to', $this->_required)]);
    }
    return parent::validate();
  }

  function postProcess() {
    // CAUTION: changes to this function should also be done in CRM_Donrec_Form_Task_DonrecTask:postProcess()

    // process remaining snapshots
    $rsid = empty($_REQUEST['rsid']) ? NULL : $_REQUEST['rsid'];
    if (!empty($rsid)) {

      //work on with a remaining snapshot...
      $use_remaining_snapshot = $_REQUEST['use_remaining_snapshot'] ?? NULL;
      if (!empty($use_remaining_snapshot)) {
        CRM_Core_Session::singleton()->pushUserContext(
          CRM_Utils_System::url('civicrm/donrec/task', 'sid=' . $rsid)
        );
        return;

      // or delete all remaining snapshots of this user
      } else {
        $uid = CRM_Donrec_Logic_Settings::getLoggedInContactID();
        CRM_Donrec_Logic_Snapshot::deleteUserSnapshots($uid);
      }
    }


    // process form values and try to build a snapshot with all contributions
    // that match the specified criteria (i.e. contributions which have been
    // created between two specific dates)
    $values = $this->exportValues();
    $contactId = empty($_REQUEST['cid']) ? NULL : $_REQUEST['cid'];
    $values['contact_id'] = $contactId;

    // get the currency ISO code
    $currencyId = $values['donrec_contribution_currency'];
    $values['donrec_contribution_currency'] = $this->availableCurrencies[ $currencyId ];

    //set url_back as session-variable
    $session = CRM_Core_Session::singleton();
    $session->set('url_back', CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$contactId&selectedChild=donation_receipts"));

    // generate the snapshot
    $result = CRM_Donrec_Logic_Selector::createSnapshot($values);
    $sid = empty($result['snapshot'])?NULL:$result['snapshot']->getId();

    if (!empty($result['intersection_error'])) {
      CRM_Core_Session::singleton()->pushUserContext(
        CRM_Utils_System::url('civicrm/donrec/task', 'conflict=1' . '&sid=' . $sid . '&ccount=1'));
    }elseif (empty($result['snapshot'])) {
      CRM_Core_Session::setStatus(E::ts('This contact has no selectable contributions in the selected time period.'), E::ts('Warning'), 'warning');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/donrec/create', "reset=1&cid=$contactId"));
    }else{
      CRM_Core_Session::singleton()->pushUserContext(
        CRM_Utils_System::url('civicrm/donrec/task', 'sid=' . $sid . '&origin=' . $contactId . '&ccount=1')
      );
    }
  }
}
