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
class CRM_Donrec_Form_Task_RebookTask extends CRM_Contribute_Form_Task {

  public function preProcess(): void {
    parent::preProcess();
    CRM_Utils_System::setTitle(E::ts('Rebook'));

    $session = CRM_Core_Session::singleton();
    $userContext = $session->readUserContext();

    $admin = CRM_Core_Permission::check('edit contributions');
    if (!$admin) {
      CRM_Core_Error::statusBounce(E::ts('You do not have the permissions required to access this page.'));
    }

    // check if the contributions are all from the same contact
    CRM_Donrec_Form_Task_Rebook::checkSameContact($this->_contributionIds, $userContext);
  }

  public function buildQuickForm(): void {
    $contributionIds = implode(',', $this->_contributionIds);
    $this->setContactIDs();

    $this->add('text', 'contactId', E::ts('CiviCRM ID'), NULL, $required = TRUE);
    $this->add('hidden', 'contributionIds', $contributionIds);
    // call the (overwritten) Form's method, so the continue button is on the right...
    CRM_Core_Form::addDefaultButtons(E::ts('Rebook'));

    parent::buildQuickForm();
  }

  public function addRules() {
    $this->addFormRule(['CRM_Donrec_Form_Task_Rebook', 'rebookRules']);
  }

  public function postProcess() {
    $session = CRM_Core_Session::singleton();
    $userContext = $session->readUserContext();

    $values = $this->exportValues();
    CRM_Donrec_Form_Task_Rebook::rebook($this->_contributionIds, (int) trim($values['contactId']), $userContext);
    parent::postProcess();

    // finally, redirect to original contact's contribution overview
    $origin_contact_id = CRM_Donrec_Form_Task_Rebook::checkSameContact($this->_contributionIds, NULL);
    if (!empty($origin_contact_id)) {
      $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$origin_contact_id&selectedChild=contribute");
    }
    else {
      $url = CRM_Utils_System::url('civicrm', '');
    }
    CRM_Utils_System::redirect($url);
  }

}
