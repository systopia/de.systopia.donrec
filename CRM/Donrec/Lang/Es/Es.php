<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: Luciano Spiegel                                |
| http://www.ixiam.com/                                  |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This class holds Spanish language helper functions
 */
class CRM_Donrec_Lang_Es_Es extends  CRM_Donrec_Lang {
  static private $UNIDADES = array(
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
    'VEINTE '
  );

  static private $DECENAS = array(
    'VEINTI',
    'TREINTA ',
    'CUARENTA ',
    'CINCUENTA ',
    'SESENTA ',
    'SETENTA ',
    'OCHENTA ',
    'NOVENTA ',
    'CIEN '
  );

  static private $CENTENAS = array(
    'CIENTO ',
    'DOSCIENTOS ',
    'TRESCIENTOS ',
    'CUATROCIENTOS ',
    'QUINIENTOS ',
    'SEISCIENTOS ',
    'SETECIENTOS ',
    'OCHOCIENTOS ',
    'NOVECIENTOS '
  );

  static private $MONEDAS = array(
    array('country' => 'Colombia', 'currency' => 'COP', 'singular' => 'PESO COLOMBIANO', 'plural' => 'PESOS COLOMBIANOS', 'symbol', '$'),
    array('country' => 'Estados Unidos', 'currency' => 'USD', 'singular' => 'DÓLAR', 'plural' => 'DÓLARES', 'symbol', 'US$'),
    array('country' => 'Europa', 'currency' => 'EUR', 'singular' => 'EURO', 'plural' => 'EUROS', 'symbol', '€'),
    array('country' => 'México', 'currency' => 'MXN', 'singular' => 'PESO MEXICANO', 'plural' => 'PESOS MEXICANOS', 'symbol', '$'),
    array('country' => 'Perú', 'currency' => 'PEN', 'singular' => 'NUEVO SOL', 'plural' => 'NUEVOS SOLES', 'symbol', 'S/'),
    array('country' => 'Reino Unido', 'currency' => 'GBP', 'singular' => 'LIBRA', 'plural' => 'LIBRAS', 'symbol', '£'),
    array('country' => 'Argentina', 'currency' => 'ARS', 'singular' => 'PESO', 'plural' => 'PESOS', 'symbol', '$')
  );

  static private $separator = ',';
  static private $decimal_mark = '.';
  static private $glue = ' CON ';



  /**
   * Render a full text expressing the amount in the given currency
   *
   * @param $amount   string amount
   * @param $currency string currency. Leave empty to render without currency
   * @param $params   array additional parameters
   * @return string rendered string in the given language
   */
  public function amount2words($amount, $currency, $params = []) {
    return self::toWords($amount);
  }

  /**
   * Get a spoken word representation for the given currency
   *
   * @param $currency string currency symbol, e.g 'EUR' or 'USD'
   * @param $quantity int count, e.g. for plural
   * @return string   spoken word, e.g. 'Euro' or 'Dollar'
   */
  public function currency2word($currency, $quantity) {
    foreach (self::$MONEDAS as $moneda) {
      if ($moneda['currency'] == $currency) {
        if ($quantity > 1) {
          return $moneda['singular'];
        } else {
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
   * @param $number número a convertir
   * @param $miMoneda clave de la moneda
   * @return string completo
   */
  static public function toWords($number, $miMoneda = NULL) {
    if (strpos($number, self::$decimal_mark) === FALSE) {
      $convertedNumber = array(
        self::convertNumber($number, $miMoneda, 'entero')
      );
    }
    else {
      $number = explode(self::$decimal_mark, str_replace(self::$separator, '', trim($number)));

      $convertedNumber = array(
        self::convertNumber($number[0], $miMoneda, 'entero'),
        self::convertNumber($number[1], $miMoneda, 'decimal'),
      );
    }
    return implode(self::$glue, array_filter($convertedNumber));
  }

  /**
   * Convierte número a letras
   * @param $number
   * @param $miMoneda
   * @param $type tipo de dígito (entero/decimal)
   * @return $converted string convertido
   */
  static private function convertNumber($number, $miMoneda = NULL, $type) {
    $converted = '';
    if ($miMoneda !== NULL) {
      try {
        $moneda = array_filter(self::$MONEDAS, function($m) use ($miMoneda) {
          return ($m['currency'] == $miMoneda);
        });

        $moneda = array_values($moneda);

        if (count($moneda) <= 0) {
          throw new Exception("Tipo de moneda inválido");
          return;
        }
        ($number < 2 ? $moneda = $moneda[0]['singular'] : $moneda = $moneda[0]['plural']);
      }
      catch (Exception $e) {
        echo $e->getMessage();
        return;
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
   * @return $output
   */
  private static function convertGroup($n) {

    $output = '';

    if ($n == '100') {
      $output = "CIEN ";
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
