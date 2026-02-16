<?php

declare(strict_types = 1);

/**
 * Class Kwota
 *
 * Based on https://bitbucket.org/stopsopa/kwotaslownie
 *
 */
class Kwota {

  protected $zl;
  protected $gr;
  protected $zlparts;
  protected $table;
  protected $jednosciNascie;
  protected $dziesiatki;
  protected $setki;
  protected static $instance;

  /**
   * Singleton
   * @return Kwota
   */
  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Kwota constructor.
   */
  protected function __construct() {}

  /**
   *
   */
  protected function _init() {
    if (!$this->table) {
      // http://pl.wikipedia.org/wiki/Liczebniki_g%C5%82%C3%B3wne_pot%C4%99g_tysi%C4%85ca
      // google(Liczebniki główne potęg tysiąca)
      // tysiąc milion miliard bilion biliard trylion tryliard kwadrylion
      // kwadryliard kwintylion kwintyliard sekstylion sekstyliard septylion
      // septyliard oktylion
      //
      // 10   9   8   7   6   5   4   3   2   1   0
      // 000 000 000 000 000 000 000 000 000 000 000   0-set,0-dzi,0-jedn
      //         kwa trd try bid bil mld mln tys set,dzi,jed
      $this->table = [
        // odmiana przez przypadki
        0  => explode(',', ',,'),
        1  => explode(',', 'tysiąc,tysiące,tysięcy'),
        2  => explode(',', 'milion,miliony,milionów'),
        3  => explode(',', 'miliard,miliardy,miliardów'),
        4  => explode(',', 'bilion,biliony,bilionów'),
        5  => explode(',', 'biliard,biliardy,biliardów'),
        6  => explode(',', 'trylion,tryliony,trylionów'),
        7  => explode(',', 'tryliard,tryliardy,tryliardów'),
        8  => explode(',', 'kwadrylion,kwadryliony,kwadrylionów'),
        9  => explode(',', 'kwadryliard,kwadryliardy,kwadryliardów'),
        10 => explode(',', 'kwintylion,kwintyliony,kwintylionów'),
        11 => explode(',', 'kwintyliard,kwintyliardy,kwintyliardów'),
        12 => explode(',', 'sekstylion,sekstyliony,sekstylionów'),
        13 => explode(',', 'sekstyliard,sekstyliardy,sekstyliardów'),
        // można rozszerzyć, ale wątpię aby ktoś tego potrzebował...
      ];
      // phpcs:disable Generic.Files.LineLength.TooLong
      $this->jednosciNascie = explode(
        ',',
        ', jeden , dwa , trzy , cztery , pięć , sześć , siedem , osiem , dziewięć , dziesięć , jedenaście , dwanaście , trzynaście , czternaście , piętnaście , szesnaście , siedemnaście , osiemnaście , dziewiętnaście '
      );
      $this->dziesiatki = explode(
        ',',
        ',, dwadzieścia , trzydzieści , czterdzieści , pięćdziesiąt , sześćdziesiąt , siedemdziesiąt , osiemdziesiąt , dziewięćdziesiąt '
      );
      // phpcs:enable
      $this->setki = explode(
        ',',
        ', sto , dwieście , trzysta , czterysta , pięćset , sześćset , siedemset , osiemset , dziewięćset '
      );
    }
    $this->zlparts = [];
  }

  /**
   * @param string|float|int $kwota - '5435.7665' || 4321.55 || 432 || .45
   * @param null|string $intEnd
   *    null  -> 'złoty,złote,złotych'
   *    false -> ',,'
   * @param bool|null $float
   *    null, -> ''
   *    false -> zwraca '67/100'
   *    true -> zwraca 'sześćdziesiąt siedem'
   * @param null|true|string $floatEnd
   *    null  -> 'grosz,grosze,groszy'
   *    false -> ',,'
   *
   * @return string
   * @throws \Exception
   */
  public function slownie($kwota, $intEnd = NULL, $float = TRUE, $floatEnd = NULL) {
    ($intEnd === NULL)  && ($intEnd   = 'złoty,złote,złotych');
    ($intEnd === FALSE) && ($intEnd   = ',,');
    ($floatEnd === NULL)  && ($floatEnd = 'grosz,grosze,groszy');
    ($floatEnd === FALSE) && ($floatEnd = ',,');
    $this->_init();
    $this->_rozbij($kwota);
    $i = 0;
    foreach ($this->zl as $d) {
      $a = [
        'd' => $d,
      // tysiąc dwieście itd..
        's' => $this->_licz($d),
      ];
      // dodaję na koniec bilionów, tysięcy, milionów itd
      $a['s'][] = $this->_mnoznikSlownie($d, $i++);
      $this->zlparts[] = $a;
    }

    $return = $this->_getzl() . $this->_zlend($intEnd) . $this->_getgr($float) . $this->_grend($floatEnd);

    // usuwam powtarzające się białe znaki i trim dla całości
    return trim(preg_replace('#\s\s+#', ' ', $return));
  }

  /**
   * @param $intEnd
   *
   * @return string
   * @throws \Exception
   */
  protected function _zlend($intEnd) {
    if (!isset($this->zlparts[0])) {
      return '';
    }
    $last = $this->zlparts[0]['d'];
    if (is_string($intEnd)) {
      if (!preg_match('#[^,]*(,[^,]*,[^,]*)?#', $intEnd)) {
        throw new Exception("\$intEnd has wrong format, should be like: 'złoty,złote,złotych' || true || null");
      }
      $intEnd = explode(',', $intEnd);

      if (count($intEnd) < 3) {
        $intEnd[1] = $intEnd[2] = $intEnd[0];
      }

      return ' ' . $this->_mnoznikSlownie($last, NULL, $intEnd);
    }
    return '';
  }

  /**
   * @param $floatEnd
   *
   * @return string
   * @throws \Exception
   */
  protected function _grend($floatEnd) {
    if ($floatEnd === TRUE) {
      return "{$this->gr}/100";
    }
    if (is_string($floatEnd)) {
      if (!preg_match('#[^,]*(,[^,]*,[^,]*)?#', $floatEnd)) {
        throw new Exception(
          "\$intEnd has wrong format, should be like: 'złoty,złote,złotych' || 'PLN' || true || null"
        );
      }
      $floatEnd = explode(',', $floatEnd);

      if (count($floatEnd) < 3) {
        $floatEnd[1] = $floatEnd[2] = $floatEnd[0];
      }

      return $this->_mnoznikSlownie($this->gr, NULL, $floatEnd);
    }
    return '';
  }

  /**
   * @return string
   */
  protected function _getzl() {
    $tmp = '';
    for ($i = count($this->zlparts) - 1; $i > -1; $i--) {
      $tmp .= implode('', $this->zlparts[$i]['s']);
    }
    return $tmp;
  }

  /**
   * @param $float  null, -> ''
   * false -> zwraca '67/100'
   * true -> zwraca 'sześćdziesiąt siedem'
   *
   * @return string
   */
  protected function _getgr($float) {
    if ($float === FALSE) {
      return "{$this->gr}/100 ";
    }
    return implode(' ', $this->_licz($this->gr));
  }

  /**
   * @param $kwota
   */
  protected function _rozbij($kwota) {
    $d = $this->_numberFormat($kwota, 2, ',', '.');
    $d = explode(',', $d);
    $this->zl = array_reverse(explode('.', $d[0]));
    $this->gr = $d[1];
  }

  /**
   * @param $licz
   * @param $i
   * @param array|null $table
   *
   * @return string
   */
  protected function _mnoznikSlownie($licz, $i, $table = NULL) {
    $table = $table ? $table : $this->table[$i];
    if ($licz == 1) {
      return $table[0];
    }
    $licz = str_pad($licz, 3, '0', STR_PAD_LEFT);
    $last   = $licz[strlen($licz) - 1];
    $second = (isset($licz[1]) && $licz[1] < 2 && $licz[1] > 0) ? TRUE : FALSE;
    if (($second) || ($last < 2 || $last > 4)) {
      return $table[2];
    }
    return $table[1];
  }

  /**
   * @param $liczba
   *
   * @return array
   */
  protected function _licz($liczba) {
    $liczba = str_pad($liczba, 3, '0', STR_PAD_LEFT);
    $r = [];
    if ($liczba == 0) {
      $r[] = ' zero ';
      return $r;
    }
    if (strlen($liczba) > 2) {
      $r[] = $this->setki[$liczba[0]];
      $liczba = ($liczba - $liczba[0] * 100) . '';
    }
    if ((int) $liczba < 20) {
      $r[] = $this->jednosciNascie[$liczba];
    }
    else {
      $r[] = $this->dziesiatki[$liczba[0]];
      $r[] = $this->jednosciNascie[$liczba[1]];
    }
    return $r;
  }

  /**
   * http://www.mail-archive.com/php-general@lists.php.net/msg217138.html
   * warto też popatrzeć tu: http://stackoverflow.com/questions/1642614/how-to-ceil-floor-and-round-bcmath-numbers
   *
   * @param $number
   * @param int $precision
   *
   * @return string
   */
  protected function _roundbc($number, $precision = 0) {
    if (strpos($number, '.') !== FALSE) {
      if ($number[0] != '-') {
        return bcadd($number, '0.' . str_repeat('0', $precision) . '5', $precision);
      }
      return bcsub($number, '0.' . str_repeat('0', $precision) . '5', $precision);
    }
    return $number;
  }

  /**
   * Przyjmuje parametry w fromie '54745432.8754545' lub '542654654' lub to samo ujemne
   * @param string $number
   * @param integer $decimal
   * @param string $dec_point
   * @param string $thousands_sep
   * @return string
   */
  public function _numberFormat($number, $decimal = 0, $dec_point = '.', $thousands_sep = ',') {
    $number .= '';
    $number = preg_match('#[0-9]#', $number[0]) ? $number : '0' . $number;
    $number = $this->_roundbc($number, $decimal);
    if ($last = strrpos($number, '.')) {
      $int = substr($number, 0, $last);
      $fra = substr($number, $last + 1);
    }
    else {
      $int = $number;
      $fra = '';
    }

    if (strlen($fra) < $decimal) {
      $fra = str_pad($fra, $decimal, '0');
    }

    $a = [];
    while ($i = substr($int, -3, 3)) {
      $int = substr($int, 0, -3);
      array_unshift($a, $i);
    }
    $int = implode($thousands_sep, $a);
    $int = strlen($int) ? $int : '0';
    return $int . $dec_point . $fra;
  }

}
