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
      $profiles[$profile_id]['last_used_date'] = $last_used ? $last_used->getTimestamp() : NULL;
      $profiles[$profile_id]['first_used_date'] = $first_used ? $first_used->getTimestamp() : NULL;
      $profiles[$profile_id]['last_used'] = (!is_null($last_used) ? CRM_Utils_Date::customFormat($last_used->format('Y-m-d H:i:s')) : E::ts('Never'));
      $profiles[$profile_id]['first_used'] = (!is_null($first_used) ? CRM_Utils_Date::customFormat($first_used->format('Y-m-d H:i:s')) : E::ts('Never'));
      $profiles[$profile_id]['usage_count'] = CRM_Donrec_Logic_Profile::getProfile($profile_id)->getUsageCount();
    }

    // Sort.
    if (!$sort = CRM_Utils_Request::retrieve('sort', 'String')) {
      $sort = 'id';
    }
    if (!$direction = CRM_Utils_Request::retrieve('direction', 'String')) {
      $direction = 'ASC';
    }
    $this->assign('sort', $sort);
    $this->assign('direction', $direction);

    usort($profiles, function($a, $b) use ($sort, $direction) {
      $result = 0;
      switch ($sort) {
        case 'id':
        case 'usage_count':
        case 'first_used_date':
        case 'last_used_date':
          if ($direction == 'ASC') {
            $result = $a[$sort] - $b[$sort];
          }
          elseif ($direction == 'DESC') {
            $result = $b[$sort] - $a[$sort];
          }
          break;
        case 'name':
          if ($direction == 'ASC') {
            $result = strcmp($a[$sort], $b[$sort]);
          }
          elseif ($direction == 'DESC') {
            $result = strcmp($b[$sort], $a[$sort]);
          }
          break;
        case 'is_default':
        case 'is_active':
        case 'is_locked':
          if ($direction == 'ASC') {
            $result = $b[$sort] - $a[$sort];
          }
          elseif ($direction == 'DESC') {
            $result = $a[$sort] - $b[$sort];
          }
          break;
      }
      return $result;
    });

    $this->assign('profiles', $profiles);

    parent::run();
  }

}
