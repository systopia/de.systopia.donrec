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
   * @var string
   */
  protected $_ajax_action;

  /**
   * @var int
   *   The profile ID.
   */
  protected $id;

  /**
   * Add form elements for variables to the form.
   */
  protected function addVariablesElements() {
    $variable_count = 0;
    $variable_elements = array();

    //Get all current variable fields when adding via Ajax.
    if (
    ($this->_ajax_action = CRM_Utils_Request::retrieve('ajax_action', 'String', $this))
      && $this->_ajax_action == 'add_variable'
    ) {
      while(TRUE) {
        $variable_count++;
        $current_name = CRM_Utils_Request::retrieve('variables--' . $variable_count . '--name', 'String', $this);
        $current_value = CRM_Utils_Request::retrieve('variables--' . $variable_count . '--value', 'String', $this);
        if (!is_null($current_name)) {
          $this->profile->addVariable($current_name, $current_value);
        }
        else {
          break;
        }
      }
      $this->profile->addVariable();
    }

    if (empty($this->profile->getVariables())) {
      $this->profile->addVariable();
    }

    $variable_count = 0;
    foreach ($this->profile->getVariables() as $variable_name => $variable) {
      $variable_count++;
      $this->add(
        'text',
        'variables--' . $variable_count . '--name',
        E::ts('Variable name')
      );
      $this->add(
        'textarea',
        'variables--' . $variable_count . '--value',
        E::ts('Variable value'),
        array(
          'rows' => 3,
          'cols' => 80,
        )
      );
      $variable_elements[$variable_count] = 'variables--' . $variable_count;
    }
    $this->assign('variable_elements', $variable_elements);
  }

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
        // Ask for new default profile when attempting to delete the current
        // default profile.
        if ($this->profile->isDefault()) {
          $this->assign('is_default', $this->profile->isDefault());
          $this->add(
            'select',
            'new_default_profile',
            E::ts('Set new default profile'),
            array_filter(CRM_Donrec_Logic_Profile::getAllNames(), function($id) use ($profile_id) {
              return $id != $profile_id;
            }, ARRAY_FILTER_USE_KEY),
            TRUE
          );
        }
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
        // Reset status values.
        $this->profile->set('is_locked', 0);
        $this->profile->set('is_active', 1);
        $this->profile->set('is_default', 0);
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
        // Pass profile properties to template.
        $this->assign('profile_name', $this->profile->getName());
        $this->assign('is_default', $this->profile->isDefault());
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

    $this->add('checkbox',
      'enable_encryption',
      E::ts('Enable "encryption"')
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
      'textarea',
      'template',
      E::ts('Template'),
      array(
        'rows' => 25,
        'cols' => 80,
      )
    );
    $this->add(
      'select',
      'template_pdf_format_id',
      E::ts('PDF format'),
      CRM_Core_BAO_PdfFormat::getList(TRUE),
      TRUE,
      array('class' => 'crm-select2')
    );

    $this->add(
      'checkbox',
      'store_original_pdf',
      E::ts('Store original *.pdf files')
    );

    $this->addVariablesElements();
    $this->add(
      'button',
      'variables_more',
      E::ts('Add variable')
    );
    CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, 'js/donrec-profile.js', 1, 'html-header');

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
      CRM_Donrec_Logic_Settings::getAllTemplates() // TODO: is that correct?
    );
    $this->add(
      'select',
      'from_email',
      E::ts('From Email'),
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
    $this->add(
      'checkbox',
      'special_mail_handling',
      E::ts('Custom Mail Handling:')
    );
    $this->add(
      'text',
      'special_mail_header',
      E::ts('Custom Mail Header:')
    );
    $this->add(
      'text',
      'special_mail_activity_id',
      E::ts('Activity ID')
    );
    $this->add(
      'text',
      'special_mail_activity_subject',
      E::ts('Activity Subject')
    );
    $this->add(
      'text',
      'special_mail_activity_contact_id',
      E::ts('Activity Contact ID')
    );
    $this->add(
      'checkbox',
      'special_mail_withdraw_receipt',
      E::ts('Withdraw receipt')
    );

    $this->addButtons(array(
      array('type' => 'next', 'name' => E::ts('Save'), 'isDefault' => TRUE),
      array('type' => 'cancel', 'name' => E::ts('Cancel')),
    ));
  }

  /**
   * Add local and global form rules.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    // Set individual properties.
    $defaults['name'] = $this->profile->getName();
    $defaults['template'] = $this->profile->getTemplate()->getTemplateHTML();
    $defaults['template_pdf_format_id'] = $this->profile->getTemplatePDFFormatId();

    // Set variables.
    $variable_count = 0;
    $variable_elements = array();
    foreach ($this->profile->getVariables() as $variable_name => $variable_value) {
      $variable_count++;
      $defaults['variables--' . $variable_count . '--name'] = (is_numeric($variable_name) ? NULL : $variable_name);
      $defaults['variables--' . $variable_count . '--value'] = $variable_value;
    }

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

  public function validate() {
    $values = $this->exportValues();
    $session = CRM_Core_Session::singleton();

    if (in_array($this->_op, array('create', 'edit'))) {
      /**
       * validate receipt ID pattern.
       */
      try {
        $generator = new CRM_Donrec_Logic_IDGenerator($values['id_pattern'], FALSE);
      } catch (Exception $exception) {
        $this->_errors['id_pattern'] = E::ts('One of the Receipt ID patterns are invalid! Changes NOT saved!');
      }
      /**
       * Validate PDF format
       */
      if (!isset($values['template_pdf_format_id'])) {
        $this->_errors['template_pdf_format_id'] = E::ts(
          'Please select a PDF format. If there are none, create one <a href="%1" target="_blank">here</a>',
          array(
            1 => CRM_Utils_System::url('civicrm/admin/pdfFormats', 'reset=1'),
          )
        );
      }
      /**
       * Validate Custom Mail handling
       */
      if (isset($values['special_mail_handling']) && $values['special_mail_handling'] == TRUE) {
        // validate that all other custom mailing fields are set!
        if (empty($values['special_mail_header'])) {
          $this->_errors['special_mail_header'] = E::ts('If custom Mail handling is activated, a custom mail Header must be set');
        }
        if (empty($values['special_mail_activity_id'])) {
          $this->_errors['special_mail_activity_id'] = E::ts('If custom Mail handling is activated, please specify an activity_id');
        }
        if (empty($values['special_mail_activity_subject'])) {
          $this->_errors['special_mail_activity_subject'] = E::ts('If custom Mail handling is activated, please specify an activity subject');
        }
        if (empty($values['special_mail_activity_contact_id'])) {
          $this->_errors['special_mail_activity_contact_id'] = E::ts('If custom Mail handling is activated, please specify an activity contact_id');
        }
      }
      /**
       * Validate variables.
       */
      $matches = array();
      $variables_values = array_filter($values, function($value, $key) use (&$matches) {
        $match = array();
        if (preg_match('/^variables--([0-9]+)--(?:name|value)$/', $key, $match)) {
          $matches[$match[1]] = TRUE;
          return TRUE;
        }
        else {
          return FALSE;
        }
      }, ARRAY_FILTER_USE_BOTH);
      foreach (array_keys($matches) as $variable_no) {
        // Validate variable names.
        if (
          !empty($values['variables--' . $variable_no . '--name'])
          && !preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $values['variables--' . $variable_no . '--name'])
        ) {
          $this->_errors['variables--' . $variable_no . '--name'] = E::ts('Variable names must be valid PHP variable names.');
        }

        // Check for empty variable names with non-empty values.
        if (
          empty($values['variables--' . $variable_no . '--name'])
          && !empty($values['variables--' . $variable_no . '--value'])
        ) {
          $this->_errors['variables--' . $variable_no . '--name'] = E::ts('Variable names must not be empty when they have a value.');
        }

        // Check for duplicate variable names.
        foreach (array_keys($matches) as $var_no) {
          if ($variable_no == $var_no) {
            continue;
          }
          if (
            !empty($values['variables--' . $variable_no . '--name'])
            && $values['variables--' . $variable_no . '--name'] == $values['variables--' . $var_no . '--name']
          ) {
            $this->_errors['variables--' . $var_no . '--name'] = E::ts('Duplicate variable name.');
          }
        }
      }
    }

    return empty($this->_errors);
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $session = CRM_Core_Session::singleton();

    if (in_array($this->_op, array('create', 'edit'))) {
      // Prevent "template" field from being encoded (HTML entities).
      // @see HTML_QuickForm::exportValues() which is doing a check for that class.
      $this->getElement('template')->setAttribute('class', 'crm-form-wysiwyg');

      $values = $this->exportValues();

      // Set all profile properties.
      foreach (array(
        'name',
        'template',
        'template_pdf_format_id',
        'variables'
               ) as $property) {
        if ($property == 'variables') {
          $values['variables'] = array();
          $matches = array();
          $variables_values = array_filter($values, function($value, $key) use (&$matches) {
            $match = array();
            if (preg_match('/^variables--([0-9]+)--(?:name|value)$/', $key, $match)) {
              $matches[$match[1]] = TRUE;
              return TRUE;
            }
            else {
              return FALSE;
            }
          }, ARRAY_FILTER_USE_BOTH);
          foreach (array_keys($matches) as $variable_no) {
            // Save variables, discarding empty variable names.
            if (!empty($values['variables--' . $variable_no . '--name'])) {
              $values['variables'][$values['variables--' . $variable_no . '--name']] = $values['variables--' . $variable_no . '--value'];
            }
          }
        }
        if (isset($values[$property])) {
          $this->profile->set($property, $values[$property]);
        }
      }

      // Set data attributes.
      foreach (array_keys(CRM_Donrec_Logic_Profile::defaultProfileData()['data']) as $element_name) {
        // Set unchecked checkbox values.
        if (in_array($element_name, array(
          'store_original_pdf',
        )) && !isset($values[$element_name])) {
          $values[$element_name] = 0;
        }

        if (isset($values[$element_name])) {
          $this->profile->setDataAttribute($element_name, $values[$element_name]);
        }
      }

      // extract and set unlock fields
      $donrec_contribution_unlock_fields = array_keys(CRM_Donrec_Logic_Settings::getContributionUnlockableFields());
      $unlock_field_values = [];
      foreach ($donrec_contribution_unlock_fields as $property_name) {
        $field_name                          = 'contribution_unlock_field_' . $property_name;
        $unlock_field_values[$property_name] = (int)!empty($values[$field_name]);
      }
      $this->profile->setDataAttribute('contribution_unlock_fields', $unlock_field_values);


      $this->profile->save();

      $session->setStatus(E::ts('Settings successfully saved'), E::ts('Settings'), 'success');
    }
    elseif ($this->_op == 'delete') {
      $values = $this->exportValues();

      if (isset($values['new_default_profile'])) {
        CRM_Donrec_Logic_Profile::changeDefaultProfile($values['new_default_profile']);
      }
      CRM_Donrec_Logic_Profile::deleteProfile($this->profile->getId());
    }
    elseif ($this->_op == 'default') {
      CRM_Donrec_Logic_Profile::changeDefaultProfile($this->profile->getId());
    }
    elseif ($this->_op == 'activate') {
      $this->profile->activate();
    }
    elseif ($this->_op == 'deactivate') {
      $this->profile->activate(FALSE);
    }

    parent::postProcess();

    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/donrec/profiles', array('reset' => 1)));
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
