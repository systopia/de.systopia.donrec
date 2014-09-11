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

/**
* single digit to text
*/
public static function num_to_text_digits($num)
{
  $digit_text = array("null","eins","zwei","drei","vier","fünf","sechs","sieben","acht","neun");
  $digits = array();
  $num = floor($num);
  while ($num > 0) {
    $rest = $num % 10;
    $num = floor($num / 10);
    echo "$rest, $num\n";
    $digits[] = $digit_text[$rest];
  }
  $digits = array_reverse($digits);
  $result = "- ".join(" - ", $digits)." - ";
  return $result;
}

/**
* 0-999 to text
*/
public static function _num_to_text($num)
{
  $hundert = floor($num / 100);
  $zehn = floor(($num - $hundert *100 ) / 10);
  $eins = $num % 10;
  $digit_1 = array("","ein","zwei","drei","vier","fünf","sechs","sieben","acht","neun");
  $digit_10 = array("","zehn","zwanzig","dreißig","vierzig","fünfzig","sechzig","siebzig","achtzig","neunzig");
  $str = "";
  if ($hundert > 0) {
    $str .= $digit_1[$hundert]."hundert ";
  }
  if ($zehn == 0) {
    $str .= $digit_1[$eins];
  } else if ($zehn == 1) {
  if ($eins == 0) {
    $str .= "zehn";
  } else if ($eins == 1) {
    $str .= "elf";
  } else if ($eins == 2){
    $str .= "zwölf";
  } else {
    $str .= $digit_1[$eins]."zehn";
  }
  } else {
    if ($eins == 0) {
      $str .= $digit_10[$zehn];
    } else {
      $str .= $digit_1[$eins]."und".$digit_10[$zehn];
    }
  }
  return $str;
}

/**
* general number to text conversion
*/
public static function num_to_text($num)
{
  static $max_len = 1;
  $strs = array();
  while ($num > 0) {
    $strs[] = self::_num_to_text($num % 1000);
    $num = floor($num / 1000);
  }
  $str = "";
  if (isset($strs[2])) {
    $str .= $strs[2] . " millionen ";
  }
  if (isset($strs[1])) {
    $str .= $strs[1] . " tausend ";
  }
  if (isset($strs[0])) {
    $str .= $strs[0];
  }
  $result = $str == "" ? "null" : trim($str);
  $len = strlen($result);
  if ($len > $max_len) {
    $max_len = $len;
  }
  return $result;
}

}
