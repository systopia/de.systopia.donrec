<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: Luciano Spiegel                                |
| http://www.ixiam.com/                                  |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

/**
 * This class holds French language helper functions
 */
class CRM_Donrec_Lang_Fr_Fr extends CRM_Donrec_Lang {

  /**
   * Get the (localised) name of the language
   *
   * @return string
   */
  public function getName() {
    return E::ts('French (France)');
  }

  /**
   * Render a full text expressing the amount in the given currency
   *
   * @param string|int|float $amount
   * @param string $currency
   * @param array<string, mixed> $params
   * @return string
   */
  public function amount2words($amount, $currency, $params = []) {
    return (string) self::amountToFrenchWords($amount, $currency, $params);
  }

  /**
   * Convert an amount into French words for EUR receipts.
   *
   * Examples:
   *   1       => un euro
   *   1.01    => un euro et un centime
   *   21      => vingt-et-un euros
   *   1000000 => un million d'euros
   *
   * @param mixed $amount
   * @param string $currency
   * @param array<string, mixed> $params
   * @return string|false
   */
  public static function amountToFrenchWords($amount, $currency = 'EUR', $params = []) {
    // Donrec currently supports EUR only. Do not silently label an amount in
    // another currency as euros.
    if (strtoupper(trim((string) $currency)) !== 'EUR') {
      return FALSE;
    }

    if ($amount === NULL || $amount === '') {
      return FALSE;
    }

    $normalized = self::normalizeNumericInput($amount);
    if ($normalized === FALSE) {
      return FALSE;
    }

    if (!class_exists('NumberFormatter')) {
      return FALSE;
    }

    [$euros, $cents] = self::splitRoundedAmount((float) $normalized);
    $formatter = new NumberFormatter('fr_FR', NumberFormatter::SPELLOUT);
    $words = self::formatAmountParts($formatter, $euros, $cents);
    if ($words === FALSE) {
      return FALSE;
    }

    return self::buildAmountLabel($euros, $cents, $words['euros'], $words['cents']);
  }

  /**
   * Normalize a raw numeric input.
   *
   * Accepts:
   *   1234.56
   *   1234,56
   *   1 234,56
   *   1 234.56
   *
   * @return string|false
   */
  private static function normalizeNumericInput(mixed $amount): string|bool {
    if (!is_scalar($amount) && $amount !== NULL && !($amount instanceof Stringable)) {
      return FALSE;
    }

    $value = trim((string) $amount);

    if ($value === '') {
      return FALSE;
    }

    // Remove normal spaces and non-breaking spaces
    $value = str_replace(["\xc2\xa0", ' '], '', $value);

    // If both comma and dot exist, assume the last separator is decimal
    $hasComma = strpos($value, ',') !== FALSE;
    $hasDot = strpos($value, '.') !== FALSE;

    if ($hasComma && $hasDot) {
      $lastComma = strrpos($value, ',');
      $lastDot = strrpos($value, '.');

      if ($lastComma > $lastDot) {
        // 1.234,56 => 1234.56
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
      }
      else {
        // 1,234.56 => 1234.56
        $value = str_replace(',', '', $value);
      }
    }
    elseif ($hasComma) {
      $value = str_replace(',', '.', $value);
    }

    if (!is_numeric($value)) {
      return FALSE;
    }

    return $value;
  }

  /**
   * Normalize formatter output for stable receipt rendering.
   *
   * @param string $text
   * @return string
   */
  private static function normalizeFrenchNumberWords(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = trim($text);

    // Normalize whitespace
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    // Normalize hyphens
    $text = str_replace('–', '-', $text);
    $text = str_replace('—', '-', $text);

    return $text;
  }

  /**
   * Split a rounded numeric amount into euros and cents.
   *
   * @param float $amount
   * @return array{0: int, 1: int}
   */
  private static function splitRoundedAmount(float $amount): array {
    $value = round($amount, 2);
    $euros = (int) floor($value);
    $cents = (int) round(($value - $euros) * 100);

    // Guard against float rounding oddities like 1.999999 => 2.00
    if ($cents === 100) {
      return [$euros + 1, 0];
    }

    return [$euros, $cents];
  }

  /**
   * Format euros and cents using NumberFormatter.
   *
   * @param \NumberFormatter $formatter
   * @param int $euros
   * @param int $cents
   * @return array{euros: string, cents: string}|false
   */
  private static function formatAmountParts(NumberFormatter $formatter, int $euros, int $cents): array|bool {
    $euroFormatted = $formatter->format($euros);
    $centFormatted = $formatter->format($cents);
    if ($euroFormatted === FALSE || $centFormatted === FALSE) {
      return FALSE;
    }

    return [
      'euros' => self::normalizeFrenchNumberWords($euroFormatted),
      'cents' => self::normalizeFrenchNumberWords($centFormatted),
    ];
  }

  /**
   * Build the final amount label.
   *
   * @param int $euros
   * @param int $cents
   * @param string $euroWords
   * @param string $centWords
   * @return string
   */
  private static function buildAmountLabel(int $euros, int $cents, string $euroWords, string $centWords): string {
    $parts = [self::buildEuroLabel($euros, $euroWords)];

    if ($cents > 0) {
      $parts[] = $centWords . ' ' . ($cents > 1 ? 'centimes' : 'centime');
    }

    return implode(' et ', $parts);
  }

  /**
   * Build the euro part of the final amount label.
   *
   * @param int $euros
   * @param string $euroWords
   * @return string
   */
  private static function buildEuroLabel(int $euros, string $euroWords): string {
    if ($euros === 0) {
      return 'zéro euro';
    }

    $needsDe = preg_match('/\b(million|millions|milliard|milliards|billion|billions)\b/u', $euroWords) === 1;
    if ($needsDe) {
      return $euroWords . " d'euros";
    }

    return $euroWords . ' ' . ($euros > 1 ? 'euros' : 'euro');
  }

}
