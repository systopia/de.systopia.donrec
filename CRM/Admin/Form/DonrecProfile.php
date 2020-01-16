<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2020 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Admin_Form_DonrecProfile extends CRM_Admin_Form {

  /**
   * @var \CRM_Donrec_Logic_Profile $profile
   *  The profile object the form is acting on.
   */
  protected $profile;

  /**
   * @var string
   *   The operation to perform within the form.
   */
  protected $_op;

  /**
   * @var int
   *   The profile ID.
   */
  protected $id;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    // "Create" is the default operation.
    if (!$this->_op = CRM_Utils_Request::retrieve('op', 'String', $this)) {
      $this->_op = 'create';
    }

    $profile_id = CRM_Utils_Request::retrieve(
      'id',
      'Int',
      $this
    );

    // Set profile and title depending on operation.
    switch ($this->_op) {
      case 'delete':
        $this->profile = CRM_Donrec_Logic_Profile::getProfile($profile_id);
        CRM_Utils_System::setTitle(
          E::ts('Delete Donation Receipts profile <em>%1</em>', array(
            1 => $this->profile->getName(),
          ))
        );
        $this->addButtons(array(
          array(
            'type' => 'submit',
            'name' => E::ts('Delete'),
            'isDefault' => TRUE,
          ),
        ));
        return;
      case 'edit':
        $this->profile = CRM_Donrec_Logic_Profile::getProfile($profile_id);
        CRM_Utils_System::setTitle(
          E::ts('Edit Donation Receipts profile <em>%1</em>', array(
            1 => $this->profile->getName(),
          ))
        );
        break;
      case 'copy':
        // This will be a 'create' actually.
        $this->_op = 'create';

        // Copy the profile.
        $this->profile = CRM_Donrec_Logic_Profile::copyProfile($profile_id);
        CRM_Utils_System::setTitle(E::ts('New Donation Receipts profile'));
        break;
      case 'create':
        // Load factory default profile values.
        $this->profile = new CRM_Donrec_Logic_Profile();
        CRM_Utils_System::setTitle(E::ts('New Donation Receipts profile'));
        break;
    }

    // Set redirect destination.
    $this->controller->_destination = CRM_Utils_System::url('civicrm/admin/setting/donrec/profiles', 'reset=1');

    // Add all profile elements.
    $this->addElement(
      'text',
      'name',
      E::ts('Profile name')
    );
    $this->addElement(
      'text',
      'draft_text',
      E::ts('Draft text')
    );
    $this->addElement(
      'text',
      'copy_text',
      E::ts('Copy text')
    );
    $this->addElement(
      'text',
      'id_pattern',
      E::ts('Receipt ID')
    );
    // actually inserted via template
    $this->addElement(
      'checkbox',
    'store_original_pdf'
    );
    $this->addElement(
      'select',
      'financial_types',
      E::ts('Contribution Types'),
      CRM_Contribute_PseudoConstant::financialType(),
      array('multiple' => "multiple", 'class' => 'crm-select2')
    );
    $this->addElement(
      'select',
      'template',
      E::ts('Template'),
      CRM_Donrec_Logic_Settings::getAllTemplates(),
      array('class' => 'crm-select2')
    );
    $this->addElement(
      'select',
      'from_email',
      ts('From Email', array('domain' => 'de.systopia.donrec')),
      $this->getSenderEmails(),
      array('class' => 'crm-select2 huge')
    );
    $this->addElement(
      'select',
      'watermark_preset',
      ts('Watermark preset', array('domain' => 'de.systopia.donrec')),
      CRM_Donrec_Logic_Settings::getWatermarkPresets(),
      array('class' => 'crm-select2')
    );

    // add profile location-type-selections
    $query = "SELECT `id`, `name` FROM `civicrm_location_type`";
    $result = CRM_Core_DAO::executeQuery($query);
    $options = array(0 => E::ts('primary address'));
    while ($result->fetch()) {
      $options[$result->id] = E::ts($result->name);
    }
    $this->addElement(
      'select',
      'legal_address',
      E::ts('Legal Address-Type:'),
      $options
    );
    $this->addElement(
      'select',
      'postal_address',
      E::ts('Postal Address-Type:'),
      $options
    );
    $this->addElement(
      'select',
      'legal_address_fallback',
      E::ts('Fallback:'),
      $options
    );
    $this->addElement(
      'select',
      'postal_address_fallback',
      E::ts('Fallback:'),
      $options
    );

    // add generic elements
    $this->addElement(
      'text',
      'pdfinfo_path',
      E::ts('External Tool: path to <code>pdfinfo</code>'),
      CRM_Donrec_Logic_Settings::get('donrec_pdfinfo_path')
    );

    $this->addElement(
      'text',
      'packet_size',
      E::ts('Packet size'),
      CRM_Donrec_Logic_Settings::get('donrec_packet_size')
    );

    $this->addElement(
      'text',
      'bcc_email',
      E::ts('BCC Email'),
      CRM_Donrec_Logic_Settings::get('donrec_bcc_email')
    );
    $this->addRule(
      'bcc_email',
      E::ts('Has to be a valid email address'),
      'email'
    );

    $this->addElement(
      'text',
      'return_path_email',
      E::ts('Email Return Path'),
      CRM_Donrec_Logic_Settings::get('donrec_return_path_email')
    );
    $this->addRule(
      'return_path_email',
      E::ts('Has to be a valid email address'),
      'email'
    );

    $this->addElement(
      'select',
      'email_template',
      E::ts('Email Template'),
      CRM_Donrec_Logic_Settings::getAllTemplates()
    );

    $this->addElement(
      'select',
      'language',
      E::ts('Language'),
      CRM_Donrec_Lang::getLanguageList()
    );

    $this->addElement(
      'select',
      'contribution_unlock',
      E::ts('Unlock receipted contributions'),
      array(
        'unlock_all' => E::ts('All fields'),
        'unlock_none' => E::ts('No fields'),
        'unlock_selected' => E::ts('Selected fields'),
      )
    );

    $donrec_contribution_unlock_fields = CRM_Donrec_Logic_Settings::getContributionUnlockableFields();
    foreach ($donrec_contribution_unlock_fields as $field_name => $field_label) {
      $this->addElement(
        'checkbox',
        'contribution_unlock_field_' . $field_name,
        $field_label
      );
    }
    $this->assign('contribution_unlock_fields', $donrec_contribution_unlock_fields);

    $this->addButtons(array(
      array('type' => 'next', 'name' => ts('Save', array('domain' => 'de.systopia.donrec')), 'isDefault' => TRUE),
      array('type' => 'cancel', 'name' => ts('Cancel', array('domain' => 'de.systopia.donrec'))),
    ));

    // add a custom form validation rule that allows only positive integers (i > 0)
    $this->registerRule('onlypositive', 'callback', 'onlyPositiveIntegers', 'CRM_Admin_Form_DonrecProfile');
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addRule('packet_size', ts('Packet size can only contain positive integers', array('domain' => 'de.systopia.donrec')), 'onlypositive');
  }

  /**
   * Add local and global form rules.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    $defaults['name'] = $this->profile->getName();
    // TODO: is_active, variables, template, template_pdf_format_id
    $defaults['pdfinfo_path'] = $this->profile->get('pdfinfo_path');
    $defaults['packet_size'] = $this->profile->get('packet_size');
    $defaults['email_template'] = $this->profile->get('email_template');
    $defaults['return_path_email'] = $this->profile->get('return_path_email');
    $defaults['bcc_email'] = $this->profile->get('bcc_email');
    $defaults['watermark_preset'] = $this->profile->get('watermark_preset');
    $defaults['contribution_unlock'] = $this->profile->get('contribution_unlock');
    foreach ($this->profile->get('contribution_unlock_fields') as $unlock_field_name => $unlock_field_value) {
      $defaults['contribution_unlock_field_' . $unlock_field_name] = $unlock_field_value;
    }

    // Use a sane default depending on the PDF engine.
    if (!isset($defaults['watermark_preset'])) {
      $defaults['watermark_preset'] = (!empty(CRM_Core_Config::singleton()->wkhtmltopdfPath) ? 'wkhtmltopdf_traditional' : 'dompdf_traditional');
    }

    $defaults['language'] = $this->profile->get('donrec_language');
    if (empty($defaults['language'])) {
      if (method_exists('CRM_Core_I18n', 'getLocale')) {
        $defaults['language'] = CRM_Core_I18n::getLocale();
      } else {
        $defaults['language'] = 'en_US';
      }
    }

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
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
    CRM_Donrec_Logic_Settings::set('donrec_contribution_unlock', $values['donrec_contribution_unlock']);
    $unlock_fields = array();
    $unlockable_fields = array_keys(CRM_Donrec_Logic_Settings::getContributionUnlockableFields());
    foreach ($unlockable_fields as $field_key) {
      if (array_key_exists('donrec_contribution_unlock_field_' . $field_key, $values)) {
        $unlock_fields[$field_key] = $values['donrec_contribution_unlock_field_' . $field_key];
      }
    }
    $unlock_fields += array_fill_keys($unlockable_fields, 0);
    CRM_Donrec_Logic_Settings::set(
      'donrec_contribution_unlock_fields',
      $unlock_fields
    );

    // make sure, that the checkboxes are in there
    if (!isset($values['store_original_pdf'])) {
      $values['store_original_pdf'] = 0;
    }

    // first, update current values into slected profile
    if (!empty($values['selected_profile'])) {
      $profile = $values['selected_profile'];
      $profile_data = json_decode($values['profile_data'], 1);
      $profile_defaults = CRM_Donrec_Logic_Profile::defaultProfileData();

      foreach (array_keys($profile_defaults['data']) as $field_name) {
        $value = CRM_Utils_Array::value($field_name, $values, NULL);
        if ($value !== NULL) {
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
