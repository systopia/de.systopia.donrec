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
 * This class will find the right place and downloadable URL for
 * temporary AND/OR permament files.
 */
class CRM_Donrec_Logic_File {

  /**
   * This function will take any file and make it temporarily available
   * for download
   *
   * @param string $path
   *   where to find the file
   * @param string|null $name
   *   end-user name of the file
   * @param bool $deleteSource
   *   if true, the file will be moved to another place rather than copied
   * @param string|null $mimetype
   *   the document's MIME type. Autodetect if null
   *
   * @return string
   *   a string with an URL where to download the file
   */
  public static function createTemporaryFile($path, $name = NULL, $deleteSource = TRUE, $mimetype = NULL) {
    return CRM_Donrec_Page_Tempfile::createFromFile($path, $name, $deleteSource, $mimetype);
  }

  /**
   * This function will take any file and make it permanently
   * available as a CiviCRM File entity.
   *
   * @param string $path          where to find the file
   * @param string $name
   * @param int $contact_id    which contact to connect to
   *
   * @param string|null $mimetype      the document's MIME type. Autodetect if null
   *
   * @param string $description
   *
   * @return array|null
   *   An array containing the created file object, including a generated url
   */
  public static function createPermanentFile($path, $name, $contact_id, $mimetype = NULL, $description = '') {
    $config = CRM_Core_Config::singleton();
    if (!file_exists($path)) {
      return NULL;
    }

    // TODO: check if a file object already exists?

    // move file to a permanent folder
    /** @var string $customFileUploadDir */
    // Since CiviCRM 6.1 this property is type hinted, so this can be reduced to a single line sometime in the future.
    $customFileUploadDir = $config->customFileUploadDir;
    $newPath = $customFileUploadDir . basename($path);
    copy($path, $newPath);

    // find mime type
    if (empty($mimetype)) {
      $mimetype = mime_content_type($newPath);
    }

    // create the file object
    if (empty($description)) {
      $description = $name;
    }
    $file = civicrm_api3('File', 'create', [
      'uri'           => basename($newPath),
      'mime_type'     => $mimetype,
      'description'   => $description,
    ]);

    if (!empty($file['is_error'])) {
      Civi::log()->debug("de.systopia.donrec: couldn't create file object - " . $file['error_message']);
      return NULL;
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
    $reply['url'] = self::getPermanentURL($file['id'], $contact_id);
    $reply['path'] = $newPath;

    return $reply;
  }

  /**
   * Will create a suitable file for writing to
   *
   * @param string $preferredName
   *   The preferred name. There will probably by a suffix appended to it
   *
   * @param string $suffix
   *
   * @return string
   *   A string with a file path
   */
  public static function makeFileName($preferredName, $suffix = '') {
    // generate a uniq temp file
    $new_file = tempnam(sys_get_temp_dir(), $preferredName . '-');

    // append the suffix, if possible
    $ideal_file = $new_file . $suffix;
    if (!file_exists($ideal_file)) {
      rename($new_file, $ideal_file);
      return $ideal_file;
    }
    else {
      return $new_file;
    }
  }

  /**
   * Delete a file
   * @param int $id - civicrm_file.id
   * @return bool
   *   TRUE if file was deleted, FALSE otherwise
   */
  public static function deleteFile($id) {
    // get file-path, but before deleting it, delete the civicrm_file-entry
    $uri = self::getUri($id);
    $path = self::getAbsolutePath($uri);

    // delete civicrm_file and civicrm_entity_file
    $query1 = "
      DELETE FROM `civicrm_entity_file`
      WHERE `file_id` = $id
    ";
    $query2 = "
      DELETE FROM `civicrm_file`
      WHERE `id` = $id
    ";
    $result1 = CRM_Core_DAO::executeQuery($query1);
    $result2 = CRM_Core_DAO::executeQuery($query2);

    // delete file on disc
    $success = unlink($path);
    if (!$success) {
      Civi::log()->debug("Could not delete file: $path. The corresponding civicrm_file has been deleted!");
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get civicrm_file.uri
   * @param int $id - civicrm_file.id
   * @return string|null
   *   civicrm_file.uri or NULL
   */
  public static function getUri($id) {
    $query = "
      SELECT `uri`
      FROM `civicrm_file`
      WHERE `id` = $id
    ";
    $uri = CRM_Core_DAO::singleValueQuery($query);
    if (!$uri) {
      Civi::log()->debug("There is no file with id '$id'.");
    }
    return $uri;
  }

  /**
   * Generate a valid download link
   *
   * @param int $file_id   the file entity ID
   * @param int $contact_id the connected entity's ID (probably contact id)
   *
   * @return string valid link
   */
  public static function getPermanentURL($file_id, $contact_id) {
    try {
      $file = civicrm_api3('File', 'getsingle', ['id' => $file_id]);
      return CRM_Utils_System::url(
        'civicrm/file',
        "reset=1&id={$file_id}&eid={$contact_id}&filename={$file['uri']}&mime-type={$file['mime_type']}"
      );
    }
    catch (Exception $ex) {
      // @ignoreException
      CRM_Core_Session::setStatus(ts('Download failed: ', ['domain' => 'de.systopia.donrec']) . $ex->getMessage());
      return CRM_Utils_System::url('civicrm/dashboard');
    }
  }

  /**
   * Get absolute Path for File
   * @param int|string $fid - either civicrm_file.id or civicrm_file.uri
   * @return string
   */
  public static function getAbsolutePath($fid) {
    $uri = (is_numeric($fid)) ? self::getUri((int) $fid) : $fid;
    $config = CRM_Core_Config::singleton();
    /** @var string $customFileUploadDir */
    // Since CiviCRM 6.1 this property is type hinted, so this can be reduced to a single line sometime in the future.
    $customFileUploadDir = $config->customFileUploadDir;
    $path = $customFileUploadDir . basename($uri);
    return $path;
  }

}
