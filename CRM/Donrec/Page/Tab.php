<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

require_once 'CRM/Core/Page.php';

class CRM_Donrec_Page_Tab extends CRM_Core_Page {
  function run() {
    $contact_id = empty($_REQUEST['cid']) ? NULL : $_REQUEST['cid'];
    $scroll_to_receipt = empty($_REQUEST['rid']) ? NULL : $_REQUEST['rid'];

    if($contact_id && CRM_Core_Permission::check('view and copy receipts')) {
      $params = array();
      $receipts = CRM_Donrec_Logic_Receipt::getReceiptsForContact($contact_id, $params);
      $display_receipts = array();
      foreach ($receipts as $rec) {
        $display_receipts[$rec->getId()] = $rec->getDisplayProperties();
      }
      $this->assign('cid', $contact_id);
      $this->assign('display_receipts', $display_receipts);
      if ($scroll_to_receipt) {
        $this->assign('scroll_to', $scroll_to_receipt);
      }
    }

    // permissions
    $this->assign('can_view_copy', CRM_Core_Permission::check('view and copy receipts'));
    $this->assign('can_create_withdraw', CRM_Core_Permission::check('create and withdraw receipts'));
    $this->assign('can_delete', CRM_Core_Permission::check('delete receipts'));

    // do we keep original pdf files?
    $this->assign('store_pdf', CRM_Donrec_Logic_Settings::saveOriginalPDF());

    parent::run();
  }
}
