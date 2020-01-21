<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This is the base class for all exporters
 */
abstract class CRM_Donrec_Logic_Exporter {

  const LOG_TYPE_DEBUG =   'DEBUG';
  const LOG_TYPE_INFO  =   'INFO';
  const LOG_TYPE_ERROR =   'ERROR';
  const LOG_TYPE_FATAL =   'FATAL';


  protected $engine = NULL;

  /**
   * returns the list of implemented exporters
   */
  public static function listExporters() {
    return array('PDF', 'Dummy', 'CSV', 'GroupedPDF', 'EmailPDF');
  }

  /**
   * get the class name for the given exporter
   *
   * @param $exporter_id
   *
   * @return string
   */
  public static function getClassForExporter($exporter_id) {
    return 'CRM_Donrec_Exporters_' . $exporter_id;
  }

  /**
   * init the exporter with the engine object
   * here, all necessary checks for the exporters 'readyness' should be performed
   *
   * @param $engine
   * @return NULL if everything is o.k., an error message string if not
*/
  function init($engine) {
    $this->engine = $engine;

    // TODO: sanity checks
    return NULL;
  }

  /**
   * @return int
   *   the ID of this importer class
   */
  abstract function getID();

  /**
   * export an individual receipt
   *
   * @param $snapshot_receipt
   * @param $is_test
   * @return TRUE on success; FALSE on failure
*/
  abstract function exportSingle($snapshot_receipt, $is_test);

  /**
   * export a bulk-receipt
   *
   * @param $snapshot_receipt
   * @param $is_test
   * @return TRUE on success; FALSE on failure
*/
  abstract function exportBulk($snapshot_receipt, $is_test);

  /**
   * generate the final result
   *
   * @param $snapshotId
   * @param $is_test
   * @param $is_bulk
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   *          'download_url: URL to download the result
   *          'download_name: suggested file name for the download
*/
  abstract function wrapUp($snapshotId, $is_test, $is_bulk);


  /**
   * check whether all requirements are met to run this exporter
   *
   * @return array:
   *         'is_error': set if there is a fatal error
   *         'message': error message
   */
  abstract function checkRequirements();


  // HELPERS

  /**
   * get the process information for this exporter type
   *  for the given snapshot item
   * @param $snapshot_item_id
   * @return array|mixed
*/
  protected function getProcessInformation($snapshot_item_id) {
    $all_process_information = $this->engine->getSnapshot()->getProcessInformation($snapshot_item_id);
    if (isset($all_process_information[$this->getID()])) {
      return $all_process_information[$this->getID()];
    } else {
      return array();
    }
  }

  /**
   * set the process information for this exporter type
   *  for the given snapshot item
   * @param $snapshot_item_id
   * @param $array
*/
  protected function updateProcessInformation($snapshot_item_id, $array) {
    $this->engine->getSnapshot()->updateProcessInformation($snapshot_item_id, array($this->getID() => $array));
  }

  /**
   * create a log entry and add to the give reply
   * @param $reply
   * @param $message
   * @param string $type
   * @param null $timestamp
*/
  public static function addLogEntry(&$reply, $message, $type=self::LOG_TYPE_INFO, $timestamp = NULL) {
    if ($timestamp == NULL) {
      $timestamp = date('Y-m-d H:i:s');
    }
    $reply['log'][] = array(
        'timestamp'   => $timestamp,
        'type'        => $type,
        'message'     => $message
        );
  }
}
