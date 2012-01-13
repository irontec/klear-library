<?php
/** Zend_Locale */
require_once 'Zend/Locale.php';

/** Zend_Translate_Adapter */
require_once 'Zend/Translate/Adapter.php';

/** Zend_Translate_Adapter */
require_once 'Zend/Db/Table.php';

/**
 * Adaptador para el sistema de literales de Karma.
 * Guarda los datos no existentes en la tabla literales, para poder traducir desde la BBDD.
 * Recoge las traducciones de esa misma tabla.
 *
 * @author Alayn Gortazar <alayn+karma@irontec.com>
 *
 */
class Iron_Translate_Adapter_Literals extends Zend_Translate_Adapter
{
    private $_data = array();
    private $_dbAdapter = null;

    public function __construct($options = array())
    {
        if (isset($options['dbAdapter']) && $options['dbAdapter'] instanceof Zend_Db_Adapter_Abstract) {
            $this->setDbAdapter($options['dbAdapter']);
        }
        parent::__construct($options);
    }

    /**
     * Load translation data
     *
     * @param  string|array  $data
     * @param  string        $locale  Locale/Language to add data for, identical with locale identifier,
     *                                see Zend_Locale for more information
     * @param  array         $options OPTIONAL Options to use
     * @throws Zend_Translate_Exception Ini file not found
     * @return array
     */
    protected function _loadTranslationData($data, $locale, array $options = array())
    {
        $this->_data = array();
        $dbData = array();

        $dbAdapter = $this->getDbAdapter();
        $select = $dbAdapter->select();
        $select->from(
            $data,
            array(
                'identificativo',
                'literal' => 'literal_' . $locale
            )
        );

        $dbStmt = $dbAdapter->query($select);
        $resultSet = $dbStmt->fetchAll();
        foreach ($resultSet as $row) {
            $dbData[$row['identificativo']] = $row['literal'];
        }

        if (!isset($this->_data[$locale])) {
            $this->_data[$locale] = array();
        }

        $this->_data[$locale] = $dbData + $this->_data[$locale];
        return $this->_data;
    }

    /**
     * Translates the given string
     * returns the translation
     *
     * @see Zend_Locale
     * @param  string|array       $messageId Translation string, or Array for plural translations
     * @param  string|Zend_Locale $locale    (optional) Locale/Language to use, identical with
     *                                       locale identifier, @see Zend_Locale for more information
     * @return string
     */
    public function translate($messageId, $locale = null)
    {
        /**
         * BUGFIX: No metemos en la BBDD nada que no tenga letras.
         */
        if (preg_match('/[a-zA-Z]/', $messageId) === 0) {
            return $messageId;
        }

        if ($locale === null) {
            $locale = $this->_options['locale'];
        }
        $plural = null;
        if (is_array($messageId)) {
            if (count($messageId) > 2) {
                $number = array_pop($messageId);
                if (!is_numeric($number)) {
                    $plocale = $number;
                    $number  = array_pop($messageId);
                } else {
                    $plocale = 'en';
                }

                $plural    = $messageId;
                $messageId = $messageId[0];
            } else {
                $messageId = $messageId[0];
            }
        }

        if (!Zend_Locale::isLocale($locale, true, false)) {
            if (!Zend_Locale::isLocale($locale, false, false)) {
                // language does not exist, return original string
                $this->_log($messageId, $locale);
                // use rerouting when enabled
                if (!empty($this->_options['route'])) {
                    if (array_key_exists($locale, $this->_options['route']) &&
                        !array_key_exists($locale, $this->_routed)) {
                        $this->_routed[$locale] = true;
                        return $this->translate($messageId, $this->_options['route'][$locale]);
                    }
                }

                $this->_routed = array();
                if ($plural === null) {
                    return $messageId;
                }

                $rule = Zend_Translate_Plural::getPlural($number, $plocale);
                if (!isset($plural[$rule])) {
                    $rule = 0;
                }

                return $plural[$rule];
            }

            $locale = new Zend_Locale($locale);
        }

        $locale = (string) $locale;
        if ((is_string($messageId) || is_int($messageId))
                && isset($this->_translate[$locale][$messageId])
                && ($this->_translate[$locale][$messageId])) {
            // return original translation
            if ($plural === null) {
                $this->_routed = array();
                return $this->_translate[$locale][$messageId];
            }

            $rule = Zend_Translate_Plural::getPlural($number, $locale);
            if (isset($this->_translate[$locale][$plural[0]][$rule])) {
                $this->_routed = array();
                return $this->_translate[$locale][$plural[0]][$rule];
            }
        } else if (strlen($locale) != 2) {
            // faster than creating a new locale and separate the leading part
            $locale = substr($locale, 0, -strlen(strrchr($locale, '_')));

            if ((is_string($messageId) || is_int($messageId)) && isset($this->_translate[$locale][$messageId])) {
                // return regionless translation (en_US -> en)
                if ($plural === null) {
                    $this->_routed = array();
                    return $this->_translate[$locale][$messageId];
                }

                $rule = Zend_Translate_Plural::getPlural($number, $locale);
                if (isset($this->_translate[$locale][$plural[0]][$rule])) {
                    $this->_routed = array();
                    return $this->_translate[$locale][$plural[0]][$rule];
                }
            }
        }

        $this->_log($messageId, $locale);
        // use rerouting when enabled
        if (!empty($this->_options['route'])) {
            if (array_key_exists($locale, $this->_options['route']) &&
                !array_key_exists($locale, $this->_routed)) {
                $this->_routed[$locale] = true;
                return $this->translate($messageId, $this->_options['route'][$locale]);
            }
        }

        if (isset($this->_translate[$locale])) {
            if (!array_key_exists($messageId, $this->_translate[$locale])) {
                $this->_createKey($messageId, $locale);
            }
        }

        $this->_routed = array();
        if ($plural === null) {
            return $messageId;
        }

        $rule = Zend_Translate_Plural::getPlural($number, $plocale);
        if (!isset($plural[$rule])) {
            $rule = 0;
        }

        return $plural[$rule];
    }

    protected function _createKey($messageId, $locale)
    {
        $dbAdapter = $this->getDbAdapter();
        if ($dbAdapter->insert('literales', array('identificativo' => $messageId))) {
            $languages = array_keys($this->_translate);
            foreach ($languages as $language) {
                $this->_translate[$language][$messageId] = null;
            }
        }
    }

    /**
     * TODO: Revisar esto que está demasiado simple para nada bueno...
     */
    public function addTranslation($options = array())
    {
        foreach ($options['availableLanguage'] as $language) {
            $this->_translate += $this->_loadTranslationData($options['content'], $language, $options);
        }
    }

    /**
     * returns the adapters name
     *
     * @return string
     */
    public function toString()
    {
        return "Iron_Translate_Adapter_Literals";
    }

    public function getDbAdapter()
    {
        if (is_null($this->_dbAdapter)) {
            $this->_dbAdapter = Zend_Db_Table::getDefaultAdapter();
        }
        return $this->_dbAdapter;
    }

    public function setDbAdapter(Zend_Db_Adapter_Abstract $dbAdapter)
    {
        $this->_dbAdapter = $dbAdapter;
    }
}
