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
   * @param path          where to find the file
   * @param name          end-user name of the file
   * @param deleteSource  if true, the file will be moved to another place rather than copied
   * @param mimetype      the document's MIME type. Autodetect if null
   * 
   * @return a string with an URL where to download the file
   */
  public static function createTemporaryFile($path, $name = null, $deleteSource = true, $mimetype = null) {
    return CRM_Donrec_Page_Tempfile::createFromFile($path, $name, $deleteSource, $mimetype);
  }


  /**
   * This function will take any file and make it permanently 
   * available as a CiviCRM File entity.
   * 
   * @param path          where to find the file
   * @param contact_id    which contact to connect to
   * 
   * @param mimetype      the document's MIME type. Autodetect if null
   * 
   * @return an array containing the created file object, including a generated url
   */
  public static function createPermanentFile($path, $name = null, $contact_id, $mimetype = null, $description = '') {
    $config = CRM_Core_Config::singleton();
    if (!file_exists($path)) return null;

    // TODO: check if a file object already exists?

    // move file to a permanent folder
    $newPath = $config->customFileUploadDir . basename($path);
    rename($path, $newPath);    

    // find mime type
    if (empty($mimetype)) {
      $mimetype = mime_content_type($newPath);
    }

    // create the file object
    if (empty($description)) $description = $name;
    $file = civicrm_api('File', 'create', array(
      'version'       => 3,
      'uri'           => basename($newPath),
      'mime_type'     => $mimetype,
      'description'   => $description,
      ));

    if (!empty($file['is_error'])) {
      error_log("de.systopia.donrec: couldn't create file object - " . $file['error_message']);
      return null;
    }

    if ($contact_id) {
      // link the file to a contact  (there is no API call for this...)
      $entityFile = new CRM_Core_DAO_EntityFile();
      $entityFile->file_id = $file['id'];
      $entityFile->entity_id = $contact_id;
      $entityFile->entity_table = 'civicrm_contact';
      $entityFile->save();
    }

    // build reply
    $reply = $file['values'];
    $reply['url'] = CRM_Utils_System::url("civicrm/file", "reset=1&id=" . $file['id'] . "&eid=$contact_id");
    $reply['path'] = $newPath;
    
    return $reply;
  }


  /**
   * Will create a suitable file for writing to
   * 
   * @param preferredName The preferred name. There will probably by a suffix appended to it
   * 
   * @return a string with a file path
   */
  public static function makeFileName($preferredName, $suffix='') {
    // generate a uniq temp file
    $new_file = tempnam(sys_get_temp_dir(), $preferredName . '-');

    // append the suffix, if possible
    $ideal_file = $new_file . $suffix;
    if (!file_exists($ideal_file)) {
      rename($new_file, $ideal_file);
      return $ideal_file;
    } else {
      return $new_file;
    }
  }
}