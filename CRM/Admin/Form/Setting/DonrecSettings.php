<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

require_once 'CRM/Admin/Form/Setting.php';
require_once 'CRM/Core/BAO/CustomField.php';

// Settings form
class CRM_Admin_Form_Setting_DonrecSettings extends CRM_Admin_Form_Setting
{
  function buildQuickForm( ) {
    CRM_Utils_System::setTitle(ts('Donation Receipts - Settings'));

    //TODO: why not use the definitions in donrec.setting.php?
    // add all required elements
    $this->addElement('text', 'draft_text', ts('Draft text'));
    $this->addElement('text', 'copy_text', ts('Copy text'));
    $this->addElement('text', 'packet_size', ts('Packet size'));
    $this->addElement('checkbox','store_pdf');           // actually inserted via template
    $this->addElement('checkbox','financial_types_all'); // "
    $this->addElement('text', 'pdfinfo_path', ts('External Tool: path to <code>pdfinfo</code>'));

    // add location-type-selections
    $query = "SELECT `id`, `name` FROM `civicrm_location_type`";
    $result = CRM_Core_DAO::executeQuery($query);
    $options = array(0 => ts('primary address'));
    while ($result->fetch()) {$options[$result->id] = ts($result->name);}
    $this->addElement('select', 'legal_address', ts('Legal Address-Type:'), $options);
    $this->addElement('select', 'postal_address', ts('Postal Address-Type:'), $options);
    $this->addElement('select', 'legal_address_fallback', ts('Fallback:'), $options);
    $this->addElement('select', 'postal_address_fallback', ts('Fallback:'), $options);

    // add a checkbox for every contribution type
    $ct = CRM_Donrec_Logic_Settings::getContributionTypes();
    for ($i=1; $i <= count($ct); $i++) {
      $this->addElement('checkbox', "financial_types$i");
    }

    $this->addButtons(array(
      array('type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE),
      array('type' => 'cancel', 'name' => ts('Cancel')),
    ));

    // add a custom form validation rule that allows only positive integers (i > 0)
    $this->registerRule('onlypositive', 'callback', 'onlyPositiveIntegers', 'CRM_Admin_Form_Setting_DonrecSettings');
    $this->registerRule('onlyLettersUmlauts', 'callback', 'onlyLettersWithUmlauts', 'CRM_Admin_Form_Setting_DonrecSettings');
  }

  function addRules() {
    $this->addRule('draft_text', ts('Draft text can only contain text'), 'onlyLettersUmlauts');
    $this->addRule('copy_text', ts('Copy text can only contain text'), 'onlyLettersUmlauts');
    $this->addRule('packet_size', ts('Packet size can only contain positive integers'), 'onlypositive');
    //TODO add rule for unix paths
  }

  function preProcess() {
    $this->assign('financialTypes', CRM_Donrec_Logic_Settings::getContributionTypes());
    $this->assign('store_pdf', CRM_Donrec_Logic_Settings::saveOriginalPDF());
    $this->setDefaults(array(
        'draft_text' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'draft_text'),
        'copy_text' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'copy_text'),
        'packet_size' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'packet_size'),
        'pdfinfo_path' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'pdfinfo_path'),
        'legal_address' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'legal_address'),
        'postal_address' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'postal_address'),
        'legal_address_fallback' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'legal_address_fallback'),
        'postal_address_fallback' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'postal_address_fallback')
      ));
  }

  function postProcess() {
    // process all form values and save valid settings
    $values = $this->exportValues();

    // save text fields
    if ($values['draft_text']){
      CRM_Core_BAO_Setting::setItem($values['draft_text'],'Donation Receipt Settings', 'draft_text');
    }
    if ($values['copy_text']){
      CRM_Core_BAO_Setting::setItem($values['copy_text'],'Donation Receipt Settings', 'copy_text');
    }
    CRM_Core_BAO_Setting::setItem($values['packet_size'],'Donation Receipt Settings', 'packet_size');
    if ($values['pdfinfo_path']){
      CRM_Core_BAO_Setting::setItem($values['pdfinfo_path'],'Donation Receipt Settings', 'pdfinfo_path');
    }
    if ($values['legal_address']){
      CRM_Core_BAO_Setting::setItem($values['legal_address'],'Donation Receipt Settings', 'legal_address');
    }
    if ($values['postal_address']){
      CRM_Core_BAO_Setting::setItem($values['postal_address'],'Donation Receipt Settings', 'postal_address');
    }
    if ($values['legal_address_fallback']){
      CRM_Core_BAO_Setting::setItem($values['legal_address_fallback'],'Donation Receipt Settings', 'legal_address_fallback');
    }
    if ($values['postal_address_fallback']){
      CRM_Core_BAO_Setting::setItem($values['postal_address_fallback'],'Donation Receipt Settings', 'postal_address_fallback');
    }

    // save checkboxes
    CRM_Core_BAO_Setting::setItem(!empty($values['store_pdf']),'Donation Receipt Settings', 'store_original_pdf');
    $get_all = !empty($values['financial_types_all']);
    if ($get_all) {
      CRM_Core_BAO_Setting::setItem('all','Donation Receipt Settings', 'contribution_types');
    }else{
      // iterate over all values and save the values of all 'financial_types'-checkboxes
      // in a comma-seperated string
      $id_bucket = array();
      foreach ($values as $key => $value) {
        if (strpos($key, 'financial_types') === 0) {
          $id_bucket[] = $value;
        }
      }

      if (count($id_bucket) > 0) {
        $result = implode(',', $id_bucket);
        CRM_Core_BAO_Setting::setItem($result,'Donation Receipt Settings', 'contribution_types');
      }else{
        // if all checkboxes have been unchecked fall back to selecting all valid contribution types
        CRM_Core_BAO_Setting::setItem('all','Donation Receipt Settings', 'contribution_types');
      }
    }

    $session = CRM_Core_Session::singleton();
    $session->setStatus(ts("Settings successfully saved"), ts('Settings'), 'success');
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/donrec'));
  }

  // custom validation rule that allows only positive integers
  static function onlyPositiveIntegers($value) {
    return !($value <= 0);
  }

  static function onlyLettersWithUmlauts($value) {
    return preg_match("/^[A-Za-zäöüÄÖÜ]+$/",$value);
  }
}
