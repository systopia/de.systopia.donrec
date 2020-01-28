<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

require_once 'CRM/Admin/Form/Setting.php';
require_once 'CRM/Core/BAO/CustomField.php';

use CRM_Donrec_ExtensionUtil as E;

// Settings form
class CRM_Admin_Form_Setting_DonrecSettings extends CRM_Admin_Form_Setting
{
  function buildQuickForm( ) {
    CRM_Utils_System::setTitle(E::ts('Donation Receipts - Settings'));

    // add generic elements
    $this->addElement(
      'text',
      'pdfinfo_path',
      E::ts('External Tool: path to <code>pdfinfo</code>'),
      CRM_Donrec_Logic_Settings::get('donrec_pdfinfo_path')
    );

    $this->addElement('text',
      'pdfunite_path',
      E::ts('External Tool: path to <code>pdfunite</code>'),
      CRM_Donrec_Logic_Settings::get('donrec_pdfunite_path'));

    $this->addElement(
      'text',
      'packet_size',
      E::ts('Packet size'),
      CRM_Donrec_Logic_Settings::get('donrec_packet_size')
    );

    $this->addButtons(array(
      array(
        'type' => 'next',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ),
    ));

    // add a custom form validation rule that allows only positive integers (i > 0)
    $this->registerRule(
      'onlypositive',
      'callback',
      'onlyPositiveIntegers',
      'CRM_Admin_Form_Setting_DonrecSettings'
    );
  }

  function addRules() {
    $this->addRule(
      'packet_size',
      E::ts('Packet size can only contain positive integers'),
      'onlypositive'
    );
  }


  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    $defaults['pdfinfo_path'] = CRM_Donrec_Logic_Settings::get('donrec_pdfinfo_path');
    $defaults['pdfunite_path'] = CRM_Donrec_Logic_Settings::get('donrec_pdfunite_path');
    $defaults['packet_size'] = CRM_Donrec_Logic_Settings::get('donrec_packet_size');

    return $defaults;
  }

  public function cancelAction() {
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/donrec/profiles', array('reset' => 1)));
  }


  function postProcess() {
    // process all form values and save valid settings
    $values = $this->exportValues();

    // save generic settings
    CRM_Donrec_Logic_Settings::set('donrec_packet_size', $values['packet_size']);

    if ($values['pdfinfo_path']) {
      CRM_Donrec_Logic_Settings::set('donrec_pdfinfo_path', $values['pdfinfo_path']);
    }

    if ($values['pdfunite_path']) {
      CRM_Donrec_Logic_Settings::set('donrec_pdfunite_path', $values['pdfunite_path']);
    }

    $session = CRM_Core_Session::singleton();
    $session->setStatus(
      E::ts('Settings successfully saved'),
      E::ts('Settings'),
      'success'
    );
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/donrec/profiles'));
  }

  // custom validation rule that allows only positive integers
  static function onlyPositiveIntegers($value) {
    return !($value <= 0);
  }

}
