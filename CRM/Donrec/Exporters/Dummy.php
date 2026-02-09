<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

/**
 * This is a dummy exporter, for testing purposes
 */
class CRM_Donrec_Exporters_Dummy extends CRM_Donrec_Logic_Exporter {

  /**
   * @return string
   *   the display name
   */
  public static function name() {
    return E::ts("Don't generate files");
  }

  /**
   * @return string
   *   a html snippet that defines the options as form elements
   */
  public static function htmlOptions() {
    return '';
  }

  /**
   * @return string
   *   the ID of this importer class
   */
  public function getID() {
    return 'Dummy';
  }

  /**
   * export an individual receipt
   *
   * @param \CRM_Donrec_Logic_SnapshotReceipt $snapshot_receipt
   * @param bool $is_test
   *
   * @return bool
   *   TRUE on success; FALSE on failure
   */
  public function exportSingle($snapshot_receipt, $is_test) {

    // edit the process information
    foreach ($snapshot_receipt->getIDs() as $line_id) {
      $this->updateProcessInformation($line_id, ['test' => 'Dummy was here!']);
    }

    return TRUE;
  }

  /**
   * export a bulk-receipt
   *
   * @param \CRM_Donrec_Logic_SnapshotReceipt $snapshot_receipt
   * @param bool $is_test
   *
   * @return bool
   *   TRUE on success; FALSE on failure
   */
  public function exportBulk($snapshot_receipt, $is_test) {

    // same logic as exportSingle()
    return $this->exportSingle($snapshot_receipt, $is_test);
  }

  /**
   * @inheritDoc
   */
  public function wrapUp($snapshotId, $is_test, $is_bulk) {
    $reply = [];
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'Dummy process ended.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

  /**
   * @inheritDoc
   */
  public function checkRequirements($profile) {
    return ['is_error' => FALSE];
  }

}
