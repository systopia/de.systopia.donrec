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

//FIXME: implement own getID-method
/**
 * Exporter for GROUPED, ZIPPED PDF files
 */
class CRM_Donrec_Exporters_EmailPDF extends CRM_Donrec_Exporters_EncryptedPDF {

  protected static ?int $_sending_to_contact_id   = NULL;
  protected static ?int $_sending_contribution_id = NULL;
  protected static ?int $_sending_with_profile_id = NULL;

  private ?int $activity_type_id = NULL;

  /**
   * @return string
   *   the display name
   */
  public static function name() {
    return E::ts('Send PDFs via Email');
  }

  /**
   * @return string
   *   a html snippet that defines the options as form elements
   */
  public static function htmlOptions() {
    return '';
  }

  /**
   * @return string
   *   the ID of this importer class
   */
  public function getID() {
    return 'EMAIL';
  }

  /**
   * @inheritDoc
   */
  public function checkRequirements($profile): array {
    // Check if email template is set up
    $template_id = CRM_Donrec_Logic_Settings::getEmailTemplateID($profile);
    if (!$template_id) {
      return [
        'is_error' => TRUE,
        'message' => E::ts('Please select email template in the Donrec settings.'),
      ];
    }
    return parent::checkRequirements($profile);
  }

  /**
   * allows the subclasses to process the newly created PDF file
   *
   * @param $file
   * @param \CRM_Donrec_Logic_SnapshotReceipt $snapshot_receipt
   * @param bool $is_test
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function postprocessPDF($file, $snapshot_receipt, $is_test) {
    // encrypt file if configured in profile
    $this->encrypt_file($file, $snapshot_receipt);

    // find the receipt
    $error = NULL;

    // first: get the (previously used) one-line tokens
    $receipt = $snapshot_receipt->getLine();

    // now: get the full token range
    $snapshot_receipt_tokens = $snapshot_receipt->getAllTokens();

    // then merge
    $receipt = array_merge($receipt, $snapshot_receipt_tokens);
    if (!$receipt) {
      $error = 'snapshot error';
    }

    // try to send the email
    if (!$error) {
      $error = $this->sendEmail($receipt, $file, $is_test, $snapshot_receipt->getID());
    }

    if ($error) {
      // create error activity
      if (!$is_test) {
        civicrm_api3('Activity', 'create', [
          'activity_type_id'   => $this->getEmailErrorActivityID(),
          'subject'            => E::ts('Donation receipt not delivered'),
          'status_id'          => CRM_Donrec_CustomData::getOptionValue('activity_status', 'Scheduled', 'name'),
          'activity_date_time' => date('YmdHis'),
          'source_contact_id'  => $receipt['created_by'],
          'target_id'          => $receipt['contact_id'],
          // phpcs:disable Squiz.PHP.CommentedOutCode.Found
          // 'assignee_contact_id'=> (int) $this->config->getAdminContactID(),
          // phpcs:enable
          'details'            => $this->getErrorMessage($error),
        ]);
      }

      // store the error in the process information (to be processed in wrap-up)
      $snapshot_line_id = $snapshot_receipt->getID();
      $this->updateProcessInformation($snapshot_line_id, ['email_error' => $error]);

    }

    return $error == NULL;
  }

  /**
   * Will try to send the PDF to the given email
   *
   * @param array $receipt
   * @param $pdf_file
   * @param bool $is_test
   * @param int $snapshot_line_id
   *
   * @return string|null NULL if all good, an error message string if it FAILED
   */
  protected function sendEmail($receipt, $pdf_file, $is_test, $snapshot_line_id) {
    try {
      // load contact data
      $contact = civicrm_api3('Contact', 'getsingle', ['id' => $receipt['contact_id']]);

      $emailLocationTypeId = CRM_Donrec_Logic_Settings::get('email_location_type_id');
      if (isset($emailLocationTypeId)) {
        // load email address from the configured location type
        $email = \Civi\Api4\Email::get(FALSE)
          ->addSelect('email')
          ->addWhere('location_type_id', '=', $emailLocationTypeId)
          ->addWhere('contact_id', '=', $contact['id'])
          ->addOrderBy('id', 'DESC')
          ->execute()
          ->first();
        if (NULL !== $email) {
          $contact['email'] = $email['email'];
        }
      }

      // load email address
      if (empty($contact['email'])) {
        return 'no email';
      }

      // Get from e-mail from profile or load domain default.
      if ($from_email_id = CRM_Donrec_Logic_Profile::getProfile($receipt['profile_id'])
        ->getDataAttribute('from_email')) {
        $fromEmailAddress = CRM_Core_OptionGroup::values(
          'from_email_address',
          condition: ' AND value = ' . $from_email_id
        );
        foreach ($fromEmailAddress as $key => $value) {
          $from_email_address = CRM_Utils_Mail::pluckEmailFromHeader($value);
          $fromArray = explode('"', $value);
          $from_email_name = $fromArray[1] ?? NULL;
          break;
        }
      }
      else {
        // load the domain
        list($from_email_name, $from_email_address) = CRM_Core_BAO_Domain::getNameAndEmail();
      }

      // compile the attachment
      $attachment = [
        'fullPath'  => $pdf_file,
        'mime_type' => 'application/pdf',
        'cleanName' => E::ts('Donation Receipt.pdf'),
      ];

      // register some variables
      $smarty_variables = [
        'receipt' => $receipt,
        'contact' => $contact,
      ];

      if ($is_test) {
        // in test mode: create message
        $this->updateProcessInformation($snapshot_line_id, ['sent' => $contact['email']]);
      }
      else {
        // set the code for the header hook
        $this->setDonrecMailCode($receipt);

        // send the email
        civicrm_api3('MessageTemplate', 'send', [
          'id'              => CRM_Donrec_Logic_Settings::getEmailTemplateID(
            CRM_Donrec_Logic_Profile::getProfile($receipt['profile_id'])
          ),
          'contact_id'      => $contact['id'],
          'to_name'         => $contact['display_name'],
          'to_email'        => $contact['email'],
          'from'            => "\"{$from_email_name}\" <{$from_email_address}>",
          'template_params' => $smarty_variables,
          'attachments'     => [$attachment],
          'bcc'             => CRM_Donrec_Logic_Profile::getProfile($receipt['profile_id'])
            ->getDataAttribute('bcc_email'),
        ]);

        // unset the code
        $this->unsetDonrecMailCode();
      }
    }
    catch (Exception $e) {
      // @ignoreException
      $this->unsetDonrecMailCode();
      return $e->getMessage();
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function wrapUp($snapshot_id, $is_test, $is_bulk) {
    $reply = [];
    $error_counters = [];

    $snapshot = CRM_Donrec_Logic_Snapshot::get($snapshot_id);
    $ids = $snapshot->getIds();
    foreach ($ids as $id) {
      $proc_info = $this->getProcessInformation($id);
      if (!empty($proc_info['email_error'])) {
        if (!isset($error_counters[$proc_info['email_error']])) {
          $error_counters[$proc_info['email_error']] = 1;
        }
        else {
          $error_counters[$proc_info['email_error']] += 1;
        }
      }
      if (!empty($proc_info['sent'])) {
        $message = E::ts("Email <i>would</i> be sent to '%1' (test mode).", [
          1 => $proc_info['sent'],
        ]);
        CRM_Donrec_Logic_Exporter::addLogEntry($reply, $message, CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
      }
    }

    foreach ($error_counters as $error => $count) {
      $error_msg = "{$count}x " . $this->getErrorMessage($error);
      CRM_Donrec_Logic_Exporter::addLogEntry($reply, $error_msg, CRM_Donrec_Logic_Exporter::LOG_TYPE_ERROR);
    }

    return $reply;
  }

  /**
   * Get the activity type id for email errors
   *
   * If no such activity exists, create a new one
   */
  public function getEmailErrorActivityID() {
    if ($this->activity_type_id === NULL) {
      $this->activity_type_id = (int) CRM_Donrec_CustomData::getOptionValue(
        'activity_type',
        'donrec_email_failed',
        'name'
      );
      if (!$this->activity_type_id) {
        // create new activity type
        $option_group = civicrm_api3('OptionGroup', 'getsingle', ['name' => 'activity_type']);
        $activity_type = civicrm_api3('OptionValue', 'create', [
          'option_group_id' => $option_group['id'],
          'name'            => 'donrec_email_failed',
          'label'           => E::ts('DonationReceipt Failure'),
        ]);
        $activity_type = civicrm_api3('OptionValue', 'getsingle', ['id' => $activity_type['id']]);
        $this->activity_type_id = $activity_type['value'];
      }
    }

    return $this->activity_type_id;
  }

  /**
   * Will produce a human-readable version of the given error
   *
   * @param string $error
   *
   * @return string
   */
  protected function getErrorMessage($error) {
    switch ($error) {

      case 'snapshot error':
        // no translation, as this shouldn't happen :)
        return 'Internal error, problems with the snapshot. Please file a bug at '
          . 'https://github.com/systopia/de.systopia.donrec/issues';

      case 'no email':
        return E::ts('No valid email address found for this contact.');

      default:
        $error_msg = E::ts('Error was: ');
        $error_msg .= $error;
        return $error_msg;
    }
  }

  /**
   * Add headers to sent donation receipts
   *
   * @param array $params
   * @param string $context
   */
  public static function addDonrecMailCodeHeader(&$params, $context) {
    if (self::$_sending_to_contact_id && self::$_sending_contribution_id && self::$_sending_with_profile_id) {
      $donrec_header = CRM_Donrec_Logic_EmailReturnProcessor::$ZWB_HEADER_PATTERN;
      $donrec_header = str_replace('{contact_id}', (string) self::$_sending_to_contact_id, $donrec_header);
      $donrec_header = str_replace('{contribution_id}', (string) self::$_sending_contribution_id, $donrec_header);
      $donrec_header = str_replace('{timestamp}', date('YmdHis'), $donrec_header);
      $donrec_header = str_replace('{profile_id}', (string) self::$_sending_with_profile_id, $donrec_header);

      if (CRM_Donrec_Logic_Profile::getProfile(self::$_sending_with_profile_id)
        ->getDataAttribute('special_mail_handling')) {
        if (self::set_custom_mail_header($params, $donrec_header, self::$_sending_with_profile_id)) {
          // we set header, no custom return path or the 'default' additions needed
          return;
        }
      }
      $params['headers'][CRM_Donrec_Logic_EmailReturnProcessor::$ZWB_HEADER_FIELD] = $donrec_header;
      $params['returnPath'] = CRM_Donrec_Logic_Profile::getProfile(self::$_sending_with_profile_id)
        ->getDataAttribute('return_path_email');
    }
  }

  /**
   * Add header to the configured sub Header. If something already is in there
   * (assumed json encoded), add our values, otherwise just add the header to
   * the configured field
   * @param $params
   * @param $donrec_header
   * @param $profile_id
   *
   * @return bool
   */
  protected static function set_custom_mail_header(&$params, $donrec_header, $profile_id) {
    $special_header = CRM_Donrec_Logic_Profile::getProfile($profile_id)->getDataAttribute('special_mail_header');
    if (empty($special_header)) {
      // we have special treatment configured, but can't get a mailheader. This is a configuration error.
      return FALSE;
    }
    $header_value = json_decode($params['headers'][$special_header], TRUE);

    $header_value['profile_id'] = $profile_id;
    $header_value['contribution_id'] = self::$_sending_contribution_id;
    $header_value['contact_id'] = self::$_sending_to_contact_id;
    $header_value['timestamp'] = date('YmdHis');
    $params['headers'][$special_header] = json_encode($header_value);

    return TRUE;
  }

  /**
   * Set the mailing code to be included in the next outgoing email
   *
   * @param array $receipt
   */
  protected function setDonrecMailCode($receipt) {
    self::$_sending_contribution_id = (int) $receipt['contribution_id'];
    self::$_sending_to_contact_id   = (int) $receipt['contact_id'];
    self::$_sending_with_profile_id = (int) $receipt['profile_id'];
  }

  /**
   * Remove the mailing code to be included in the next outgoing email
   */
  protected function unsetDonrecMailCode(): void {
    self::$_sending_to_contact_id   = NULL;
    self::$_sending_contribution_id = NULL;
    self::$_sending_with_profile_id = NULL;
  }

}
