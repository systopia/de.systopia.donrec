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
    $error = NULL;

    try {
      // load contact data
      $receipt = CRM_Donrec_Logic_Snapshot::getLine($snapshot_line_id);
      $contact = civicrm_api3('Contact', 'getsingle', array('id' => $receipt['contact_id']));

      // load email address
      if (empty($contact['email'])) {
        $error = 'no email';
      } else {
        // load the domain
        list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

        // compile the attachment
        $attachment   = array('fullPath'  => $file,
                              'mime_type' => 'application/pdf',
                              'cleanName' => ts("Donation Receipt.pdf", array('domain' => 'de.systopia.donrec')));

        // register some variables
        $smarty_variables = array(
          'receipt' => $receipt,
          'contact' => $contact);

        // ...and finally send the template via email
        civicrm_api3('MessageTemplate', 'send', array(
          'id'              => $template_id,
          'contact_id'      => $contact['id'],
          'to_name'         => $contact['display_name'],
          'to_email'        => $contact['email'],
          'from'            => "\"{$domainEmailName}\" <{$domainEmailAddress}>",
          'template_params' => $smarty_variables,
          'attachments'     => array($attachment),
          ));
      }
    } catch (Exception $e) {
      $error = $e->getMessage();
    }

    if ($error) {
      // create error activity
      $activity_id = $this->getEmailErrorActivity();
      
    }
  }

  /**
   * postprocessing - not needed here
   */
  public function wrapUp($snapshot_id, $is_test, $is_bulk) {
    // nothing to do here, I think.
    // Sending report?
  }

}
