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
 * Settings form
 */
class CRM_Admin_Form_Setting_DonrecSettings extends CRM_Admin_Form_Generic {

  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts('Donation Receipts - Settings'));

    // add generic elements
    $this->addYesNo(
      'enable_line_item',
      E::ts('Enable Line Item'),
    );

    // add generic elements
    $this->addElement(
      'text',
      'pdfinfo_path',
      E::ts('External Tool: path to <code>pdfinfo</code>'),
    );

    $this->addElement(
      'text',
      'pdfunite_path',
      E::ts('External Tool: path to <code>pdfunite</code>'),
    );

    $this->addElement(
      'text',
      'packet_size',
      E::ts('Packet size'),
    );

    $this->addElement(
      'text',
      'encryption_command',
      E::ts('Command line for "encryption"'),
    );

    $locationTypeOptions = \Civi\Api4\LocationType::get(FALSE)
      ->addSelect('id', 'display_name')
      ->addWhere('is_active', '=', 1)
      ->execute()
      ->column('display_name', 'id');

    $locationTypeOptions = ['' => E::ts('- None -')] + $locationTypeOptions;

    $this->addElement(
        'select',
        'email_location_type_id',
        E::ts('Email Location Type'),
        $locationTypeOptions,
        ['class' => 'crm-select2 huge']
    );

    // Add CiviOffice configuration.
    $manager = CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('de.systopia.civioffice') === CRM_Extension_Manager::STATUS_INSTALLED) {
      $this->assign('civioffice_enabled', TRUE);
      /**
       * Code borrowed from CiviOffice
       * @see CRM_Civioffice_Form_DocumentFromSingleContact::buildQuickForm()
       */
      $document_renderer_list = [
        '' => E::ts('- None -'),
      ];
      foreach (CRM_Civioffice_Configuration::getDocumentRenderers(TRUE) as $dr) {
        $document_renderer_list[$dr->getURI()] = $dr->getName();
      }
      $this->add(
        'select',
        'civioffice_document_renderer_uri',
        E::ts('Document Renderer'),
        $document_renderer_list,
        FALSE,
        ['class' => 'crm-select2 huge']
      );

      // build document list
      $document_list = [
        '' => E::ts('- None -'),
      ];
      // todo: only show supported source mime types
      foreach (CRM_Civioffice_Configuration::getDocumentStores(TRUE) as $document_store) {
        // todo: recursive
        foreach ($document_store->getDocuments() as $document) {
          /** @var CRM_Civioffice_Document $document */

          // TODO: Mimetype checks could be handled differently in the future:
          // https://github.com/systopia/de.systopia.civioffice/issues/2
          if (!CRM_Civioffice_MimeType::hasSpecificFileNameExtension(
            $document->getName(),
            CRM_Civioffice_MimeType::DOCX
          )) {
            // for now only allow/return docx files
            continue;
          }

          $document_list[$document->getURI()] = "[{$document_store->getName()}] {$document->getName()}";
        }
      }
      $this->add(
        'select',
        'civioffice_document_uri',
        E::ts('Document'),
        $document_list,
        FALSE,
        ['class' => 'crm-select2 huge']
      );
    }

    $this->addButtons([
      [
        'type' => 'next',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
    ]);

    // add a custom form validation rule that allows only positive integers (i > 0)
    $this->registerRule(
      'onlypositive',
      'callback',
      'onlyPositiveIntegers',
      'CRM_Admin_Form_Setting_DonrecSettings'
    );
  }

  public function addRules(): void {
    $this->addRule(
      'packet_size',
      E::ts('Packet size can only contain positive integers'),
      'onlypositive'
    );
  }

  /**
   * @return array<string, mixed>
   *
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues(): array {
    $defaults = parent::setDefaultValues();

    $defaults['enable_line_item'] = CRM_Donrec_Logic_Settings::get('donrec_enable_line_item');
    $defaults['pdfinfo_path'] = CRM_Donrec_Logic_Settings::get('donrec_pdfinfo_path');
    $defaults['pdfunite_path'] = CRM_Donrec_Logic_Settings::get('donrec_pdfunite_path');
    $defaults['packet_size'] = CRM_Donrec_Logic_Settings::get('donrec_packet_size');
    // @todo Change to "donrec_encryption_command" and create corresponding update step.
    $defaults['encryption_command'] = CRM_Donrec_Logic_Settings::get('encryption_command');
    $defaults['civioffice_document_uri'] = CRM_Donrec_Logic_Settings::get('donrec_civioffice_document_uri');
    $defaults['civioffice_document_renderer_uri'] =
      CRM_Donrec_Logic_Settings::get('donrec_civioffice_document_renderer_uri');
    $defaults['email_location_type_id'] = CRM_Donrec_Logic_Settings::get('email_location_type_id');

    return $defaults;
  }

  public function cancelAction(): void {
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/donrec/profiles', ['reset' => 1]));
  }

  public function postProcess(): void {
    // process all form values and save valid settings
    $values = $this->exportValues();

    // save generic settings
    CRM_Donrec_Logic_Settings::set('donrec_packet_size', $values['packet_size']);
    CRM_Donrec_Logic_Settings::set('donrec_enable_line_item', $values['enable_line_item']);

    if ($values['pdfinfo_path']) {
      CRM_Donrec_Logic_Settings::set('donrec_pdfinfo_path', $values['pdfinfo_path']);
    }

    if ($values['pdfunite_path']) {
      CRM_Donrec_Logic_Settings::set('donrec_pdfunite_path', $values['pdfunite_path']);
    }

    if ($values['encryption_command']) {
      // @todo Change to "donrec_encryption_command" and create corresponding update step.
      CRM_Donrec_Logic_Settings::set('encryption_command', $values['encryption_command']);
    }

    if ($values['email_location_type_id']) {
      CRM_Donrec_Logic_Settings::set('email_location_type_id', $values['email_location_type_id']);
    }

    CRM_Donrec_Logic_Settings::set('donrec_civioffice_document_uri', $values['civioffice_document_uri'] ?: NULL);
    CRM_Donrec_Logic_Settings::set(
      'donrec_civioffice_document_renderer_uri',
      $values['civioffice_document_renderer_uri'] ?: NULL
    );

    $session = CRM_Core_Session::singleton();
    $session::setStatus(
      E::ts('Settings successfully saved'),
      E::ts('Settings'),
      'success'
    );
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/donrec/profiles'));
  }

  /**
   * custom validation rule that allows only positive integers
   */
  public static function onlyPositiveIntegers(mixed $value): bool {
    return !($value <= 0);
  }

}
