<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

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
          $result['is_eror'] = TRUE;
          $result['message'] = 'no path to encryption tool given. please check the donrec settings';
        }else{

          // "Ping" encyrption command.
          $cmd = escapeshellcmd($path) . ' 2>&1';
          exec($cmd, $output, $ret_status);

          if($ret_status != 0){
            $result['is_error'] = TRUE;
            $result['message'] ='execution of ' . $path . ' failed';
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

      // puzzle the real command together here
      $cmd .= " ". $tmpfile . " output " . $file . " owner_pw " . $password . " allow printing screenreaders";

      // TODO: Error handling of the command execution

      $ouput = shell_exec(escapeshellcmd($cmd));
    }
  }
}
