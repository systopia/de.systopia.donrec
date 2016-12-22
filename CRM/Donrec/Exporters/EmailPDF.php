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
  protected function postprocessPDF($file, $snapshot_line_id) {
    // try to send the email
    $error = $this->sendEmail($snapshot_line_id, $file);

    if ($error) {
      // create error activity

      error_log("ERROR $error");
      $activity_id = $this->getEmailErrorActivity();
      // TODO: create activity

    }
  }


  /**
   * Will try to send the PDF to the given email
   *
   * @return NULL if all good, an error message string if it FAILED
   */
  protected function sendEmail($snapshot_line_id, $pdf_file) {
    try {
      // load contact data
      $snapshot = CRM_Donrec_Logic_Snapshot::getSnapshotForLineID($snapshot_line_id);
      if (!$snapshot) {
        return 'snapshot error';
      }

      $receipt = $snapshot->getLine($snapshot_line_id);
      if (!$receipt) {
        return 'snapshot error';
      }

      $contact = civicrm_api3('Contact', 'getsingle', array('id' => $receipt['contact_id']));

      // load email address
      if (empty($contact['email'])) {
        $error = 'no email';
      } else {
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
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'zip->close() returned false!', CRM_Donrec_Logic_Exporter::LOG_TYPE_ERROR);
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'Dummy process ended.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);    
  }

  /**
   * Get the activity type id for email errors
   *
   * If no such activity exists, create a new one
   */
  public function getEmailErrorActivity() {
    $activity_type_id = (int) CRM_Core_OptionGroup::getValue('activity_type', 'donrec_email_failed', 'id');
    if (!$activity_type_id) {

    }
    // TODO: implement
    return 1;
  }

}
