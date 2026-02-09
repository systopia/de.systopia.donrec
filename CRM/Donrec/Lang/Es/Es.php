<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: Luciano Spiegel                                |
| http://www.ixiam.com/                                  |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

/**
 * This class holds Spanish language helper functions
 */
class CRM_Donrec_Lang_Es_Es extends CRM_Donrec_Lang {
  private static array $UNIDADES = [
    '',
    'UN ',
    'DOS ',
    'TRES ',
    'CUATRO ',
    'CINCO ',
    'SEIS ',
    'SIETE ',
    'OCHO ',
    'NUEVE ',
    'DIEZ ',
    'ONCE ',
    'DOCE ',
    'TRECE ',
    'CATORCE ',
    'QUINCE ',
    'DIECISEIS ',
    'DIECISIETE ',
    'DIECIOCHO ',
    'DIECINUEVE ',
    'VEINTE ',
  ];

  private static array $DECENAS = [
    'VEINTI',
    'TREINTA ',
    'CUARENTA ',
    'CINCUENTA ',
    'SESENTA ',
    'SETENTA ',
    'OCHENTA ',
    'NOVENTA ',
    'CIEN ',
  ];

  private static array $CENTENAS = [
    'CIENTO ',
    'DOSCIENTOS ',
    'TRESCIENTOS ',
    'CUATROCIENTOS ',
    'QUINIENTOS ',
    'SEISCIENTOS ',
    'SETECIENTOS ',
    'OCHOCIENTOS ',
    'NOVECIENTOS ',
  ];

  private static array $MONEDAS = [
    [
      'country' => 'Colombia',
      'currency' => 'COP',
      'singular' => 'PESO COLOMBIANO',
      'plural' => 'PESOS COLOMBIANOS',
      'symbol' => '$',
    ],
    [
      'country' => 'Estados Unidos',
      'currency' => 'USD',
      'singular' => 'DÓLAR',
      'plural' => 'DÓLARES',
      'symbol' => 'US$',
    ],
    [
      'country' => 'Europa',
      'currency' => 'EUR',
      'singular' => 'EURO',
      'plural' => 'EUROS',
      'symbol' => '€',
    ],
    [
      'country' => 'México',
      'currency' => 'MXN',
      'singular' => 'PESO MEXICANO',
      'plural' => 'PESOS MEXICANOS',
      'symbol' => '$',
    ],
    ['country' => 'Perú', 'currency' => 'PEN', 'singular' => 'NUEVO SOL', 'plural' => 'NUEVOS SOLES', 'symbol' => 'S/'],
    ['country' => 'Reino Unido', 'currency' => 'GBP', 'singular' => 'LIBRA', 'plural' => 'LIBRAS', 'symbol' => '£'],
    ['country' => 'Argentina', 'currency' => 'ARS', 'singular' => 'PESO', 'plural' => 'PESOS', 'symbol' => '$'],
  ];

  private static string $separator = ',';
  private static string $decimal_mark = '.';
  private static string $glue = ' CON ';

  /**
   * Get the (localised) name of the language
   *
   * @return string name of the language
   */
  public function getName() {
    return E::ts('Spanish (Spain)');
  }

  /**
   * @inheritDoc
   */
  public function amount2words($amount, $currency, $params = []) {
    return self::toWords($amount);
  }

  /**
   * @inheritDoc
   */
  public function currency2word($currency, $quantity) {
    foreach (self::$MONEDAS as $moneda) {
      if ($moneda['currency'] == $currency) {
        if ($quantity > 1) {
          return $moneda['singular'];
        }
        else {
          return $moneda['plural'];
        }
      }
    }

    // fallback: return currency symbol
    return parent::currency2word($currency, $quantity);
  }

  /**
   * Evalua si el número contiene separadores o decimales
   * formatea y ejecuta la función conversora
   * @param string|int|float $number número a convertir
   * @param string|null $miMoneda clave de la moneda
   * @return string completo
   */
  public static function toWords($number, $miMoneda = NULL) {
    if (strpos((string) $number, self::$decimal_mark) === FALSE) {
      $convertedNumber = [
        self::convertNumber($number, $miMoneda, 'entero'),
      ];
    }
    else {
      $number = explode(self::$decimal_mark, str_replace(self::$separator, '', trim($number)));

      $convertedNumber = [
        self::convertNumber($number[0], $miMoneda, 'entero'),
        self::convertNumber($number[1], $miMoneda, 'decimal'),
      ];
    }
    return implode(self::$glue, array_filter($convertedNumber));
  }

  /**
   * Convierte número a letras
   *
   * @param string|int|float $number
   * @param string|null $miMoneda
   * @param string $type tipo de dígito (entero/decimal)
   *
   * @return string|FALSE $converted string convertido
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
   */
  private static function convertNumber($number, ?string $miMoneda, $type) {
  // phpcs:enable
    $converted = '';
    if ($miMoneda !== NULL) {
      try {
        $moneda = array_filter(self::$MONEDAS, function($m) use ($miMoneda) {
          return ($m['currency'] == $miMoneda);
        });

        $moneda = array_values($moneda);

        if (count($moneda) <= 0) {
          throw new Exception('Tipo de moneda inválido');
        }
        ($number < 2 ? $moneda = $moneda[0]['singular'] : $moneda = $moneda[0]['plural']);
      }
      catch (Exception $e) {
        // @ignoreException
        echo $e->getMessage();
        return FALSE;
      }
    }
    else {
      $moneda = '';
    }

    if (($number < 0) || ($number > 999999999)) {
      return FALSE;
    }

    $numberStr = (string) $number;
    $numberStrFill = str_pad($numberStr, 9, '0', STR_PAD_LEFT);
    $millones = substr($numberStrFill, 0, 3);
    $miles = substr($numberStrFill, 3, 3);
    $cientos = substr($numberStrFill, 6);

    if (intval($millones) > 0) {
      if ($millones == '001') {
        $converted .= 'UN MILLON ';
      }
      elseif (intval($millones) > 0) {
        $converted .= sprintf('%sMILLONES ', self::convertGroup($millones));
      }
    }

    if (intval($miles) > 0) {
      if ($miles == '001') {
        $converted .= 'MIL ';
      }
      elseif (intval($miles) > 0) {
        $converted .= sprintf('%sMIL ', self::convertGroup($miles));
      }
    }

    if (intval($cientos) > 0) {
      if ($cientos == '001') {
        $converted .= 'UN ';
      }
      elseif (intval($cientos) > 0) {
        $converted .= sprintf('%s ', self::convertGroup($cientos));
      }
    }

    $converted .= $moneda;

    return $converted;
  }

  /**
   * Define el tipo de representación decimal (centenas/millares/millones)
   * @param $n
   * @return string $output
   */
  private static function convertGroup($n) {

    $output = '';

    if ($n == '100') {
      $output = 'CIEN ';
    }
    elseif ($n[0] !== '0') {
      $output = self::$CENTENAS[$n[0] - 1];
    }

    $k = intval(substr($n, 1));

    if ($k <= 20) {
      $output .= self::$UNIDADES[$k];
    }
    else {
      if (($k > 30) && ($n[2] !== '0')) {
        $output .= sprintf('%sY %s', self::$DECENAS[intval($n[1]) - 2], self::$UNIDADES[intval($n[2])]);
      }
      else {
        $output .= sprintf('%s%s', self::$DECENAS[intval($n[1]) - 2], self::$UNIDADES[intval($n[2])]);
      }
    }

    return $output;
  }

}
