<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2015 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Donrec_Form_Task_RebookTask extends CRM_Contribute_Form_Task {
  

  function preProcess() {
    parent::preProcess();
    CRM_Utils_System::setTitle(ts('Rebook'));

    $session = CRM_Core_Session::singleton();
    $userContext = $session->readUserContext();  

    $admin = CRM_Core_Permission::check('edit contributions');
    if (!$admin) {
      CRM_Core_Error::fatal(ts('You do not have the permissions required to access this page.'));
      CRM_Utils_System::redirect($userContext);
    }

    // check if the contributions are all from the same contact
    CRM_Donrec_Form_Task_Rebook::checkSameContact($this->_contributionIds, $userContext);
  }


  function buildQuickForm() {
    $contributionIds = implode(',', $this->_contributionIds);
    $this->setContactIDs();

    $this->add('text', 'contactId', ts('CiviCRM ID'), null, $required = true);
    $this->add('hidden', 'contributionIds', $contributionIds);
    // call the (overwritten) Form's method, so the continue button is on the right...
    CRM_Core_Form::addDefaultButtons(ts('Rebook'));

    parent::buildQuickForm();
  }


  function addRules() {
    $this->addFormRule(array('CRM_Donrec_Form_Task_Rebook', 'rebookRules'));
  }


  function postProcess() {
    $session = CRM_Core_Session::singleton();
    $userContext = $session->readUserContext();  

    $values = $this->exportValues();
    CRM_Donrec_Form_Task_Rebook::rebook($this->_contributionIds, trim($values['contactId']), $userContext);
    parent::postProcess();

    // finally, redirect to original contact's contribution overview
    $origin_contact_id = CRM_Donrec_Form_Task_Rebook::checkSameContact($this->_contributionIds, NULL);
    if (!empty($origin_contact_id)) {
      $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$origin_contact_id&selectedChild=contribute");
    } else {
      $url = CRM_Utils_System::url('civicrm', "");
    }
    CRM_Utils_System::redirect($url);    
  }

}
