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

    $profiles = CRM_Donrec_Logic_Profile::getAllData();
    foreach ($profiles as $profile_id => $profile_data) {
      $last_used = CRM_Donrec_Logic_Profile::getProfile($profile_id)->getLastUsage();
      $first_used = CRM_Donrec_Logic_Profile::getProfile($profile_id)->getFirstUsage();
      $profiles[$profile_id]['last_used'] = (!is_null($last_used) ? CRM_Utils_Date::customFormat($last_used->format('Y-m-d H:i:s')) : E::ts('Never'));
      $profiles[$profile_id]['first_used'] = (!is_null($first_used) ? CRM_Utils_Date::customFormat($first_used->format('Y-m-d H:i:s')) : E::ts('Never'));
      $profiles[$profile_id]['usage_count'] = CRM_Donrec_Logic_Profile::getProfile($profile_id)->getUsageCount();
    }
    $this->assign('profiles', $profiles);

    parent::run();
  }

}
