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
                      'selected_profile',
                      'Default');
    $this->addElement('hidden',
                      'profile_data',
                      json_encode(CRM_Donrec_Logic_Profile::getAllData()));

    // add all profile elements
    $this->addElement('text', 'draft_text', ts('Draft text', array('domain' => 'de.systopia.donrec')));
    $this->addElement('text', 'copy_text', ts('Copy text', array('domain' => 'de.systopia.donrec')));
    $this->addElement('text', 'id_pattern', ts('Receipt ID', array('domain' => 'de.systopia.donrec')));
    $this->addElement('checkbox','store_original_pdf');           // actually inserted via template
    $this->addElement('select', 'financial_types', ts('Contribution Types', array('domain' => 'de.systopia.donrec')), CRM_Contribute_PseudoConstant::financialType(), array('multiple' => "multiple", 'class' => 'crm-select2'));
    $this->addElement('select', 'template', ts('Template', array('domain' => 'de.systopia.donrec')), CRM_Donrec_Logic_Settings::getAllTemplates(), array('class' => 'crm-select2'));
    $this->addElement(
      'select',
      'donrec_from_email',
      ts('From Email', array('domain' => 'de.systopia.donrec')),
      $this->getSenderEmails(),
      array('class' => 'crm-select2 huge')
    );
    $this->addElement(
      'select',
      'donrec_watermark_preset',
      ts('Watermark preset', array('domain' => 'de.systopia.donrec')),
      CRM_Donrec_Logic_Settings::getWatermarkPresets(),
      array('class' => 'crm-select2')
    );

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
                      CRM_Donrec_Logic_Settings::get('donrec_pdfinfo_path'));

    $this->addElement('text',
                      'packet_size',
                      ts('Packet size', array('domain' => 'de.systopia.donrec')),
                      CRM_Donrec_Logic_Settings::get('donrec_packet_size'));

    $this->addElement('text',
                      'donrec_bcc_email',
                      ts('BCC Email', array('domain' => 'de.systopia.donrec')),
                      CRM_Donrec_Logic_Settings::get('donrec_bcc_email'));
    $this->addRule('donrec_bcc_email', ts('Has to be a valid email address', array('domain' => 'de.systopia.donrec')), 'email');

    $this->addElement('text',
                      'donrec_return_path_email',
                      ts('Email Return Path', array('domain' => 'de.systopia.donrec')),
                      CRM_Donrec_Logic_Settings::get('donrec_return_path_email'));
    $this->addRule('donrec_return_path_email', ts('Has to be a valid email address', array('domain' => 'de.systopia.donrec')), 'email');

    $this->addElement('select',
                      'donrec_email_template',
                      ts('Email Template', array('domain' => 'de.systopia.donrec')),
                      CRM_Donrec_Logic_Settings::getAllTemplates());

    $this->addElement('select',
                      'donrec_language',
                      ts('Language', array('domain' => 'de.systopia.donrec')),
                      CRM_Core_I18n::languages(FALSE));


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


  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    $defaults['selected_profile'] = 'Default';
    $defaults['pdfinfo_path'] = CRM_Donrec_Logic_Settings::get('donrec_pdfinfo_path');
    $defaults['packet_size'] = CRM_Donrec_Logic_Settings::get('donrec_packet_size');
    $defaults['donrec_email_template'] = CRM_Donrec_Logic_Settings::get('donrec_email_template');
    $defaults['donrec_return_path_email'] = CRM_Donrec_Logic_Settings::get('donrec_return_path_email');
    $defaults['donrec_bcc_email'] = CRM_Donrec_Logic_Settings::get('donrec_bcc_email');
    $defaults['donrec_watermark_preset'] = CRM_Donrec_Logic_Settings::get('donrec_watermark_preset');

    // Use a sane default depending on the PDF engine.
    if (!isset($defaults['donrec_watermark_preset'])) {
      $defaults['donrec_watermark_preset'] = (!empty(CRM_Core_Config::singleton()->wkhtmltopdfPath) ? 'wkhtmltopdf_traditional' : 'dompdf_traditional');
    }

    $defaults['donrec_language'] = CRM_Donrec_Logic_Settings::get('donrec_language');
    if (empty($defaults['donrec_language'])) {
      if (method_exists('CRM_Core_I18n', 'getLocale')) {
        $defaults['donrec_language'] = CRM_Core_I18n::getLocale();
      } else {
        $defaults['donrec_language'] = 'en_US';
      }
    }

    return $defaults;
  }


  function postProcess() {
    // process all form values and save valid settings
    $values = $this->exportValues();

    // save generic settings
    CRM_Donrec_Logic_Settings::set('donrec_packet_size', $values['packet_size']);
    CRM_Donrec_Logic_Settings::set('donrec_email_template', $values['donrec_email_template']);
    CRM_Donrec_Logic_Settings::set('donrec_return_path_email', $values['donrec_return_path_email']);
    CRM_Donrec_Logic_Settings::set('donrec_language', $values['donrec_language']);
    CRM_Donrec_Logic_Settings::set('donrec_bcc_email', $values['donrec_bcc_email']);
    CRM_Donrec_Logic_Settings::set('donrec_watermark_preset', $values['donrec_watermark_preset']);
    if ($values['pdfinfo_path']) {
      CRM_Donrec_Logic_Settings::set('donrec_pdfinfo_path', $values['pdfinfo_path']);
    }

    // first, update current values into slected profile
    if (!empty($values['selected_profile'])) {
      $profile = $values['selected_profile'];
      $profile_data = json_decode($values['profile_data'], 1);
      $profile_defaults = CRM_Donrec_Logic_Profile::defaultProfileData();

      foreach (array_keys($profile_defaults) as $field_name) {
        $value = CRM_Utils_Array::value($field_name, $values, NULL);
        if ($value != NULL) {
          $profile_data[$profile][$field_name] = $value;
        }
      }

      // verify some stuff
      foreach ($profile_data as $profile_name => $profile) {
        // test the ID pattern
        try {
          $generator = new CRM_Donrec_Logic_IDGenerator($profile['id_pattern'], false);
        } catch (Exception $e) {
          $session = CRM_Core_Session::singleton();
          $session->setStatus(ts("One of the Receipt ID patterns are invalid! Changes NOT saved!", array('domain' => 'de.systopia.donrec')), ts('Error', array('domain' => 'de.systopia.donrec')), 'error');
          return;
        }
      }

      // then store the profiles
      CRM_Donrec_Logic_Profile::setAllData($profile_data);
    }

    $session = CRM_Core_Session::singleton();
    $session->setStatus(ts("Settings successfully saved", array('domain' => 'de.systopia.donrec')), ts('Settings', array('domain' => 'de.systopia.donrec')), 'success');
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/donrec'));
  }

  // custom validation rule that allows only positive integers
  static function onlyPositiveIntegers($value) {
    return !($value <= 0);
  }


  /**
   * Get a drop-down list of registered sender email addresses
   */
  protected function getSenderEmails() {
    $sender_email_addresses = [];
    $fromEmailAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL);
    foreach ($fromEmailAddress as $email_id => $email_string) {
      $sender_email_addresses[$email_id] = htmlentities($email_string);
    }
    return $sender_email_addresses;
  }
}
