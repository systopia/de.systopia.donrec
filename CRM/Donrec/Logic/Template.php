<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

/**
 * This class holds all template related functions,
 *  including PDF generation
 */
class CRM_Donrec_Logic_Template
{
  private $template_html;

  private $pdf_format_id;

  private $profile_variables = array();

  private function __construct($template_html, $pdf_format_id, $profile_variables = array()) {
    $this->template_html = $template_html;
    $this->pdf_format_id = $pdf_format_id;
    $this->profile_variables = $profile_variables;
  }

  /**
  * Returns an array of all templates that can be used
  * to create donation receipts
  * @return array of template objects
  */
  public static function findAllTemplates() {
    $messageTemplate = new CRM_Core_BAO_MessageTemplate();
    $messageTemplate->orderBy('msg_title' . ' asc');
    $messageTemplate->find();
    $results = array();
    $workflowId = CRM_Donrec_DataStructure::getFirstUsedOptionValueId();
    while ($messageTemplate->fetch()) {
      if($messageTemplate->workflow_id == $workflowId) {
        CRM_Core_DAO::storeValues($messageTemplate, $results[$messageTemplate->id]);
      }
    }
    return empty($results) ? NULL : $results;
  }

  /**
  * Returns a CRM_Donrec_Logic_Template object that uses
  * the specified CRM_Core_BAO_MessageTemplate
  * @param int template id
  * @return CRM_Donrec_Logic_Template object or NULL
   *
   * @deprecated Since 2.0
  */
  public static function create($template_id) {
    $params = array('id' => $template_id);
    $defaults = array();
    $result = CRM_Core_BAO_MessageTemplate::retrieve($params, $defaults);
    if (is_null($result)) {
      return NULL;
    }
    return new self($result->msg_html, $result->pdf_format_id);
  }

  /**
   * Returns a template with the specified id or default template
   * if the template does not exist.
   *
   * @param \CRM_Donrec_Logic_Profile $profile
   *
   * @return self | NULL
   */
  public static function getTemplate($profile) {
    return new self($profile->getTemplateHTML(), $profile->getTemplatePDFFormatId(), $profile->getVariables());
  }

  /**
   * Retrieves the template's HTML content.
   *
   * @return string
   *   The template's HTML content.
   */
  public function getTemplateHTML() {
    return $this->template_html;
  }

  /**
   * Returns default template ID. 
   * If default template doesn't exist, it will install it
   *
   * @return int template ID
   * @throws Exception if there's something wrong with the default template
   *
   * @deprecated Since 2.0.
   */
  public static function getDefaultTemplateID() {
    $default_template_title = sprintf("%s - %s", E::ts('Donation Receipts'), E::ts('Default template'));
    $result = civicrm_api3('MessageTemplate', 'get', array(
      'msg_title'  => $default_template_title,
      'return'     => 'id'));
    if (!empty($result['id'])) {
      // we found it!
      return $result['id'];
    }

    if ($result['count'] > 1) {
      // oops, there's more of them...
      CRM_Core_Error::debug_log_message("de.systopia.donrec: getDefaultTemplate '{$default_template_title}' is ambiguous.");
      $first_result = reset($result['values']);
      return $first_result['id'];
    }

    // default template is not installed yet, so do it
    $default_template_file = dirname(__DIR__) . '/../../templates/Export/default_template.tpl';
    $default_template_html = file_get_contents($default_template_file);
    if($default_template_html === FALSE) {
      throw new Exception("Cannot load default template from '{$default_template_file}'.");
    }

    // TODO: what is this...?
    $workflowId = CRM_Donrec_DataStructure::getFirstUsedOptionValueId();

    $params = array(
      'msg_title'   => $default_template_title,
      'msg_html'    => $default_template_html,
      'is_active'   => 1,
      'workflow_id' => $workflowId,
      'is_default'  => 0,
      'is_reserved' => 0,
    );

    $result = CRM_Core_BAO_MessageTemplate::add($params);
    if ($result) {
      return $result->id;
    } else {
      throw new Exception("Cannot create default template.");
    }
  }

  /**
   * TODO: Document.
   *
   * @return string
   * @throws \Exception
   */
  public static function getDefaultTemplateHTML() {
    $default_template_file = E::path('templates/Export/default_template.tpl');
    $default_template_html = file_get_contents($default_template_file);
    if($default_template_html === FALSE) {
      throw new Exception("Cannot load default template from '{$default_template_file}'.");
    }

    return $default_template_html;
  }

  /**
   * Creates a PDF file from the specified values
   *
   * @param $values
   * @param $parameters
   * @param \CRM_Donrec_Logic_Profile $profile
   * @return string | bool
   *   The filename or FALSE if an error occurred.
*/
  public function generatePDF($values, &$parameters, $profile) {
    $smarty = CRM_Core_Smarty::singleton();
    $config = CRM_Core_Config::singleton();

    // assign all values
    foreach ($values as $token => $value) {
       $smarty->assign($token, $value);
    }

    // Assign profile variables.
    foreach ($this->profile_variables as $name => $value) {
      $smarty->assign($name, $value);
    }

    // callback for custom variables
    CRM_Utils_DonrecCustomisationHooks::pdf_unique_token($smarty, $values);

    // get template
    $html = $this->template_html;

    $pdf_format = CRM_Core_BAO_PdfFormat::getById($this->pdf_format_id);

    // --- watermark injection ---
    $watermark_class = "CRM_Donrec_Logic_WatermarkPreset_" . $profile->getDataAttribute('watermark_preset');
    if (!class_exists($watermark_class)) {
      CRM_Core_Error::debug_log_message("Donrec: Invalid Watermark preset '{$watermark_class}'");
      if (empty(CRM_Core_Config::singleton()->wkhtmltopdfPath)) {
        $watermark_class = 'CRM_Donrec_Logic_WatermarkPreset_DompdfTraditional';
      } else {
        $watermark_class = 'CRM_Donrec_Logic_WatermarkPreset_WkhtmltopdfTraditional';
      }
      CRM_Core_Error::debug_log_message("Donrec: Defaulting to watermark '{$watermark_class}'");
    }
    /* @var \CRM_Donrec_Logic_WatermarkPreset $watermark */
    $watermark = new $watermark_class();
    $watermark->injectMarkup($html, $pdf_format);
    $watermark->injectStyles($html, $pdf_format);

    // identify pdf engine
    $smarty->assign('wk_enabled', !empty($config->wkhtmltopdfPath));

    // --- watermark injection end ---
    // compile template
    $html = $smarty->fetch("string:$html");

    // reset template variables
    $smarty->clearTemplateVars();

    // set up file names
    $filename_export = CRM_Donrec_Logic_File::makeFileName(E::ts("donationreceipt-")."{$values['contributor']['id']}-".date('YmdHis'), ".pdf");

    // render PDF receipt
    $result = file_put_contents($filename_export , CRM_Utils_PDF_Utils::html2pdf($html, null, true, $this->pdf_format_id));
    if($result) {
      return $filename_export;
    }else{
      $parameters['error'] = "Could not write file $filename_export";
      return FALSE;
    }
  }

}
