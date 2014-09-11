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
 * This class holds all settings related functions
 */
class CRM_Donrec_Logic_Templates {
  /**
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
  * @return array or NULL
  */
  public static function getDefaultTemplate() {
    $params = array('id' => CRM_Donrec_Logic_Settings::getDefaultTemplate());
    $template = CRM_Core_BAO_MessageTemplate::retrieve($params, $_);
    if (!$template) {
      error_log('de.systopia.donrec: error: default template not found');
      return NULL;
    }
    return $template;
  }

  /**
  *
  * @return
  */
  public static function getTemplate($id, $fallback = TRUE) {
    $id = empty($id) ? -1 : $id;
    $params = array('id' => $id);
    $template = CRM_Core_BAO_MessageTemplate::retrieve($params, $_);
    if (!$template && $fallback) {
      // fallback to default
      return CRM_Donrec_Logic_Templates::getDefaultTemplate();
    }
    return $template;
  }

}
