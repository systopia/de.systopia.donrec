<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

//FIXME: implement own getID-method
/**
 * Exporter for GROUPED, ZIPPED PDF files
 */
class CRM_Donrec_Exporters_EmailPDF extends CRM_Donrec_Exporters_BasePDF {

  protected $_activity_type_id = NULL;

  /**
   * @return the display name
   */
  static function name() {
    return ts('Send PDFs via Email', array('domain' => 'de.systopia.donrec'));
  }

  /**
   * @return a html snippet that defines the options as form elements
   */
  static function htmlOptions() {
    return '';
  }

  /**
   * @return the ID of this importer class
   */
  public function getID() {
    return 'EMAIL';
  }

  /**
   * check whether all requirements are met to run this exporter
   *
   * @return array:
   *         'is_error': set if there is a fatal error
   *         'message': error message
   */
  public function checkRequirements() {
    // Check if email template is set up
    $template_id = CRM_Donrec_Logic_Settings::getEmailTemplateID();
    if ($template_id) {
      return array('is_error' => FALSE, 'message' => '');
    } else {
      return array(
        'is_error' => TRUE, 
        'message' => ts("Please select email template in the Donrec settings.", array('domain' => 'de.systopia.donrec')),
        );
    }
  }


  /**
   * allows the subclasses to process the newly created PDF file
   */
  protected function postprocessPDF($file, $snapshot_line_id, $is_test) {
    // find the receipt
    $error = NULL;
    $snapshot = CRM_Donrec_Logic_Snapshot::getSnapshotForLineID($snapshot_line_id);
    if (!$snapshot) {
      $error = 'snapshot error';
    } else {
      $receipt = $snapshot->getLine($snapshot_line_id);
      if (!$receipt) {
        $error = 'snapshot error';
      }
    }

    // try to send the email
    if (!$error) {
      $error = $this->sendEmail($receipt, $file, $is_test, $snapshot_line_id);
    }

    if ($error) {
      // create error activity
      if (!$is_test) {
        civicrm_api3('Activity', 'create', array(
          'activity_type_id'   => $this->getEmailErrorActivityID(),
          'subject'            => ts("Donation receipt not delivered", array('domain' => 'de.systopia.donrec')),
          'status_id'          => CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name'),
          'activity_date_time' => date('YmdHis'),
          'source_contact_id'  => $receipt['created_by'],
          'target_id'          => $receipt['contact_id'],
          // 'assignee_contact_id'=> (int) $this->config->getAdminContactID(),
          'details'            => $this->getErrorMessage($error),
          ));
      }

      // store the error in the process information (to be processed in wrap-up)
      $this->updateProcessInformation($snapshot_line_id, array('email_error' => $error));

    } // END if $error
    return $error == NULL;
  }


  /**
   * Will try to send the PDF to the given email
   *
   * @return NULL if all good, an error message string if it FAILED
   */
  protected function sendEmail($receipt, $pdf_file, $is_test, $snapshot_line_id) {
    try {
      // load contact data
      $contact = civicrm_api3('Contact', 'getsingle', array('id' => $receipt['contact_id']));

      // load email address
      if (empty($contact['email'])) {
        return 'no email';
      }

      // load the domain
      list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

      // compile the attachment
      $attachment   = array('fullPath'  => $pdf_file,
                            'mime_type' => 'application/pdf',
                            'cleanName' => ts("Donation Receipt.pdf", array('domain' => 'de.systopia.donrec')));

      // register some variables
      $smarty_variables = array(
        'receipt' => $receipt,
        'contact' => $contact);

      if ($is_test) {
        // in test mode: create message
        $this->updateProcessInformation($snapshot_line_id, array('sent' => $contact['email']));
      } else {
        civicrm_api3('MessageTemplate', 'send', array(
          'id'              => CRM_Donrec_Logic_Settings::getEmailTemplateID(),
          'contact_id'      => $contact['id'],
          'to_name'         => $contact['display_name'],
          'to_email'        => $contact['email'],
          'from'            => "\"{$domainEmailName}\" <{$domainEmailAddress}>",
          'template_params' => $smarty_variables,
          'attachments'     => array($attachment),
          ));
      }
    } catch (Exception $e) {
      return $e->getMessage();
    }
    return NULL;
  }

  /**
   * generate the final result
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   *          'download_url: URL to download the result
   *          'download_name: suggested file name for the download
   */
  public function wrapUp($snapshot_id, $is_test, $is_bulk) {
    $reply = array();
    $error_counters = array();

    $snapshot = CRM_Donrec_Logic_Snapshot::get($snapshot_id);
    $ids = $snapshot->getIds();
    foreach($ids as $id) {
      $proc_info = $this->getProcessInformation($id);
      if(!empty($proc_info['email_error'])) {
        if (!isset($error_counters[$proc_info['email_error']])) {
          $error_counters[$proc_info['email_error']] = 1;
        } else {
          $error_counters[$proc_info['email_error']] += 1;
        }
      }
      if(!empty($proc_info['sent'])) {
        $message = ts("Email <i>would</i> be sent to '%1' (test mode).", array(
          1 => $proc_info['sent'],
          'domain' => 'de.systopia.donrec'));
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
      $this->activity_type_id = (int) CRM_Core_OptionGroup::getValue('activity_type', 'donrec_email_failed', 'name');
      if (!$this->activity_type_id) {
        // create new activity type
        $option_group = civicrm_api3('OptionGroup', 'getsingle', array('name' => 'activity_type'));
        $activity_type = civicrm_api3('OptionValue', 'create', array(
          'option_group_id' => $option_group['id'],
          'name'            => 'donrec_email_failed',
          'label'           => ts("DonationReceipt Failure", array('domain' => 'de.systopia.donrec')),
          ));
        $this->activity_type_id = $activity_type['value'];
      }
    }

    return $this->activity_type_id;
  }

  /**
   * Will produce a human-readable version of the given error
   */
  protected function getErrorMessage($error) {
    switch ($error) {

      case 'snapshot error':
        // no translation, as this shouldn't happen :)
        return 'Internal error, problems with the snapshot. Please file a bug at https://github.com/systopia/de.systopia.donrec/issues';

      case 'no email':
        return ts("No valid email address found for this contact.", array('domain' => 'de.systopia.donrec'));

      default:
        $error_msg = ts("Error was: ", array('domain' => 'de.systopia.donrec'));
        $error_msg .= $error;
        return $error_msg;
    }
  }
}
