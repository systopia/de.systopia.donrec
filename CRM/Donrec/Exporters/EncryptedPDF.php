<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

/**
 * This is the PDF exporter base class
 */
abstract class CRM_Donrec_Exporters_EncryptedPDF extends CRM_Donrec_Exporters_BasePDF {

  // generate a random password with default length 15
  private function generate_password($length = 15) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
      $password .= $characters[mt_rand(0, strlen($characters) - 1)];
    }

    return $password;
  }

  // checks if pdftk is installed, but only if encryption is activates in the profile
  public function checkRequirements($profile = NULL): array {
      $result = array();
      $result['is_error'] = FALSE;
      $result['message'] ='';
      if($profile->getDataAttribute('enable_encryption')){

        // Check if encryption tool is available.
        $path = CRM_Donrec_Logic_Settings::get('encryption_command');
        if (empty($path)) {
          $result['is_error'] = TRUE;
          $result['message'] = E::ts('no path to encryption tool given. please check the donrec settings');
        }else{

          // "Ping" encyrption command.
          $cmd = escapeshellcmd($path) . ' 2>&1';
          exec($cmd, $output, $ret_status);

          if($ret_status != 0){
            $result['is_error'] = TRUE;
            $result['message'] = E::ts('execution of') . ' ' . $path . ' ' . E::ts('failed');
          }
        }
      }
      return $result;
  }

  // encrypt the given file if the setting in the profile says so
  protected function encrypt_file($file, $snapshot_receipt): void {

    if ($snapshot_receipt->getProfile()->getDataAttribute('enable_encryption')) {
      $password = $this->generate_password();
      $cmd = CRM_Donrec_Logic_Settings::get('encryption_command');
      $tmpfile = $file . "_tmp";
      rename($file,$tmpfile);

      // Puzzle the real command together here.
      $cmd .= " "
        . escapeshellarg($tmpfile)
        . " output " . escapeshellarg($file)
        . " owner_pw " . escapeshellarg($password)
        . " allow printing screenreaders";
      $output = [];
      $result_code = NULL;
      exec(escapeshellcmd($cmd) . " 2>&1", $output, $result_code);
      if ($result_code !== 0) {
        Civi::log()->error(E::ts('Encryption of DonRec PDF failed. Output was: %1', [
          1 => implode("\n", $output)
        ]));
      }
    }
  }
}
