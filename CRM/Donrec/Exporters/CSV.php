<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * This exporter creates CSV files
 */
class CRM_Donrec_Exporters_CSV extends CRM_Donrec_Logic_Exporter {

  /**
   * @return the display name
   */
  static function name() {
    return ts("CSV File");
  }

  /**
   * @return a html snippet that defines the options as form elements
   */
  static function htmlOptions() {
    return '';
  }

  /**
   * @return the ID of this importer class
   */
  public function getID() {
    return 'CSV';
  }


  /**
   * export this chunk of individual items
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'timestamp', 'message'
   */
  public function exportSingle($chunk, $snapshotId, $is_test) {
    return $this->exportLine($chunk, $snapshotId, $is_test, false);
  }

  /**
   * bulk-export this chunk of items
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   */
  public function exportBulk($chunk, $snapshotId, $is_test) {
    return $this->exportLine($chunk, $snapshotId, $is_test, true);
  }

  /**
   * generate the final result
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   *          'download_url: URL to download the result
   *          'download_name: suggested file name for the download
   */
  public function wrapUp($chunk, $is_test, $is_bulk) {
    $reply = array();

    // TODO: get process info iterator

    // TODO: write headers, compile all information into one file

    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'CSV process ended.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

  /**
   * check whether all requirements are met to run this exporter
   *
   * @return array:
   *         'is_error': set if there is a fatal error
   *         'message': error message
   */
  public function checkRequirements() {
    return array('is_error' => FALSE);
  }

  /**
   * wil create bulk and/or individual items as CSV lines that
   * are stored in the process information field
   */
  private function exportLine($chunk, $snapshotId, $is_test, $is_bulk) {
    $reply = array();

    // TODO: get data from snapshot (#1399)

    // TODO: create single CSV line
    

    // TODO: create temp file

    // add a log entry
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'Dummy processed ' . count($chunk) . ' items.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }
}
