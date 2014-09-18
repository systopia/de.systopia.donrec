<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)			 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * This class holds all template related functions,
 *  including PDF generation
 */
class CRM_Donrec_Logic_Template 
{
  // CRM_Core_BAO_MessageTemplate object
  private $_template;

  /**
  * Constructor
  */
  public function __construct($template) {
    $this->_template = $template;
  }

   /**
   * Returns a template with the specified id or default template
   * if the template does not exist.
   * @param int id of the template to retrieve
   * @param bool defines whether the function should return the
   *        default template if it cannot find a template with the
   *        specified id 
   * @return template object or NULL
   */
  public static function getTemplate($id, $fallback = TRUE) {
    $id = empty($id) ? -1 : $id;
    $params = array('id' => $id);
    $template = CRM_Core_BAO_MessageTemplate::retrieve($params, $_);
    if (!$template && $fallback) {
      // fallback to default
      return CRM_Donrec_Logic_Template::getDefaultTemplate();
    }
    return new self($template);
  }

  /**
   * Installs the default template and saves the id as a setting
   * @return bool
   */
  public static function setDefaultTemplate() {
    $default_template_title = sprintf("%s - %s", ts('Donation Receipts'), ts('Default template'));

    $params = array(
        'version' => 3,
        'q' => 'civicrm/ajax/rest',
        'sequential' => 1,
        'msg_title' => $default_template_title,
    );
    $result = civicrm_api('MessageTemplate', 'get', $params);
    if (($result['is_error'] != 0)) {
      error_log(sprintf("de.systopia.donrec: setDefaultTemplate: error: %s", $result['error_message']));
      return FALSE;
    } 

    // the default template has been already set
    if ($result['count'] != 0) {
      return TRUE;
    }

    $default_template_html = file_get_contents(dirname(__DIR__) . '/../../templates/Export/default_template.tpl');
    if($default_template_html === FALSE) {
      error_log('de.systopia.donrec: error: could not open default template file!');
      return FALSE;
    }

    $params = array(
      'msg_title' => $default_template_title,
      'msg_html' => $default_template_html,
      'is_active' => 1,
      'workflow_id' => NULL,
      'is_default' => 0,
      'is_reserved' => 0,
    );

    $result = CRM_Core_BAO_MessageTemplate::add($params);
    if ($result) {
      CRM_Donrec_Logic_Settings::setDefaultTemplate($result->id);    
    }else{
      error_log('de.systopia.donrec: error: could not set default template!');
      return FALSE;
    }
  }

  /**
   * get default template (/templates/Export/default_template.tpl)
   * @return template object or NULL
   */
  public static function getDefaultTemplate() {
    // load message template with the default template id
    $params = array('id' => CRM_Donrec_Logic_Settings::getDefaultTemplate());
    $template = CRM_Core_BAO_MessageTemplate::retrieve($params, $_);
    if (!$template) {
      error_log('de.systopia.donrec: error: default template not found');
      return NULL;
    }
    return new self($template);
  }

  public function getBAOMessageTemplate() {
    return $this->_template;
  }

  /**
  * Creates a PDF file from the specified values
  *
  * @param object template object
  * @param array associative array of values that will be
  *        assigned to the template
  * @param array array of configuration parameters
  * @return bool
  */
  public static function generatePDF($template, $values, $parameters) {
    // TODO: @Niko: this should NOT be a static function, but rather a method of a template object
    $smarty = CRM_Core_Smarty::singleton();

    // assign all values
    foreach ($values as $token => $value) {
       $smarty->assign($token, $value);
    }
    
    // callback for custom variables
    CRM_Utils_DonrecCustomisationHooks::pdf_unique_token($smarty, $values);

    // compile template
    $baoTemplate = $template->getBAOMessageTemplate();
    $html = $baoTemplate->msg_html;
    $html = $smarty->fetch("string:$html");

    // set up file names
    $config = CRM_Core_Config::singleton();
    $filename = CRM_Utils_File::makeFileName("donrec.pdf");
    $filename = sprintf("%s%s", $config->customFileUploadDir, $filename);

    // render PDF receipt
    // TODO: Make the file downloadable (@Niko: this should happen outside this class.)
    return file_put_contents($filename, CRM_Utils_PDF_Utils::html2pdf($html, null, true, $baoTemplate->pdf_format_id));
  }

}
