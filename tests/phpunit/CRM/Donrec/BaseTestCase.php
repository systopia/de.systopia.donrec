<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use Civi\Test;
use Civi\Test\Api3TestTrait;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 *
 * phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
 */
abstract class CRM_Donrec_BaseTestCase extends TestCase implements HeadlessInterface, TransactionalInterface {

  use Api3TestTrait;
  use \Civi\Test\GenericAssertionsTrait;
  use \Civi\Test\DbTestTrait;
  use \Civi\Test\ContactTestTrait;
  use \Civi\Test\MailingTestTrait;
  use \Civi\Test\LocaleTestTrait;

  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->install('civi_contribute')
      ->installMe(__DIR__)
      ->apply();
  }

  // ############################################################################
  //                              Helper functions
  // ############################################################################

  /**
   * creates a varible amount of contributions
   *
   * @param int $count
   *
   * @return array with contribution ids
   * @author endres -at- systopia.de
   *         bochan -at- systopia.de
   */
  public function generateContributions($count = 2) {
    $contribution_status_pending = (int) CRM_Donrec_CustomData::getOptionValue(
      'contribution_status',
      'Pending',
      'name'
    );
    static::assertNotEmpty($contribution_status_pending, "Could not find the 'Pending' contribution status.");

    $create_contribution = [
      'contact_id'              => $this->individualCreate(),
      'financial_type_id'       => 1,
      'currency'                => 'EUR',
      'contribution_status_id'  => $contribution_status_pending,
      'is_test'                 => 0,
      'id'                      => NULL,
    ];

    $create_contribution['payment_instrument_id'] = 1;
    $result = [];
    for ($c = 0; $c < $count; $c++) {
      $create_contribution['total_amount'] = number_format((float) rand(1, 1000), 2, '.', '');
      $create_contribution['receive_date'] = date('YmdHis');
      $contribution = $this->callAPISuccess('Contribution', 'create', $create_contribution);
      $result[] = $contribution['id'];
    }

    return $result;
  }

  /**
   * Quick clean by emptying tables created for the test.
   *
   * @param array $tablesToTruncate
   * @param bool $dropCustomValueTables
   */
  public function quickCleanup(array $tablesToTruncate, $dropCustomValueTables = FALSE): void {
    CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
    foreach ($tablesToTruncate as $table) {
      $sql = "TRUNCATE TABLE $table";
      CRM_Core_DAO::executeQuery($sql);
    }
    CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
  }

}
