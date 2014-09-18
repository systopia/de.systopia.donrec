<?php

require_once 'CRM/Core/Page.php';

class CRM_Donrec_Page_Test extends CRM_Core_Page {
  function run() {

  	$r = CRM_Donrec_Logic_Template::findAllTemplates();
  	printf("%s<br />", print_r($r, TRUE));
  	printf("%s", $r === NULL ? "NULL" : "not NULL");

  	parent::run();
  }
}