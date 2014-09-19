<?php

require_once 'CRM/Core/Page.php';

class CRM_Donrec_Page_Test extends CRM_Core_Page {
  function run() {

  	$id = 1;
  	$line_id = array(1, 2, 3);

  	$snapshot = CRM_Donrec_Logic_Snapshot::get($id);
  	$params = array();
  	$r = CRM_Donrec_Logic_Receipt::createBulkFromSnapshot($snapshot, $line_id, $params);
  	printf("%s<br />", print_r($r, TRUE));
  	printf("%s<br />", $r === NULL ? "NULL" : "not NULL");
  	printf("%s", $r === TRUE ? "TRUE" : "FALSE");

  	//$v = new CRM_Donrec_Logic_ReceiptItem();
  	//$v->bla();

  	parent::run();
  }
}