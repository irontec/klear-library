<?php
/**
 * Valida NIF, CIF y NIE
 * Valores ejemplo: '45750067P', 'B95274890', 'X9634268M'
 */

/**
 * @see Zend_Validate_Abstract
 */
require_once 'Zend/Validate/Abstract.php';

/**
 * @see Zend_Locale_Format
 */
require_once 'Zend/Locale/Format.php';

class Iron_Validate_Nif extends Zend_Validate_Abstract
{
    const INVALID = 'notValid';

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        self::INVALID => "'%value%' does not appear to be valid",
    );

    protected $_locale;

    /**
     * Constructor for the float validator
     *
     * @param string|Zend_Config|Zend_Locale $locale
     */
    public function __construct($locale = null)
    {
        if ($locale instanceof Zend_Config) {
            $locale = $locale->toArray();
        }

        if (is_array($locale)) {
            if (array_key_exists('locale', $locale)) {
                $locale = $locale['locale'];
            } else {
                $locale = null;
            }
        }

        if (empty($locale)) {
            require_once 'Zend/Registry.php';
            if (Zend_Registry::isRegistered('Zend_Locale')) {
                $locale = Zend_Registry::get('Zend_Locale');
            }
        }

        $this->setLocale($locale);
    }

    /**
     * Returns the set locale
     */
    public function getLocale()
    {
        return $this->_locale;
    }

    /**
     * Sets the locale to use
     *
     * @param string|Zend_Locale $locale
     */
    public function setLocale($locale = null)
    {
        require_once 'Zend/Locale.php';
        $this->_locale = Zend_Locale::findLocale($locale);
        return $this;
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if $value is a floating-point value
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        //Posibles valores para la letra final en DNI|NIE
        $valoresLetra = array(
            0 => 'T', 1 => 'R', 2 => 'W', 3 => 'A', 4 => 'G', 5 => 'M',
            6 => 'Y', 7 => 'F', 8 => 'P', 9 => 'D', 10 => 'X', 11 => 'B',
            12 => 'N', 13 => 'J', 14 => 'Z', 15 => 'S', 16 => 'Q', 17 => 'V',
            18 => 'H', 19 => 'L', 20 => 'C', 21 => 'K',22 => 'E'
        );

        //Comprobar si es un DNI
        if (preg_match('/^[0-9]{8}[A-Z]$/i', $value)) {
            //Comprobar letra
            if (
                strtoupper($value[strlen($value) - 1]) != $valoresLetra[((int) substr($value, 0, strlen($value) - 1)) % 23]
            ) {
                $this->_error(self::INVALID);
                return false;
            }

            //Todo fue bien
            return true;

        //Comprobar si es un NIE (tarjeta de residencia)
        } else if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/i', $value)) {

            //Comprobar letra
            if (
                strtoupper($value[strlen($value) - 1]) != $valoresLetra[((int) substr($value, 1, strlen($value) - 2)) % 23]
            ) {
                $this->_error(self::INVALID);
                return false;
            }

            //Todo fue bien
            return true;

        //Comprobar si es un CIF
        } else if (preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $value)) {

            $cifCodes = 'JABCDEFGHI';
            $sum = (string) $this->_getCifSum ($value);
            $n = (10 - substr ($sum, -1)) % 10;

            $isValid =  false;

            if (preg_match ('/^[ABCDEFGHJNPQRSUVW]{1}/', $value)) {
                if (in_array($value[0], array('A','B','E','H'))) {
                    // Numerico
                    $isValid = $value[8] == $n;

                } elseif (in_array($value[0], array('K','P','Q','S'))) {
                    // Letras
                    $isValid = $value[8] == $cifCodes[$n];

                } else {

                    // Alfanumérico
                    if (is_numeric($value[8])) {
                        $isValid = ($value[8] == $n);
                    } else {
                        $isValid = ($value[8] == $cifCodes[$n]);
                    }
                }
            }

            if ($isValid) {
                return true;
            }

            //Cadena no válida
            $this->_error(self::INVALID);
            return false;
        }
    }

    private function _getCifSum ($cif) {
        $sum = $cif[2] + $cif[4] + $cif[6];

        for ($i = 1; $i<8; $i += 2) {
            $tmp = (string) (2 * $cif[$i]);

            $tmp = $tmp[0] + ((strlen ($tmp) == 2) ?  $tmp[1] : 0);

            $sum += $tmp;
        }

        return $sum;
    }
}
