<?php

require_once 'CRM/Core/Page.php';

class CRM_Donrec_Page_Tab extends CRM_Core_Page {
  function run() {


    if (CRM_Utils_Array::value('view', $_REQUEST, False)) {
      $rid = empty($_REQUEST['rid']) ? NULL : $_REQUEST['rid'];
      if (empty($rid)) {
        //TODO: ERROR
      }
      $receipt = new CRM_Donrec_Logic_Receipt($rid);
      $file_url = $receipt->getFile();

      //redirect to the pdf
      CRM_Utils_System::redirect($file_url);
    } else {
      $contact_id = empty($_REQUEST['cid']) ? NULL : $_REQUEST['cid'];

      if($contact_id) {
        $params = array();
        $receipts = CRM_Donrec_Logic_Receipt::getReceiptsForContact($contact_id, $params);
        $display_receipts = array();
        $view_url = array();
        foreach ($receipts as $rec) {
          $rid = $rec->getId();
          $display_receipts[$rid] = $rec->getDisplayProperties();
          $display_receipts[$rid]['view_url'] = CRM_Utils_System::url('civicrm/donrec/tab', "view=1&rid=$rid");
        }
        $this->assign('cid', $contact_id);
        $this->assign('display_receipts', $display_receipts);
        $this->assign('view_url', $view_url);
      }

      // admin only
      $this->assign('is_admin', CRM_Core_Permission::check('administer CiviCRM'));
      // do we keep original pdf files?
      $this->assign('store_pdf', CRM_Donrec_Logic_Settings::saveOriginalPDF());
    }
  	parent::run();
  }
}
