<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
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
   * Returns default template ID. 
   * If default template doesn't exist, it will install it
   *
   * @return int template ID
   * @throws Exception if there's something wrong with the default template
   */
  public static function getDefaultTemplateID() {
    $default_template_title = sprintf("%s - %s", ts('Donation Receipts', array('domain' => 'de.systopia.donrec')), ts('Default template', array('domain' => 'de.systopia.donrec')));
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
   * get default template (/templates/Export/default_template.tpl)
   * @return template object or NULL
   */
  public static function getDefaultTemplate() {
    // load message template with the default template id
    $params = array('id' => self::getDefaultTemplateID());
    $template = CRM_Core_BAO_MessageTemplate::retrieve($params, $_);
    if (!$template) {
      CRM_Core_Error::debug_log_message('de.systopia.donrec: error: default template not found');
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


    //Add getAllowVolatileTokens
    if ($parameters["allow_volatile_tokens"]) {
      $tokens = CRM_Utils_Token::getTokens($html);
      $contactDetails = CRM_Utils_Token::getTokenDetails(array($values['contact_id']));
      $html = CRM_Utils_Token::replaceContactTokens($html, $contactDetails[0][$values['contact_id']], TRUE, $tokens);

      if (is_array($values["lines"]) && count($values["lines"])  == 1 ) {
        $contribution = reset($values["lines"]);
        $contribution_id = $contribution["contribution_id"];
        $result_contribution = civicrm_api3('Contribution', 'getsingle', array(
          'sequential' => 1,
          'id' => $contribution_id,
        ));

        $html = CRM_Utils_Token::replaceContributionTokens($html, $result_contribution, TRUE, $tokens);

      }
    }


    // --- watermark injection ---
    // identify pdf engine
    $pdf_engine = $config->wkhtmltopdfPath;
    if (!empty($pdf_engine)) {
      $wk_is_enabled = TRUE;
      $watermark_css = '<style>
                        {literal}
                        .watermark {
                          position: fixed;
                          z-index: 999;
                          color: rgba(128, 128, 128, 0.20);
                          -ms-transform: rotate(-45deg); /* IE 9 */
                          -webkit-transform: rotate(-45deg); /* Chrome, Safari, Opera */
                          transform: rotate(-45deg);
                          font-size: 100pt!important;
                        }

                        .watermark-center {
                          left: 10px;
                          top: 400px;
                        }

                        {/literal}
                        </style>
                        ';
    }else{
      $wk_is_enabled = FALSE;
      $watermark_css = '<style>
                        {literal}
                        .watermark {
                          position: fixed;
                          z-index: 999;
                          opacity: 0.10;
                          -ms-transform: rotate(-45deg); /* IE 9 */
                          -webkit-transform: rotate(-45deg); /* Chrome, Safari, Opera */
                          transform: rotate(-45deg);
                          font-size: 100pt!important;
                        }

                        .watermark-center {
                          left: 30px;
                          top: 650px;
                        }

                        {/literal}
                        </style>
                        ';
    }
    $smarty->assign('wk_enabled', $wk_is_enabled);

    // prepare watermark
    $watermark_site = '<div class="watermark watermark-center">{if $watermark}{$watermark}{/if}</div>';

    // find </style> element
    $matches = array();
    preg_match('/<\/style>/', $html, $matches, PREG_OFFSET_CAPTURE);
    if (count($matches) == 1) {
      $head_offset = $matches[0][1];
      $html = substr_replace($html, $watermark_css, $head_offset + strlen($matches[0][0]), 0);
    }else if (count($matches) < 1) {
      CRM_Core_Error::debug_log_message('de.systopia.donrec: watermark css could not be created (</style> not found). falling back to <body>.');
      $matches = array();
      preg_match('/<body>/', $html, $matches, PREG_OFFSET_CAPTURE);
      if (count($matches) == 1) {
        $head_offset = $matches[0][1];
        $html = substr_replace($html, $watermark_css, $head_offset, 0);
      }else{
        CRM_Core_Error::debug_log_message('de.systopia.donrec: watermark could not be created. pdf rendering cancelled.');
        return FALSE;
      }
    }

    // find <body> element
    $matches = array();
    preg_match('/<body[^>]*>/', $html, $matches, PREG_OFFSET_CAPTURE);
    if (count($matches) == 1) {
      $body_offset = $matches[0][1];
      $html = substr_replace($html, $watermark_site, $body_offset + strlen($matches[0][0]), 0);
    }else if (count($matches) < 1) {
      CRM_Core_Error::debug_log_message('de.systopia.donrec: watermark could not be created for site one (<body> not found). pdf rendering cancelled.');
      return FALSE;
    }

    // --- watermark injection end ---
    // compile template
    $html = $smarty->fetch("string:$html");

    // reset template variables
    $smarty->clearTemplateVars();

    // set up file names
    $filename_export = CRM_Donrec_Logic_File::makeFileName(ts("donationreceipt-", array('domain' => 'de.systopia.donrec'))."{$values['contributor']['id']}-".date('YmdHis'), ".pdf");

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
