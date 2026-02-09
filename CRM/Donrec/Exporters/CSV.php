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
 * This exporter creates CSV files
 */
class CRM_Donrec_Exporters_CSV extends CRM_Donrec_Logic_Exporter {

  /**
   * @return string
   *   the display name
   */
  public static function name() {
    return E::ts('CSV File');
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
    return 'CSV';
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
    return $this->exportLine($snapshot_receipt, $is_test, FALSE);
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
    return $this->exportLine($snapshot_receipt, $is_test, TRUE);
  }

  /**
   * @inheritDoc
   */
  public function wrapUp($snapshot_id, $is_test, $is_bulk) {
    $snapshot = CRM_Donrec_Logic_Snapshot::get($snapshot_id);
    $reply = [];

    // open file
    $preferredFileName = E::ts('donation_receipts');
    $preferredFileSuffix = E::ts('.csv');
    $temp_file = CRM_Donrec_Logic_File::makeFileName($preferredFileName, $preferredFileSuffix);
    $handle = fopen($temp_file, 'w');

    // get headers
    $headers = CRM_Donrec_Logic_ReceiptTokens::getFullTokenList();
    $headers = $this->flattenTokenData($headers);
    $headers = array_keys($headers);
    $header_written = FALSE;

    // write them all into the file
    $ids = $snapshot->getIds();
    foreach ($ids as $id) {
      $proc_info = $snapshot->getProcessInformation($id);
      $csv_data = $proc_info['CSV']['csv_data'];
      if (!empty($csv_data)) {
        if (!$header_written) {
          // extend header by extra fields
          $headers = array_merge($headers, array_keys($csv_data));
          $headers = array_unique($headers);

          // write header
          fputcsv($handle, $headers, ';', '"');
          $header_written = TRUE;
        }

        // create and write a line
        $line = [];
        foreach ($headers as $field) {
          if (isset($csv_data[$field])) {
            $line[$field] = $csv_data[$field];
          }
          else {
            $line[$field] = '';
          }
        }
        fputcsv($handle, $line, ';', '"');
      }
    }

    // get process info iterator
    fclose($handle);

    // create the file
    $file = CRM_Donrec_Logic_File::createTemporaryFile($temp_file, $preferredFileName . $preferredFileSuffix);
    Civi::log()->debug("de.systopia.donrec: resulting CSV file URL is '$file'.");
    if (!empty($file)) {
      $reply['download_name'] = $preferredFileName;
      $reply['download_url'] = $file;
    }

    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'CSV process ended.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

  /**
   * @inheritDoc
   */
  public function checkRequirements($profile) {
    return ['is_error' => FALSE];
  }

  /**
   * wil create bulk and/or individual items as CSV lines that
   * are stored in the process information field
   *
   * @param \CRM_Donrec_Logic_SnapshotReceipt $snapshotReceipt
   * @param bool $is_test
   * @param bool $is_bulk
   *
   * @return bool
   */
  private function exportLine($snapshotReceipt, $is_test, $is_bulk) {

    $values = $snapshotReceipt->getAllTokens();
    $flattened_data = $this->flattenTokenData($values);

    // add accumulated data
    $flattened_data['individual_count']                 = 0;
    $flattened_data['individual_receive_date']          = '';
    $flattened_data['individual_total_amount']          = '';
    $flattened_data['individual_non_deductible_amount'] = '';
    $flattened_data['individual_financial_type_id']     = '';
    $flattened_data['individual_financial_type']        = '';
    foreach ($values['lines'] as $line_id => $line) {
      $flattened_data['individual_count'] += 1;
      if ($flattened_data['individual_count'] > 1) {
        $flattened_data['individual_receive_date']          .= "\n";
        $flattened_data['individual_total_amount']          .= "\n";
        $flattened_data['individual_non_deductible_amount'] .= "\n";
        $flattened_data['individual_financial_type_id']     .= "\n";
        $flattened_data['individual_financial_type']        .= "\n";
      }
      $flattened_data['individual_receive_date']          .= $line['receive_date'];
      $flattened_data['individual_total_amount']          .= $line['total_amount'];
      $flattened_data['individual_non_deductible_amount'] .= $line['non_deductible_amount'];
      $flattened_data['individual_financial_type_id']     .= $line['financial_type_id'];
      $flattened_data['individual_financial_type']        .= $line['financial_type'];
    }

    // store the data in the process information
    $this->updateProcessInformation($snapshotReceipt->getID(), ['csv_data' => $flattened_data]);

    return TRUE;
  }

  private function flattenTokenData($values) {
    $flattened_data = [];
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        // phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedIf
        if ($key === 'lines' || $key === 'items') {
          // don't do anything here
        }
        // phpcs:enable
        else {
          foreach ($value as $key2 => $value2) {
            $flattened_data[$key . '_' . $key2] = $value2;
          }
        }
      }
      else {
        $flattened_data[$key] = $value;
      }
    }
    return $flattened_data;
  }

}
