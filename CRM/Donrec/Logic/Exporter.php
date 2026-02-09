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

/**
 * This is the base class for all exporters
 *
 * phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
 */
abstract class CRM_Donrec_Logic_Exporter {

  public const LOG_TYPE_DEBUG = 'DEBUG';
  public const LOG_TYPE_INFO  = 'INFO';
  public const LOG_TYPE_ERROR = 'ERROR';
  public const LOG_TYPE_FATAL = 'FATAL';

  protected ?CRM_Donrec_Logic_Engine $engine = NULL;

  /**
   * returns the list of implemented exporters
   *
   * @return list<string>
   */
  public static function listExporters() {
    $exporters = ['PDF', 'Dummy', 'CSV', 'GroupedPDF', 'MergedPDF', 'EmailPDF'];

    $manager = CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('de.systopia.civioffice') === CRM_Extension_Manager::STATUS_INSTALLED) {
      $exporters[] = 'PDFCiviOffice';
    }

    return $exporters;
  }

  /**
   * get the class name for the given exporter
   *
   * @param string $exporter_id
   *
   * @return class-string<\CRM_Donrec_Logic_Exporter>
   */
  public static function getClassForExporter($exporter_id) {
    // @phpstan-ignore return.type
    return 'CRM_Donrec_Exporters_' . $exporter_id;
  }

  /**
   * @return string
   */
  abstract public static function name();

  /**
   * @return string
   */
  abstract public static function htmlOptions();

  /**
   * init the exporter with the engine object
   * here, all necessary checks for the exporters 'readyness' should be performed
   *
   * @param \CRM_Donrec_Logic_Engine $engine
   * @return string|null NULL if everything is o.k., an error message string if not
   */
  public function init($engine) {
    $this->engine = $engine;

    // TODO: sanity checks
    return NULL;
  }

  /**
   * @return string
   *   the ID of this importer class
   */
  abstract public function getID();

  /**
   * export an individual receipt
   *
   * @param \CRM_Donrec_Logic_SnapshotReceipt $snapshot_receipt
   * @param bool $is_test
   * @return bool TRUE on success; FALSE on failure
   */
  abstract public function exportSingle($snapshot_receipt, $is_test);

  /**
   * export a bulk-receipt
   *
   * @param \CRM_Donrec_Logic_SnapshotReceipt $snapshot_receipt
   * @param bool $is_test
   * @return bool TRUE on success; FALSE on failure
   */
  abstract public function exportBulk($snapshot_receipt, $is_test);

  /**
   * generate the final result
   *
   * @param int $snapshotId
   * @param bool $is_test
   * @param bool $is_bulk
   * @return array{is_error?: bool, log?: string, download_url?: string, download_name?: string}
   *   'is_error': set if there is a fatal error
   *   'log': array with keys: 'type', 'level', 'timestamp', 'message'
   *   'download_url': URL to download the result
   *   'download_name': suggested file name for the download
   */
  abstract public function wrapUp($snapshotId, $is_test, $is_bulk);

  /**
   * check whether all requirements are met to run this exporter
   *
   * @param \CRM_Donrec_Logic_Profile $profile
   *
   * @return array{is_error: bool, message?: string}
   *   'is_error': set if there is a fatal error
   *   'message': error message
   */
  abstract public function checkRequirements($profile);

  // HELPERS

  /**
   * get the process information for this exporter type
   *  for the given snapshot item
   * @param int $snapshot_item_id
   * @return mixed
   */
  protected function getProcessInformation($snapshot_item_id) {
    $all_process_information = $this->engine->getSnapshot()->getProcessInformation($snapshot_item_id);
    if (isset($all_process_information[$this->getID()])) {
      return $all_process_information[$this->getID()];
    }
    else {
      return [];
    }
  }

  /**
   * set the process information for this exporter type
   *  for the given snapshot item
   * @param int $snapshot_item_id
   * @param array<string, mixed> $array
   *
   * @return void
   */
  protected function updateProcessInformation($snapshot_item_id, $array) {
    $this->engine->getSnapshot()->updateProcessInformation($snapshot_item_id, [$this->getID() => $array]);
  }

  /**
   * create a log entry and add to the give reply
   * @param array<string, mixed> $reply
   * @param string $message
   * @param string $type
   * @param string|null $timestamp
   *
   * @return void
   */
  public static function addLogEntry(&$reply, $message, $type = self::LOG_TYPE_INFO, $timestamp = NULL) {
    if ($timestamp == NULL) {
      $timestamp = date('Y-m-d H:i:s');
    }
    $reply['log'][] = [
      'timestamp'   => $timestamp,
      'type'        => $type,
      'message'     => $message,
    ];
  }

}
