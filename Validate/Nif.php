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
     * Constructor for the Nif validator
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
     * Returns true if and only if $value is a nif value
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

        $isValid = false;

        //Comprobar si es un DNI
        if (preg_match('/^[0-9]{8}[A-Z]$/i', $value)) {

            //Comprobar letra
            if (
                strtoupper($value[strlen($value) - 1]) == $valoresLetra[((int) substr($value, 0, strlen($value) - 1)) % 23]
            ) {
                $isValid = true;
            }

        //Comprobar si es un NIE (tarjeta de residencia)
        } else if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/i', $value)) {

            //Comprobar letra
            if (
                strtoupper($value[strlen($value) - 1]) == $valoresLetra[((int) substr($value, 1, strlen($value) - 2)) % 23]
            ) {
                $isValid = true;
            }

        //Comprobar si es un CIF
        } else if (preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $value)) {
            /*
                Letra:
                A - Sociedad Anónima.
                B - Sociedad de Responsabilidad Limitada.
                ...
                P - Corporación local.
                S - Organos de la Administración del Estado y Comunidades Autónomas

                Cod. Control:
                Es una letra si la clave de la  organización es K, P, Q ó S y es un número si la clave de la organización es A, B, E ó H.
                Para el resto de claves indentificativas del tipo de organización podrá ser tanto número como letra.

               +info: http://www.aplicacionesinformaticas.com/programas/gratis/cif.php
            */
            $suma = $value[2] + $value[4] + $value[6];

            for ($i = 1; $i < 8; $i += 2) {
                $suma += substr((2 * $value[$i]),0,1) + substr((2 * $value[$i]), 1, 1);
            }

            $n = 10 - substr($suma, strlen($suma) - 1, 1);
            if ($value[8] == chr(64 + $n) || $value[8] == substr($n, strlen($n) - 1, 1)) {
                $isValid = true;
            }

        } else {
            $isValid = false;
        }

        if ($isValid) {
            return true;
        }

        //Cadena no válida
        $this->_error(self::INVALID);
        return false;
    }
}
