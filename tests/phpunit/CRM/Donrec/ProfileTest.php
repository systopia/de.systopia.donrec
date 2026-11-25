<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2026 SYSTOPIA                       |
| Author: SYSTOPIA GmbH                                  |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

/**
 * Profile and Profile Fallback Test Suite
 *
 * @covers \CRM_Donrec_Logic_Profile
 * @covers \CRM_Donrec_Logic_ReceiptTokens
 * @group headless
 */
class CRM_Donrec_ProfileTest extends CRM_Donrec_BaseTestCase {

  /**
   * @var list<string> */
  private array $tablesToTruncate = ['donrec_profile', 'donrec_snapshot'];

  protected function setUp(): void {
    parent::setUp();
    $this->quickCleanup($this->tablesToTruncate);
    $defaultProfile = new CRM_Donrec_Logic_Profile();
    $defaultProfile->setDefault(TRUE);
  }

  public function testGetProfileFallback(): void {
    $profileNull = CRM_Donrec_Logic_Profile::getProfile(NULL);
    static::assertSame('Default', $profileNull->getName());

    $profileZero = CRM_Donrec_Logic_Profile::getProfile(0);
    static::assertSame('Default', $profileZero->getName());

    $profileEmptyString = CRM_Donrec_Logic_Profile::getProfile('');
    static::assertSame('Default', $profileEmptyString->getName());

    $defaultProfile = CRM_Donrec_Logic_Profile::getDefaultProfile();
    $profileById = CRM_Donrec_Logic_Profile::getProfile($defaultProfile->getId());
    static::assertSame($defaultProfile->getId(), $profileById->getId());
  }

  public function testAddDynamicTokensWithNullProfile(): void {
    $values = [
      'profile_id' => NULL,
      'status' => 'DRAFT',
      'total_amount' => '100.00',
      'currency' => 'EUR',
      'contributor' => [
        'id' => 1,
        'display_name' => 'John Doe',
      ],
      'lines' => [],
    ];

    CRM_Donrec_Logic_ReceiptTokens::addDynamicTokens($values);

    static::assertArrayHasKey('watermark', $values);
    static::assertSame('DRAFT', $values['watermark']);
    static::assertArrayHasKey('total', $values);
    static::assertSame('100.00', $values['total']);
  }

  public function testAddDynamicTokensWithExplicitProfile(): void {
    $profile = CRM_Donrec_Logic_Profile::getDefaultProfile();
    $profile->setDataAttribute('copy_text', 'CUSTOM_COPY');

    $values = [
      'profile_id' => 999999,
      'status' => 'COPY',
      'total_amount' => '50.00',
      'currency' => 'EUR',
      'contributor' => [
        'id' => 1,
        'display_name' => 'Jane Doe',
      ],
      'lines' => [],
    ];

    CRM_Donrec_Logic_ReceiptTokens::addDynamicTokens($values, $profile);

    static::assertArrayHasKey('watermark', $values);
    static::assertSame('CUSTOM_COPY', $values['watermark']);
  }

  /**
   * Tests upgrade_0222 upgrader method on legacy data with NULL or 0 profile_id.
   */
  public function testUpgrade0222(): void {
    $defaultId = CRM_Donrec_Logic_Profile::getDefaultProfile()->getId();

    $receiptTable = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $receiptFields = CRM_Donrec_DataStructure::getCustomFields('zwb_donation_receipt');
    $profileIdField = $receiptFields['profile_id'];

    // Legacy receipt with profile_id = NULL
    CRM_Core_DAO::executeQuery("
      INSERT INTO `$receiptTable` (`entity_id`, `$profileIdField`)
      VALUES (1, NULL)
    ");
    $legacyReceiptId1 = (int) CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');

    // Legacy receipt with profile_id = 0
    CRM_Core_DAO::executeQuery("
      INSERT INTO `$receiptTable` (`entity_id`, `$profileIdField`)
      VALUES (1, 0)
    ");
    $legacyReceiptId2 = (int) CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');

    $result = (new CRM_Donrec_Upgrader())->upgrade_0222();

    static::assertTrue($result);

    $updatedProfileId1 = (int) CRM_Core_DAO::singleValueQuery("
      SELECT `$profileIdField` FROM `$receiptTable` WHERE `id` = %1
    ", [1 => [$legacyReceiptId1, 'Integer']]);

    $updatedProfileId2 = (int) CRM_Core_DAO::singleValueQuery("
      SELECT `$profileIdField` FROM `$receiptTable` WHERE `id` = %1
    ", [1 => [$legacyReceiptId2, 'Integer']]);

    static::assertSame($defaultId, $updatedProfileId1);
    static::assertSame($defaultId, $updatedProfileId2);

    // Clean up inserted receipts
    CRM_Core_DAO::executeQuery("DELETE FROM `$receiptTable` WHERE `id` IN ($legacyReceiptId1, $legacyReceiptId2)");
  }

}
