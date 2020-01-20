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
class CRM_Admin_Form_DonrecProfile extends CRM_Core_Form {

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

    // Set redirect destination.
    $this->controller->_destination = CRM_Utils_System::url('civicrm/admin/setting/donrec/profiles', 'reset=1');

    // "Create" is the default operation.
    if (!$this->_op = CRM_Utils_Request::retrieve('op', 'String', $this)) {
      $this->_op = 'create';
    }
    $this->assign('op', $this->_op);

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
          array(
            'type' => 'cancel',
            'name' => E::ts('Cancel')
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
      case 'default':
        $this->profile = CRM_Donrec_Logic_Profile::getProfile($profile_id);
        CRM_Utils_System::setTitle(
          E::ts('Set Donation Receipts profile <em>%1</em> as default', array(
            1 => $this->profile->getName(),
          ))
        );
        $this->addButtons(array(
          array(
            'type' => 'submit',
            'name' => E::ts('Set default'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => E::ts('Cancel')
          ),
        ));
        // Pass profile name to template.
        $this->assign('profile_name', $this->profile->getName());
        return;
      case 'activate':
        $this->profile = CRM_Donrec_Logic_Profile::getProfile($profile_id);
        CRM_Utils_System::setTitle(
          E::ts('Activate Donation Receipts profile <em>%1</em>', array(
            1 => $this->profile->getName(),
          ))
        );
        $this->addButtons(array(
          array(
            'type' => 'submit',
            'name' => E::ts('Activate'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => E::ts('Cancel')
          ),
        ));
        // Pass profile name to template.
        $this->assign('profile_name', $this->profile->getName());
        return;
        break;
      case 'deactivate':
        $this->profile = CRM_Donrec_Logic_Profile::getProfile($profile_id);
        CRM_Utils_System::setTitle(
          E::ts('Deactivate Donation Receipts profile <em>%1</em>', array(
            1 => $this->profile->getName(),
          ))
        );
        $this->addButtons(array(
          array(
            'type' => 'submit',
            'name' => E::ts('Deactivate'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => E::ts('Cancel')
          ),
        ));
        // Pass profile name to template.
        $this->assign('profile_name', $this->profile->getName());
        return;
        break;
    }

    $this->assign('is_locked', $this->profile->isLocked());

    // Add general profile elements.
    $this->add(
      'text',
      'name',
      E::ts('Profile name'),
      array(),
      TRUE
    );

    $this->add(
      'select',
      'language',
      E::ts('Language'),
      CRM_Donrec_Lang::getLanguageList()
    );

    /**
     * Contribution settings.
     */
    $this->add(
      'select',
      'financial_types',
      E::ts('Contribution Types'),
      CRM_Contribute_PseudoConstant::financialType(),
      FALSE,
      array('multiple' => "multiple", 'class' => 'crm-select2')
    );

    $this->add(
      'select',
      'contribution_unlock_mode',
      E::ts('Unlock receipted contributions'),
      array(
        'unlock_all' => E::ts('All fields'),
        'unlock_none' => E::ts('No fields'),
        'unlock_selected' => E::ts('Selected fields'),
      )
    );

    $donrec_contribution_unlock_fields = CRM_Donrec_Logic_Settings::getContributionUnlockableFields();
    foreach ($donrec_contribution_unlock_fields as $field_name => $field_label) {
      $this->add(
        'checkbox',
        'contribution_unlock_field_' . $field_name,
        $field_label
      );
    }
    $this->assign('contribution_unlock_fields', $donrec_contribution_unlock_fields);

    /**
     * Receipt settings.
     */
    $this->add(
      'text',
      'id_pattern',
      E::ts('Receipt ID')
    );
    $this->add(
      'wysiwyg',
      'template',
      E::ts('Template'),
      array(
        'rows' => 6,
        'cols' => 80,
      )
    );
    $this->add(
      'checkbox',
      'store_original_pdf',
      E::ts('Store original *.pdf files')
    );

    /**
     * Watermark settings.
     */
    $this->add(
      'text',
      'draft_text',
      E::ts('Draft text')
    );
    $this->add(
      'text',
      'copy_text',
      E::ts('Copy text')
    );
    $this->add(
      'select',
      'watermark_preset',
      E::ts('Watermark preset'),
      CRM_Donrec_Logic_Settings::getWatermarkPresets(),
      FALSE,
      array('class' => 'crm-select2')
    );

    /**
     * Address type settings.
     */
    // add profile location-type-selections
    $query = "SELECT `id`, `name` FROM `civicrm_location_type`";
    $result = CRM_Core_DAO::executeQuery($query);
    $options = array(0 => E::ts('primary address'));
    while ($result->fetch()) {
      $options[$result->id] = E::ts($result->name);
    }
    $this->add(
      'select',
      'legal_address',
      E::ts('Legal Address-Type:'),
      $options
    );
    $this->add(
      'select',
      'postal_address',
      E::ts('Postal Address-Type:'),
      $options
    );
    $this->add(
      'select',
      'legal_address_fallback',
      E::ts('Fallback:'),
      $options
    );
    $this->add(
      'select',
      'postal_address_fallback',
      E::ts('Fallback:'),
      $options
    );

    /**
     * E-mail settings.
     */
    $this->add(
      'select',
      'email_template',
      E::ts('E-mail template'),
      CRM_Donrec_Logic_Settings::getAllTemplates()
    );
    $this->add(
      'select',
      'from_email',
      ts('From Email', array('domain' => 'de.systopia.donrec')),
      $this->getSenderEmails(),
      FALSE,
      array('class' => 'crm-select2 huge')
    );
    $this->add(
      'text',
      'bcc_email',
      E::ts('BCc E-mail address'),
      $this->profile->getDataAttribute('bcc_email')
    );
    $this->addRule(
      'bcc_email',
      E::ts('Has to be a valid email address'),
      'email'
    );
    $this->add(
      'text',
      'return_path_email',
      E::ts('Return path e-mail address'),
      $this->profile->getDataAttribute('return_path_email')
    );
    $this->addRule(
      'return_path_email',
      E::ts('Has to be a valid email address'),
      'email'
    );

    // add generic elements
    // TODO: Move to extension settings form (out of profile).
    $this->add(
      'text',
      'pdfinfo_path',
      E::ts('External Tool: path to <code>pdfinfo</code>'),
      CRM_Donrec_Logic_Settings::get('donrec_pdfinfo_path')
    );
    $this->add(
      'text',
      'packet_size',
      E::ts('Packet size'),
      CRM_Donrec_Logic_Settings::get('donrec_packet_size')
    );

    $this->addButtons(array(
      array('type' => 'next', 'name' => E::ts('Save'), 'isDefault' => TRUE),
      array('type' => 'cancel', 'name' => E::ts('Cancel')),
    ));

    // add a custom form validation rule that allows only positive integers (i > 0)
    $this->registerRule('onlypositive', 'callback', 'onlyPositiveIntegers', 'CRM_Admin_Form_DonrecProfile');
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    // TODO: Move to extension settings (out of profile).
//    $this->addRule('packet_size', ts('Packet size can only contain positive integers', array('domain' => 'de.systopia.donrec')), 'onlypositive');
  }

  /**
   * Add local and global form rules.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    // Set individual properties.
    $defaults['name'] = $this->profile->getName();
    $defaults['template'] = $this->profile->getTemplate()->getTemplateHTML();

    // Set data properties.
    foreach ($this->profile->getData() as $key => $value) {
      if ($key == 'contribution_unlock_fields') {
        foreach ($value as $unlock_field_name => $unlock_field_value) {
          $defaults['contribution_unlock_field_' . $unlock_field_name] = $unlock_field_value;
        }
      }
      else {
        $defaults[$key] = $value;
      }
    }

    // Use a sane default depending on the PDF engine.
    if (!isset($defaults['watermark_preset'])) {
      $defaults['watermark_preset'] = CRM_Donrec_Logic_WatermarkPreset::getDefaultWatermarkPresetName();
    }

    // Use a sane default for language.
    if (empty($defaults['language'])) {
      if (method_exists('CRM_Core_I18n', 'getLocale')) {
        $defaults['language'] = CRM_Core_I18n::getLocale();
      } else {
        $defaults['language'] = 'en_US';
      }
    }

    return $defaults;
  }

  public function cancelAction() {
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/donrec/profiles', array('reset' => 1)));
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $values = $this->exportValues();
    $session = CRM_Core_Session::singleton();

    if (in_array($this->_op, array('create', 'edit'))) {
      // Set all profile properties.
      foreach (array(
        'name',
        'template',
        'template_pdf_format_id',
        'variables'
               ) as $property) {
        if (isset($values[$property])) {
          $this->profile->set($property, $values[$property]);
        }
      }

      // Set data attributes.
      foreach ($this->profile->getData() as $element_name => $value) {
        if (isset($values[$element_name])) {
          $this->profile->setDataAttribute($element_name, $values[$element_name]);
        }
      }

      $this->profile->save();

      $session->setStatus(E::ts('Settings successfully saved'), E::ts('Settings'), 'success');
    }
    elseif ($this->_op == 'delete') {
      $this->profile->deleteProfile();
    }
    elseif ($this->_op == 'default') {
      // Set default and remove is_default from current default profile.
      $default_profile = CRM_Donrec_Logic_Profile::getDefaultProfile();
      $default_profile->setDefault(FALSE);
      $this->profile->setDefault();
    }
    elseif ($this->_op == 'activate') {
      $this->profile->activate();
    }
    elseif ($this->_op == 'deactivate') {
      $this->profile->activate(FALSE);
    }

    parent::postProcess();

    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/donrec/profiles', array('reset' => 1)));

    return;







    // save generic settings
    // TODO: Move to extension settings form (out of profile form)
    CRM_Donrec_Logic_Settings::set('donrec_packet_size', $values['packet_size']);
    if ($values['pdfinfo_path']) {
      CRM_Donrec_Logic_Settings::set('donrec_pdfinfo_path', $values['pdfinfo_path']);
    }

    CRM_Donrec_Logic_Settings::set('donrec_email_template', $values['donrec_email_template']);
    CRM_Donrec_Logic_Settings::set('donrec_return_path_email', $values['donrec_return_path_email']);
    CRM_Donrec_Logic_Settings::set('donrec_language', $values['donrec_language']);
    CRM_Donrec_Logic_Settings::set('donrec_bcc_email', $values['donrec_bcc_email']);
    CRM_Donrec_Logic_Settings::set('donrec_watermark_preset', $values['donrec_watermark_preset']);
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
