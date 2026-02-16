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
 * Collection of upgrade steps.
 */
class CRM_Donrec_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Install hook
   */
  public function install(): void {
    // Create database tables.
    $this->executeSqlFile('sql/donrec_uninstall.sql');
    $this->executeSqlFile('sql/donrec.sql');

    // Create default profile.
    $default_profile = new CRM_Donrec_Logic_Profile();
    $default_profile->save();
  }

  /**
   * Uninstall hook
   */
  public function uninstall(): void {
    // Drop database tables.
    $this->executeSqlFile('sql/donrec_uninstall.sql');
  }

  /**
   * Make sure all the data structures are there when the module is enabled
   */
  public function enable(): void {
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
  public function disable(): void {
    // Empty the snapshot table.
    $query = '
      TRUNCATE `donrec_snapshot`;
    ';
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
  public function upgrade_0140(): bool {
    $OLD_SETTINGS_GROUP = 'Donation Receipt Profiles';

    // STEP 1: Migrate old general settings to prefixed ones
    $settings_migration = [
      'default_profile' => 'donrec_default_profile',
      'packet_size'     => 'donrec_packet_size',
      'pdfinfo_path'    => 'donrec_pdfinfo_path',
    ];

    foreach ($settings_migration as $old_key => $new_key) {
      $new_value = CRM_Core_BAO_Setting::getItem($OLD_SETTINGS_GROUP, $new_key);
      if ($new_value === NULL) {
        $old_value = CRM_Core_BAO_Setting::getItem($OLD_SETTINGS_GROUP, $old_key);
        if ($old_value !== NULL) {
          \Civi::settings()->set($new_key, $old_value);
        }
      }
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
  public function upgrade_0150(): bool {
    if (version_compare(CRM_Utils_System::version(), '6.1', '>=')) {
      // @phpstan-ignore argument.type
      Civi::rebuild(['menu' => TRUE, 'router' => TRUE, 'navigation' => TRUE, 'system' => TRUE])->execute();
    }
    else {
      // @phpstan-ignore-next-line
      CRM_Core_Invoke::rebuildMenuAndCaches();
    }

    return TRUE;
  }

  /**
   * Upgrade to 1.8:
   * - Set default values for contribution fields to unlock for editing.
   *
   * @return bool
   *   TRUE on success
   */
  public function upgrade_0180(): bool {
    // Set legacy behavior as default.
    CRM_Donrec_Logic_Settings::set('donrec_contribution_lock', 'lock_selected');
    CRM_Donrec_Logic_Settings::set('donrec_contribution_lock_fields', [
      'financial_type_id' => 1,
      'total_amount' => 1,
      'receive_date' => 1,
      'currency' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
    ]);

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
  public function upgrade_0181(): bool {
    // Get the old settings.
    $lock_mode = CRM_Donrec_Logic_Settings::get('donrec_contribution_lock');
    $lock_fields = CRM_Donrec_Logic_Settings::get('donrec_contribution_lock_fields');

    // Translate into new settings.
    $unlock_mode = 'un' . $lock_mode;
    $unlock_fields = array_map(function ($value) {
      return (int) !$value;
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
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
   */
  public function upgrade_0200(): bool {
  // phpcs:enable
    /**
     * Migrate profiles to new storage.
     */
    $profiles = civicrm_api3('Setting', 'getvalue', [
      'name' => 'donrec_profiles',
    ]);
    if (is_array($profiles)) {
      // Create `donrec_profile` database table.
      $query = '
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
    ;';
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
        $snapshot_query = '
      ALTER TABLE
        `donrec_snapshot`
      ADD COLUMN `profile_id` int(10) unsigned NOT NULL AFTER `snapshot_id`
    ;';
        CRM_Core_DAO::executeQuery($snapshot_query);
      }
      catch (Exception $exception) {
        // @ignoreException
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
              [
                'id' => $profile_data['template'],
                'return' => [
                  'msg_html',
                  'pdf_format_id',
                ],
              ]
            );
            unset($profile_data['template']);
          }
          catch (Exception $exception) {
            // @ignoreException
            // Nothing to do here, there is a fallback for $template below.
          }
        }
        if (!isset($template)) {
          $pdf_format = CRM_Core_BAO_PdfFormat::getPdfFormat('is_default', 1);
          $template = [
            'msg_html' => CRM_Donrec_Logic_Template::getDefaultTemplateHTML(),
            'pdf_format_id' => $pdf_format['id'],
          ];
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
        $usage_query_params = [
          1 => [$profile_name, 'String'],
        ];
        $is_locked = (int) (bool) CRM_Core_DAO::singleValueQuery($usage_query, $usage_query_params);

        $query = '
        INSERT INTO
          `donrec_profile`
        SET
           `name` = %1,
           `data` = %2,
           `is_default` = %3,
           `is_locked` = %4,
           `template` = %5,
           `variables` = %6
        ';
        $query_params = [
          1 => [$profile_name, 'String'],
          2 => [serialize($profile_data), 'String'],
          3 => [(int) ($profile_name == 'Default'), 'Int'],
          4 => [$is_locked, 'Int'],
          5 => [$template['msg_html'], 'String'],
          6 => [serialize([]), 'String'],
        ];
        if (isset($template['pdf_format_id'])) {
          $query .= '
        ,`template_pdf_format_id` = %7
        ';
          $query_params[7] = [$template['pdf_format_id'], 'Int'];
        }

        $query .= ';';

        CRM_Core_DAO::executeQuery($query, $query_params);

        // Set "profile_id" custom fields.
        $profile_query = '
        SELECT
          *
        FROM
          `donrec_profile`
        WHERE
          `name` = %1
      ;';
        /** @var \CRM_Core_DAO $donrec_profile_dao */
        $donrec_profile_dao = CRM_Core_DAO::executeQuery(
          $profile_query,
          [
            1 => [$profile_name, 'String'],
          ]
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
          [
            1 => [$donrec_profile_dao->id, 'Int'],
            2 => [$donrec_profile_dao->name, 'String'],
          ]
        );

        // Update snapshot table with profile IDs.
        try {
          $snapshot_query = '
        UPDATE
          `donrec_snapshot`
        SET
          `profile_id` = %1
        WHERE
          `profile` = %2
      ;';
          CRM_Core_DAO::executeQuery(
            $snapshot_query,
            [
              1 => [$donrec_profile_dao->id, 'Int'],
              2 => [$donrec_profile_dao->name, 'String'],
            ]
          );
        }
        catch (Exception $exception) {
          // @ignoreException
          // Do nothing.
        }
      }

      // Alter snapshot table structure.
      try {
        $snapshot_query = '
      ALTER TABLE
        `donrec_snapshot`
      DROP COLUMN `profile`
    ;';
        CRM_Core_DAO::executeQuery($snapshot_query);
      }
      catch (Exception $exception) {
        // @ignoreException
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

      if (version_compare(CRM_Utils_System::version(), '6.1', '>=')) {
        // @phpstan-ignore argument.type
        Civi::rebuild(['menu' => TRUE, 'router' => TRUE, 'navigation' => TRUE, 'system' => TRUE])->execute();
      }
      else {
        // @phpstan-ignore-next-line
        CRM_Core_Invoke::rebuildMenuAndCaches();
      }
    }

    return TRUE;
  }

  /**
   * Upgrade to 2.0:
   * - Delete erroneously copied ReceiptItem custom value entries for
   *   contributions created by the Contribution.repeattransaction API action.
   * @see donrec_civicrm_post()
   * @link https://github.com/civicrm/civicrm-core/pull/17454
   */
  public function upgrade_0201(): bool {
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
            item_keep.{$receipt_item_fields['contribution_hash']} =
              item_delete.{$receipt_item_fields['contribution_hash']}
        )
    ;";
    /** @var \CRM_Core_DAO $idsQuery */
    $idsQuery = CRM_Core_DAO::executeQuery($ids_query);
    $ids = $idsQuery->fetchMap('id', 'hash');
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
  public function upgrade_0210(): bool {
    civicrm_api3('System', 'flush');
    return TRUE;
  }

  /**
   * Upgrade to 2.1:
   * - Added functionality to create donation recepeits for contributions with more than one
   *   line item.
   * @link https://github.com/systopia/de.systopia.donrec/issues/136
   */
  public function upgrade_0220(): bool {
    /** @var \CRM_Core_DAO $fieldExistsDao */
    $fieldExistsDao = \CRM_Core_DAO::executeQuery("SHOW COLUMNS FROM `donrec_snapshot` LIKE 'line_item_id';");
    if (!$fieldExistsDao->N) {
      \CRM_Core_DAO::executeQuery(
        'ALTER TABLE `donrec_snapshot`
                ADD `line_item_id` INT UNSIGNED NULL DEFAULT NULL AFTER `contribution_id`,
                ADD INDEX `line_item_id` (`line_item_id`);'
      );
    }
    CRM_Donrec_Logic_Settings::set('donrec_enable_line_item', 0);
    return TRUE;
  }

  /**
   * Change the primary key of the table. See https://github.com/systopia/de.systopia.donrec/issues/145
   *
   * @return bool
   */
  public function upgrade_0221(): bool {
    \CRM_Core_DAO::executeQuery('
      ALTER TABLE `donrec_snapshot`
        DROP PRIMARY KEY,
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE `snapshot_contrib_line_item` (`snapshot_id`, `contribution_id`, `line_item_id`);
    ');
    return TRUE;
  }

}
