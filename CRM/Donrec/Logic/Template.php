<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
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
  private $_template;

  private function __construct($template) {
    $this->_template = $template;
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
  */
  public static function create($template_id) {
    $params = array('id' => $template_id);
    $defaults = array();
    $result = CRM_Core_BAO_MessageTemplate::retrieve($params, $defaults);
    if (is_null($result)) {
      return NULL;
    }
    return new self($result);
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

    // TODO: don't use an ajax-request here.
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
      // update the donrec-setting
      CRM_Donrec_Logic_Settings::setDefaultTemplate($result['values'][0]['id']);
      return TRUE;
    }

    $default_template_html = file_get_contents(dirname(__DIR__) . '/../../templates/Export/default_template.tpl');
    if($default_template_html === FALSE) {
      error_log('de.systopia.donrec: error: could not open default template file!');
      return FALSE;
    }

    $workflowId = CRM_Donrec_DataStructure::getFirstUsedOptionValueId();

    $params = array(
      'msg_title' => $default_template_title,
      'msg_html' => $default_template_html,
      'is_active' => 1,
      'workflow_id' => $workflowId,
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

  /**
  * Creates a PDF file from the specified values
  *
  * @param array associative array of values that will be
  *        assigned to the template
  * @param array of configuration parameters
  * @return filename or False
  */
  public function generatePDF($values, &$parameters) {
    $smarty = CRM_Core_Smarty::singleton();
    $config = CRM_Core_Config::singleton();

    // assign all values
    foreach ($values as $token => $value) {
       $smarty->assign($token, $value);
    }

    // callback for custom variables
    CRM_Utils_DonrecCustomisationHooks::pdf_unique_token($smarty, $values);

    // get template
    $html = $this->_template->msg_html;

    // --- watermark injection ---
    // identify pdf engine
    $pdf_engine = $config->wkhtmltopdfPath;
    if (!empty($pdf_engine)) {
      $wk_is_enabled = TRUE;
    }else{
      $wk_is_enabled = FALSE;
    }

    // prepare watermark
    $watermark_css = '<style>
                      {literal}
                      .watermark {
                        position: fixed;
                        z-index: 999;
                        ' .
                        ($wk_is_enabled) ?
                        'color: rgba(128, 128, 128, 0.65);' :
                        'opacity: 0.65;'
                        . '
                        -ms-transform: rotate(-45deg); /* IE 9 */
                        -webkit-transform: rotate(-45deg); /* Chrome, Safari, Opera */
                        transform: rotate(-45deg);
                        font-size: 100pt!important;
                      }

                      .watermark-center {
                        left: 60px;
                        top: 600px;
                      }

                      .watermark-top {
                        left: 60px;
                        top: 180px;
                      }
                      {/literal}
                      </style>
                      ';
    $watermark_site1 = '<div class="watermark watermark-center">{if $watermark}{$watermark}{/if}</div>';
    $watermark_site2 = '<div class="watermark watermark-top">{if $watermark}{$watermark}{/if}</div>';

    // find </style> element
    $matches = array();
    preg_match('/<\/style>/', $html, $matches, PREG_OFFSET_CAPTURE);
    if (count($matches) == 1) {
      $head_offset = $matches[0][1];
      $html = substr_replace($html, $watermark_css, $head_offset + strlen($matches[0][0]), 0);
    }else if (count($matches) < 1) {
      error_log('de.systopia.donrec: watermark css could not be created (</style> not found). falling back to <body>.');
      $matches = array();
      preg_match('/<body>/', $html, $matches, PREG_OFFSET_CAPTURE);
      if (count($matches) == 1) {
        $head_offset = $matches[0][1];
        $html = substr_replace($html, $watermark_css, $head_offset, 0);
      }else{
        error_log('de.systopia.donrec: watermark could not be created. pdf rendering cancelled.');
        return FALSE;
      }
    }

    // find <body> element
    $matches = array();
    preg_match('/<body[^>]*>/', $html, $matches, PREG_OFFSET_CAPTURE);
    if (count($matches) == 1) {
      $body_offset = $matches[0][1];
      $html = substr_replace($html, $watermark_site1, $body_offset + strlen($matches[0][0]), 0);
    }else if (count($matches) < 1) {
      error_log('de.systopia.donrec: watermark could not be created for site one (<body> not found). pdf rendering cancelled.');
      return FALSE;
    }

    // find <div class="newpage"> element
    $matches = array();
    preg_match('/<div\s*class=["\']newpage["\']\s*>/', $html, $matches, PREG_OFFSET_CAPTURE);
    if (count($matches) == 1) {
      $newpage_offset = $matches[0][1];
      $html = substr_replace($html, $watermark_site2, $newpage_offset + strlen($matches[0][0]), 0);
    }else if (count($matches) < 1) {
      error_log('de.systopia.donrec: watermark could not be created for site two (<div id="newpage"> not found). possible manipulation of the template file? pdf rendering cancelled.');
      return FALSE;
    }

    // --- watermark injection end ---
    error_log($html);
    // compile template
    $html = $smarty->fetch("string:$html");

    // set up file names
    $filename_export = CRM_Donrec_Logic_File::makeFileName(ts("donationreceipt-")."{$values['contributor']['id']}-{$values['today']}", ".pdf");

    // render PDF receipt
    $result = file_put_contents($filename_export , CRM_Utils_PDF_Utils::html2pdf($html, null, true, $this->_template->pdf_format_id));
    if($result) {
      return $filename_export;
    }else{
      $parameters['error'] = "Could not write file $filename_export";
      return FALSE;
    }
  }

}
