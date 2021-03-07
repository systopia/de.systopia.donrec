<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

require_once 'CRM/Core/Page.php';

/**
 * This page will do nothing else but show a previously stored temp file
 */
class CRM_Donrec_Page_Tempfile extends CRM_Core_Page {

  const PREFIX = 'donrec-tmp-';

  /**
   * page expects three parameters
   * 
   * @param path  - file path of file in temp folder
   * @param name  - file name
   * @param mime  - mime type. Autodetect if not given
   */
  function run() {
    if (!empty($_REQUEST['path'])) {
      $filename = sys_get_temp_dir() . '/' . self::PREFIX . $_REQUEST['path'];
      if (file_exists($filename)) {

        // set file name
        if (empty($_REQUEST['name'])) {
          header("Content-Disposition: attachment; filename=File");
        } else {
          header("Content-Disposition: attachment; filename=" . $_REQUEST['name']);
        }

        // set content type
        if (empty($_REQUEST['type'])) {
          header('Content-Type: ' . mime_content_type($filename));
        } else {
          header('Content-Type: ' . $_REQUEST['type']);
        }

        // dump file contents in stream
        readfile($filename);

        CRM_Utils_System::civiExit();
      }
    }
    parent::run();
  }


  /**
   * This function will take any file and make it temporarily available
   * for download
   *
   * @param string $path
   * @param null $name
   * @param bool $deleteSource
   * @param null $mimetype
   *
   * @return string | NULL
   *   a string with an URL where to download the file
   */
  public static function createFromFile($path, $name = null, $deleteSource = true, $mimetype = null) {
    $tempfile = tempnam(sys_get_temp_dir(), self::PREFIX);
    if (file_exists($path)) {
      // create the temp file
      if ($deleteSource) {
        rename($path, $tempfile);
      } else {
        copy($path, $tempfile);
      }

      // create an URL to download
      $file_id = substr($tempfile, (strpos($tempfile, self::PREFIX) + strlen(self::PREFIX)));
      $urlparams = 'path=' . urlencode($file_id);
      if ($name) {
        $urlparams .= '&name=' . urlencode($name);
      } else {
        $urlparams .= '&name=' . urlencode(basename($path));
      }
      if (!empty($mimetype)) {
        $urlparams .= '&type=' . urlencode($mimetype);
      }

      $url = CRM_Utils_System::url('civicrm/donrec/showfile', $urlparams);
      return $url;
    } else {
      return null;
    }
  }
}
