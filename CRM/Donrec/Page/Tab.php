<?php

require_once 'CRM/Core/Page.php';

class CRM_Donrec_Page_Tab extends CRM_Core_Page {
  function run() {
    $contact_id = empty($_REQUEST['cid']) ? NULL : $_REQUEST['cid'];

    if($contact_id) {
      $params = array();
      $receipts = CRM_Donrec_Logic_Receipt::getReceiptsForContact($contact_id, $params);
      $display_receipts = array();
      foreach ($receipts as $rec) {
        $display_receipts[$rec->getId()] = $rec->getDisplayProperties();
      }
      $this->assign('cid', $contact_id);
      $this->assign('display_receipts', $display_receipts);
    }

  	parent::run();
  }
}