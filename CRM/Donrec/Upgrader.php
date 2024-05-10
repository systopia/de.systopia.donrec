<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * Collection of upgrade steps.
 */
class CRM_Donrec_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Install hook
   */
  public function install() {
    // Create database tables.
    $this->executeSqlFile('sql/donrec_uninstall.sql', true);
    $this->executeSqlFile('sql/donrec.sql', true);

    // Create default profile.
    $default_profile = new CRM_Donrec_Logic_Profile();
    $default_profile->save();
  }

  /**
   * Uninstall hook
   */
  public function uninstall() {
    // Drop database tables.
    $this->executeSqlFile('sql/donrec_uninstall.sql', true);
  }

  /**
   * Make sure all the data structures are there when the module is enabled
   */
  public function enable() {
    // create/update custom groups
    CRM_Donrec_DataStructure::update();

    // rename the custom fields according to l10.
    // FIXME: this is a workaround: if you do this before, the table name change,
    //         BUT we should not be working with static table names
    CRM_Donrec_DataStructure::translateCustomGroups();
  }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  public function disable() {
    // Empty the snapshot table.
    $query = "
      TRUNCATE `donrec_snapshot`;
    ";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Upgrade to 1.4:
   *  - update to new, prefixed, settings names
   *  - (on 4.6) if no profile exists, create default (from legacy indivudual values)
   *  - (on 4.6) migrate all profiles into a "settings bag"
   *
   * REMARK: using the CRM_Core_BAO_Setting::getItem in order to evaluate the group_name
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0140() {
    $OLD_SETTINGS_GROUP = 'Donation Receipt Profiles';

    // STEP 1: Migrate old general settings to prefixed ones
    $settings_migration = array(
        'default_profile' => 'donrec_default_profile',
        'packet_size'     => 'donrec_packet_size',
        'pdfinfo_path'    => 'donrec_pdfinfo_path',
    );

    foreach ($settings_migration as $old_key => $new_key) {
      $new_value = CRM_Core_BAO_Setting::getItem($OLD_SETTINGS_GROUP, $new_key);
      if ($new_value === NULL) {
        $old_value = CRM_Core_BAO_Setting::getItem($OLD_SETTINGS_GROUP, $old_key);
        if ($old_value !== NULL) {
          CRM_Core_BAO_Setting::setItem($old_value, $OLD_SETTINGS_GROUP, $new_key);
        }
      }
    }

    // Migrate profiles
    //  (only works on 4.6. With 4.7 the group_name was dropped, and we cannot find the profiles any more)
    $existing_profiles = civicrm_api3('Setting', 'getvalue', array('name' => 'donrec_profiles'));
    if (empty($existing_profiles) && version_compare(CRM_Utils_System::version(), '4.6', '<=')) {

      // FIXME: is there a better way than a SQL query?
      $profiles = array();
      $query = CRM_Core_DAO::executeQuery("SELECT name FROM civicrm_setting WHERE group_name = '$OLD_SETTINGS_GROUP'");
      while ($query->fetch()) {
        $profile_data = CRM_Core_BAO_Setting::getItem($OLD_SETTINGS_GROUP, $query->name);
        if (is_array($profile_data)) {
          $profiles[$query->name] = $profile_data;
        } else {
          $this->ctx->log->warn('Profile "{$query->name}" seems to be broken and is lost.');
        }
      }

      // if there is no default profile, create one and copy legacy (pre 1.3) values
      if (empty($profiles['Default'])) {
        $default_profile = CRM_Donrec_Logic_Profile::getProfileByName('Default');
        $profile_data    = $default_profile->getData();

        foreach (array_keys($profile_data) as $field_name) {
          $legacy_value = CRM_Core_BAO_Setting::getItem(CRM_Donrec_Logic_Settings::$SETTINGS_GROUP, $field_name);
          if ($legacy_value !== NULL) {
            $profile_data[$field_name] = $legacy_value;
          }
        }
        $legacy_contribution_types = CRM_Core_BAO_Setting::getItem(CRM_Donrec_Logic_Settings::$SETTINGS_GROUP, 'contribution_types');
        if ($legacy_contribution_types !== NULL && $legacy_contribution_types != 'all') {
          $profile_data['financial_types'] = explode(',', $legacy_contribution_types);
        }

        $profiles['Default'] = $profile_data;
        $this->ctx->log->warn('Created default profile.');
      }

      CRM_Donrec_Logic_Profile::setAllData($profiles);

      $profiles_migrated = count($profiles);
      $this->ctx->log->info('Migrated {$profiles_migrated} profiles.');
    }

    return TRUE;
  }

  /**
   * Upgrade to 1.5:
   *  - forms have changed, so rebuild menu
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0150() {
    CRM_Core_Invoke::rebuildMenuAndCaches();
    return TRUE;
  }

  /**
   * Upgrade to 1.8:
   * - Set default values for contribution fields to unlock for editing.
   *
   * @return bool
   *  TRUE on success
   */
  public function upgrade_0180() {
    // Set legacy behavior as default.
    CRM_Donrec_Logic_Settings::set('donrec_contribution_lock', 'lock_selected');
    CRM_Donrec_Logic_Settings::set('donrec_contribution_lock_fields', array(
      'financial_type_id' => 1,
      'total_amount' => 1,
      'receive_date' => 1,
      'currency' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
    ));

    return TRUE;
  }

  /**
   * Upgrade to 1.8:
   * - Apply reverse logic for locking contribution fields on current
   * configuration.
   *
   * @return bool
   *   TRUE on success
   */
  public function upgrade_0181() {
    // Get the old settings.
    $lock_mode = CRM_Donrec_Logic_Settings::get('donrec_contribution_lock');
    $lock_fields = CRM_Donrec_Logic_Settings::get('donrec_contribution_lock_fields');

    // Translate into new settings.
    $unlock_mode = 'un' . $lock_mode;
    $unlock_fields = array_map(function ($value) {
      return (int)!$value;
    }, $lock_fields);

    // Set the new settings.
    CRM_Donrec_Logic_Settings::set('donrec_contribution_unlock', $unlock_mode);
    CRM_Donrec_Logic_Settings::set('donrec_contribution_unlock_fields', $unlock_fields);

    // Remove the old settings.
    $lock_mode_setting = new CRM_Core_DAO_Setting();
    $lock_mode_setting->name = 'donrec_contribution_lock';
    $lock_mode_setting->delete();

    $lock_mode_setting = new CRM_Core_DAO_Setting();
    $lock_mode_setting->name = 'donrec_contribution_lock_fields';
    $lock_mode_setting->delete();

    return TRUE;
  }

  /**
   * Upgrade to 2.0:
   * - Refactor profile storage
   */
  public function upgrade_0200() {
    /**
     * Migrate profiles to new storage.
     */
    $profiles = civicrm_api3('Setting', 'getvalue', array(
      'name' => 'donrec_profiles',
    ));
    if (is_array($profiles)) {
      // Create `donrec_profile` database table.
      $query = "
      CREATE TABLE IF NOT EXISTS `donrec_profile` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `name` char(64) NOT NULL,
        `data` text,
        `variables` text,
        `template` longtext,
        `template_pdf_format_id` int(10) unsigned,
        `is_default` tinyint(4) DEFAULT 0,
        `is_active` tinyint(4) DEFAULT 1,
        `is_locked` tinyint(4) DEFAULT 0,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1
    ;";
      CRM_Core_DAO::executeQuery($query);

      // Add "profile_id" column to custom data table.
      CRM_Donrec_DataStructure::update();
      $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
      $receipt_fields = CRM_Donrec_DataStructure::getCustomFields('zwb_donation_receipt');

      // Retrieve profiles from settings, injecting default data if not set.
      if (empty($profiles['Default'])) {
        $profiles['Default'] = CRM_Donrec_Logic_Profile::defaultProfileData()['data'];
      }

      // Alter snapshot table structure.
      try {
        $snapshot_query = "
      ALTER TABLE
        `donrec_snapshot`
      ADD COLUMN `profile_id` int(10) unsigned NOT NULL AFTER `snapshot_id`
    ;";
        CRM_Core_DAO::executeQuery($snapshot_query);
      }
      catch (Exception $exception) {
        // Do nothing.
      }

      foreach ($profiles as $profile_name => $profile_data) {
        // Copy template contents and remove template reference from profile data.
        unset($template);
        if (!empty($profile_data['template'])) {
          try {
            $template = civicrm_api3(
              'MessageTemplate',
              'getsingle',
              array(
                'id' => $profile_data['template'],
                'return' => array(
                  'msg_html',
                  'pdf_format_id',
                ),
              )
            );
            unset($profile_data['template']);
          }
          catch (Exception $exception) {
            // Nothing to do here, there is a fallback for $template below.
          }
        }
        if (!isset($template)) {
          $pdf_format = CRM_Core_BAO_PdfFormat::getPdfFormat('is_default', 1);
          $template = array(
            'msg_html' => CRM_Donrec_Logic_Template::getDefaultTemplateHTML(),
            'pdf_format_id' => $pdf_format['id'],
          );
        }

        // Rename profile settings.
        $profile_data['from_email'] = $profile_data['donrec_from_email'];
        unset($profile_data['donrec_from_email']);

        // Copy formerly global settings to profiles.
        $profile_data['email_template'] = Civi::settings()->get('donrec_email_template');
        $profile_data['bcc_email'] = Civi::settings()->get('donrec_bcc_email');
        $profile_data['return_path_email'] = Civi::settings()->get('donrec_return_path_email');
        $profile_data['watermark_preset'] = Civi::settings()->get('donrec_watermark_preset');
        $profile_data['language'] = Civi::settings()->get('donrec_language');
        $profile_data['contribution_unlock_mode'] = Civi::settings()->get('donrec_contribution_unlock');
        $profile_data['contribution_unlock_fields'] = Civi::settings()->get('donrec_contribution_unlock_fields');

        // Set lock status for profiles that have already been used for issueing receipts.
        $usage_query = "
        SELECT
          COUNT(`id`)
        FROM
          {$receipt_table}
        WHERE
          {$receipt_fields['profile']} = %1
        ;";
        $usage_query_params = array(
          1 => array($profile_name, 'String'),
        );
        $is_locked = (int)(bool) CRM_Core_DAO::singleValueQuery($usage_query, $usage_query_params);

        $query = "
        INSERT INTO
          `donrec_profile`
        SET
           `name` = %1,
           `data` = %2,
           `is_default` = %3,
           `is_locked` = %4,
           `template` = %5,
           `variables` = %6
        ";
        $query_params = array(
          1 => array($profile_name, 'String'),
          2 => array(serialize($profile_data), 'String'),
          3 => array((int)($profile_name == 'Default'), 'Int'),
          4 => array($is_locked, 'Int'),
          5 => array($template['msg_html'], 'String'),
          6 => array(serialize(array()), 'String'),
        );
        if (isset($template['pdf_format_id'])) {
          $query .= "
        ,`template_pdf_format_id` = %7
        ";
          $query_params[7] = array($template['pdf_format_id'], 'Int');
        }

        $query .= ";";

        CRM_Core_DAO::executeQuery($query, $query_params);

        // Set "profile_id" custom fields.
        $profile_query = "
        SELECT
          *
        FROM
          `donrec_profile`
        WHERE
          `name` = %1
      ;";
        $donrec_profile_dao = CRM_Core_DAO::executeQuery(
          $profile_query,
          array(
            1 => array($profile_name, 'String'),
          )
        );
        $donrec_profile_dao->fetch();

        $custom_values_query = "
        UPDATE
          {$receipt_table}
        SET
          `{$receipt_fields['profile_id']}` = %1
        WHERE
          `{$receipt_fields['profile']}` = %2
      ;";
        CRM_Core_DAO::executeQuery(
          $custom_values_query,
          array(
            1 => array($donrec_profile_dao->id, 'Int'),
            2 => array($donrec_profile_dao->name, 'String'),
          )
        );

        // Update snapshot table with profile IDs.
        try {
          $snapshot_query = "
        UPDATE
          `donrec_snapshot`
        SET
          `profile_id` = %1
        WHERE
          `profile` = %2
      ;";
          CRM_Core_DAO::executeQuery(
            $snapshot_query,
            array(
              1 => array($donrec_profile_dao->id, 'Int'),
              2 => array($donrec_profile_dao->name, 'String'),
            )
          );
        }
        catch (Exception $exception) {
          // Do nothing.
        }
      }

      // Alter snapshot table structure.
      try {
        $snapshot_query = "
      ALTER TABLE
        `donrec_snapshot`
      DROP COLUMN `profile`
    ;";
        CRM_Core_DAO::executeQuery($snapshot_query);
      }
      catch (Exception $exception) {
        // Do nothing.
      }


      // Remove (revert) old settings entries.
      Civi::settings()->revert('donrec_profiles');
      Civi::settings()->revert('donrec_email_template');
      Civi::settings()->revert('donrec_bcc_email');
      Civi::settings()->revert('donrec_return_path_email');
      Civi::settings()->revert('donrec_watermark_preset');
      Civi::settings()->revert('donrec_language');
      Civi::settings()->revert('donrec_contribution_unlock');
      Civi::settings()->revert('donrec_contribution_unlock_fields');

      CRM_Core_Invoke::rebuildMenuAndCaches();
    }

    return TRUE;
  }

  /**
   * Upgrade to 2.0:
   * - Delete erroneously copied ReceiptItem custom value entries for
   *   contributions created by the Contribution.repeattransaction API action.
   * @see donrec_civicrm_post().
   * @link https://github.com/civicrm/civicrm-core/pull/17454
   */
  public function upgrade_0201() {
    $receipt_item_table = CRM_Donrec_DataStructure::getTableName(
      'zwb_donation_receipt_item'
    );
    $receipt_item_fields = CRM_Donrec_DataStructure::getCustomFields(
      'zwb_donation_receipt_item'
    );
    $ids_query = "
      SELECT
        item_delete.id AS `id`,
        item_delete.{$receipt_item_fields['contribution_hash']} AS `hash`
      FROM
        `{$receipt_item_table}` item_delete
      WHERE
        IFNULL(item_delete.{$receipt_item_fields['contribution_hash']}, '') != ''
        AND item_delete.{$receipt_item_fields['contribution_hash']} IN(
          SELECT
            item_duplicate.{$receipt_item_fields['contribution_hash']}
          FROM
            `{$receipt_item_table}` item_duplicate
          GROUP BY
            item_duplicate.{$receipt_item_fields['contribution_hash']}
          HAVING
            COUNT(*) > 1
        )
        AND item_delete.id != (
          SELECT
            MIN(item_keep.id)
          FROM
            `{$receipt_item_table}` item_keep
          WHERE
            item_keep.{$receipt_item_fields['contribution_hash']} = item_delete.{$receipt_item_fields['contribution_hash']}
        )
    ;";
    $ids = CRM_Core_DAO::executeQuery($ids_query)->fetchMap('id', 'hash');
    if (!empty($ids)) {
      $ids_sql = implode(',', array_keys($ids));

      $delete_query = "
      DELETE
      FROM
        `{$receipt_item_table}`
      WHERE
        `id` IN ({$ids_sql})
      ;";
      CRM_Core_DAO::executeQuery($delete_query);
    }

    return TRUE;
  }

  /**
   * - Flush cache for registering new settings for CiviOffice integration.
   */
  public function upgrade_0210() {
    civicrm_api3('System', 'flush');
    return TRUE;
  }

  /**
   * Upgrade to 2.1:
   * - Added functionality to create donation recepeits for contributions with more than one
   *   line item.
   * @link https://github.com/systopia/de.systopia.donrec/issues/136
   */
  public function upgrade_0220() {
    $fieldExistsDao = \CRM_Core_DAO::executeQuery("SHOW COLUMNS FROM `donrec_snapshot` LIKE 'line_item_id';");
    if (!$fieldExistsDao->N) {
      \CRM_Core_DAO::executeQuery("ALTER TABLE `donrec_snapshot` ADD `line_item_id` INT UNSIGNED NULL DEFAULT NULL AFTER `contribution_id`, ADD INDEX `line_item_id` (`line_item_id`);");
    }
    CRM_Donrec_Logic_Settings::set('donrec_enable_line_item', 0);
    return TRUE;
  }

}
