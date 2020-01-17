<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2020 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

class CRM_Admin_Page_DonrecProfiles extends CRM_Core_Page {

  public function run() {

    $this->assign('profiles', CRM_Donrec_Logic_Profile::getAllData());

    parent::run();
  }

}
