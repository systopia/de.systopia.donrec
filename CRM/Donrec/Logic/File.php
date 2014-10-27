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
 * This class will find the right place and downloadable URL for
 * temporary AND/OR permament files.
 */
class CRM_Donrec_Logic_File {

  /**
   * This function will take any file and make it temporarily available
   * for download
   * 
   * @param filepath      where to find the file
   * @param deleteSource  if true, the file will be moved to another place rather than copied
   * @param mimetype      the document's MIME type. Autodetect if null
   * 
   * @return a string with an URL where to download the file
   */
  public static function makeTemporary($filepath, $deleteSource = true, $mimetype = null) {
    // TODO: Björn - TTL?

    // TODO: Implement
    return null;
  }

  /**
   * This function will take any file and make it permanently 
   * available as a CiviCRM File entity.
   * 
   * @param filepath      where to find the file
   * @param deleteSource  if true, the original file will be deleted
   * @param mimetype      the document's MIME type. Autodetect if null
   * 
   * @return a CRM_Core_BAO_File object
   */
  public static function makePermanent($filepath, $deleteSource = true, $mimetype = null) {

    // TODO: Implement
    return null;
  }  
}