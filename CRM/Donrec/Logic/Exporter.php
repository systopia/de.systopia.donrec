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
    return array('Dummy', 'PDF');
  }

  /**
   * get the class name for the given exporter
   */
  public static function getClassForExporter($exporter_id) {
    return 'CRM_Donrec_Exporters_' . $exporter_id;
  }

  /**
   * init the exporter with the engine object
   * here, all necessary checks for the exporters 'readyness' should be performed
   * 
   * @return NULL if everything is o.k., an error message string if not
   */
  function init($engine) {
    $this->engine = $engine;

    // TODO: sanity checks
    return NULL;
  }

  /**
   * @return the ID of this importer class
   */
  abstract function getID();

  /**
   * export this chunk of individual items
   * 
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   */
  abstract function exportSingle($chunk);

  /**
   * bulk-export this chunk of items
   * 
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   */
  abstract function exportBulk($chunk);

  /**
   * generate the final result
   * 
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   *          'download_url: URL to download the result
   *          'download_name: suggested file name for the download
   */
  abstract function wrapUp($chunk);


  // HELPERS

  /**
   * get the process information for this exporter type
   *  for the given snapshot item
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
   */
  protected function setProcessInformation($snapshot_item_id, $values) {
    $all_process_information = $this->engine->getSnapshot()->getProcessInformation($snapshot_item_id);
    $all_process_information[$this->getID()] = $values;
    $this->engine->getSnapshot()->setProcessInformation($snapshot_item_id, $all_process_information);
  }

  /**
   * will create an empty file for the exporter to overwrite
   * 
   * @return NULL if not possible, e.g. when the name is already taken,
   *         or   array(file_path, file_URL)
   */
  protected function createFile($file_name, $is_temp = FALSE) {
    // TODO: Implement! This is only a stub!
    $config =  CRM_Core_Config::singleton();
    error_log(print_r($config, 1));
    if ($is_temp) {
      $file = $config->customFileUploadDir . $file_name;
    } else {
      $file = $config->customFileUploadDir . $file_name;
    }

    return array($file, "TODO://file_url.");
  }

  /**
   * create a log entry and add to the give reply
   */
  public static function addLogEntry(&$reply, $message, $type=self::LOG_TYPE_INFO) {
    $dateFormat = CRM_Core_Config::singleton()->dateformatDatetime;
    $reply['log'][] = array(
        'timestamp'   => CRM_Utils_Date::customFormat(date('c'), $dateFormat),
        'type'        => $type,
        'message'     => $message
        );
  }
}