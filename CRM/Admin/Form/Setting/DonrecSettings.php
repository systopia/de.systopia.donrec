<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2015 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

require_once 'CRM/Admin/Form/Setting.php';
require_once 'CRM/Core/BAO/CustomField.php';

// Settings form
class CRM_Admin_Form_Setting_DonrecSettings extends CRM_Admin_Form_Setting
{
  function buildQuickForm( ) {
    CRM_Utils_System::setTitle(ts('Donation Receipts - Settings', array('domain' => 'de.systopia.donrec')));

    // add profile selector + data
    $this->addElement('select', 
                      'profile', 
                      ts('Profile', array('domain' => 'de.systopia.donrec')), 
                      CRM_Donrec_Logic_Profile::getAllNames(), 
                      array('class' => 'crm-select2'));
    $this->addElement('hidden', 
                      'profile_data', 
                      json_encode(CRM_Donrec_Logic_Profile::getAllData()));

    // add all profile elements
    $this->addElement('text', 'draft_text', ts('Draft text', array('domain' => 'de.systopia.donrec')));
    $this->addElement('text', 'copy_text', ts('Copy text', array('domain' => 'de.systopia.donrec')));
    $this->addElement('text', 'id_pattern', ts('Receipt ID', array('domain' => 'de.systopia.donrec')));
    $this->addElement('checkbox','store_pdf');           // actually inserted via template
    $this->addElement('select', 'financial_types', ts('Contribution Types', array('domain' => 'de.systopia.donrec')), CRM_Contribute_PseudoConstant::financialType(), array('multiple' => "multiple", 'class' => 'crm-select2'));
    $this->addElement('select', 'template', ts('Template', array('domain' => 'de.systopia.donrec')), CRM_Donrec_Logic_Settings::getAllTemplates(), array('class' => 'crm-select2'));

    // add profile location-type-selections
    $query = "SELECT `id`, `name` FROM `civicrm_location_type`";
    $result = CRM_Core_DAO::executeQuery($query);
    $options = array(0 => ts('primary address', array('domain' => 'de.systopia.donrec')));
    while ($result->fetch()) {$options[$result->id] = ts($result->name, array('domain' => 'de.systopia.donrec'));}
    $this->addElement('select', 'legal_address', ts('Legal Address-Type:', array('domain' => 'de.systopia.donrec')), $options);
    $this->addElement('select', 'postal_address', ts('Postal Address-Type:', array('domain' => 'de.systopia.donrec')), $options);
    $this->addElement('select', 'legal_address_fallback', ts('Fallback:', array('domain' => 'de.systopia.donrec')), $options);
    $this->addElement('select', 'postal_address_fallback', ts('Fallback:', array('domain' => 'de.systopia.donrec')), $options);

    // add generic elements
    $this->addElement('text', 
                      'pdfinfo_path', 
                      ts('External Tool: path to <code>pdfinfo</code>', array('domain' => 'de.systopia.donrec')),
                      CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'pdfinfo_path'));

    $this->addElement('text', 
                      'packet_size', 
                      ts('Packet size', array('domain' => 'de.systopia.donrec')),
                      CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'packet_size'));


    $this->addButtons(array(
      array('type' => 'next', 'name' => ts('Save', array('domain' => 'de.systopia.donrec')), 'isDefault' => TRUE),
      array('type' => 'cancel', 'name' => ts('Cancel', array('domain' => 'de.systopia.donrec'))),
    ));

    // add a custom form validation rule that allows only positive integers (i > 0)
    $this->registerRule('onlypositive', 'callback', 'onlyPositiveIntegers', 'CRM_Admin_Form_Setting_DonrecSettings');
  }

  function addRules() {
    $this->addRule('packet_size', ts('Packet size can only contain positive integers', array('domain' => 'de.systopia.donrec')), 'onlypositive');
  }

  function preProcess() {
    $this->setDefaults(array(
      'pdfinfo_path' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'pdfinfo_path'),
      'packet_size'  => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'packet_size')
    ));
  }

  function postProcess() {
    // process all form values and save valid settings
    $values = $this->exportValues();

    // save generic settings
    CRM_Core_BAO_Setting::setItem($values['packet_size'],'Donation Receipt Settings', 'packet_size');
    if ($values['pdfinfo_path']){
      CRM_Core_BAO_Setting::setItem($values['pdfinfo_path'],'Donation Receipt Settings', 'pdfinfo_path');
    }
        
    // first, update current values into slected profile
    if (!empty($values['profile'])) {
      $profile = $values['profile'];
      $profile_data = json_decode($values['profile_data'], 1);
      $profile_defaults = CRM_Donrec_Logic_Profile::defaultProfileData();

      foreach (array_keys($profile_defaults) as $field_name) {
        $value = CRM_Utils_Array::value($field_name, $values, NULL);
        if ($value != NULL) {
          $profile_data[$profile][$field_name] = $value;
        }
      }

      // then store the profiles
      CRM_Donrec_Logic_Profile::syncProfileData($profile_data);        
    }

    $session = CRM_Core_Session::singleton();
    $session->setStatus(ts("Settings successfully saved", array('domain' => 'de.systopia.donrec')), ts('Settings', array('domain' => 'de.systopia.donrec')), 'success');
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/donrec'));
  }

  // custom validation rule that allows only positive integers
  static function onlyPositiveIntegers($value) {
    return !($value <= 0);
  }
}
