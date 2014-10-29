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
  abstract function exportSingle($chunk, $snapshotId, $is_test);

  /**
   * bulk-export this chunk of items
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   */
  abstract function exportBulk($chunk, $snapshotId, $is_test);

  /**
   * generate the final result
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   *          'download_url: URL to download the result
   *          'download_name: suggested file name for the download
   */
  abstract function wrapUp($snapshotId, $is_test, $is_bulk);


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
   // TODO: rename to updateProcessInformations
   // TODO: use $snapshot->updateProcessInformations()
  protected function setProcessInformation($snapshot_item_id, $values) {
    $all_process_information = $this->engine->getSnapshot()->getProcessInformation($snapshot_item_id);
    $all_process_information[$this->getID()] = $values;
    $this->engine->getSnapshot()->setProcessInformation($snapshot_item_id, $all_process_information);
  }

  /**
   * will create an empty file for the exporter to overwrite
   *
   * @return NULL if not possible, e.g. when the name is already taken,
   *         or   array(file_URL, file_id)
   */
  function createFile($file_name, $is_temp = FALSE) {
    // TODO: make protected again
    $config =  CRM_Core_Config::singleton();

    $params = array(
      'version' => 3,
      'q' => 'civicrm/ajax/rest',
      'sequential' => 1,
      'uri' => $file_name
    );
    $result = civicrm_api('File', 'get', $params);

    if($result['is_error'] == 1 || $result['count'] > 0) {
      return NULL;
    }

    $params = array(
      'version' => 3,
      'q' => 'civicrm/ajax/rest',
      'sequential' => 1,
      'uri' => $file_name
    );
    $result = civicrm_api('File', 'create', $params);

    if($result['is_error'] == 1) {
      return NULL;
    }

    $entityFile = new CRM_Core_DAO_EntityFile();
    $entityFile->file_id = $result['id'];
    $entityFile->entity_id = 1;
    $entityFile->entity_table = 'civicrm_contact';
    $entityFile->save();

    $dl_url = CRM_Utils_System::url("civicrm/file", "reset=1&id=" . $entityFile->file_id . "&eid=1");
    $result = array($dl_url, $entityFile->file_id);

    return $result;
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
