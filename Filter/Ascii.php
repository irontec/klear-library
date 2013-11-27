<?php
class Iron_Filter_Ascii implements Zend_Filter_Interface
{
    protected $_currentLocales;
    protected $_locales = array(
            'en_US.UTF8', 
            'en_GB.UTF8', 
            'es_ES.UTF8', 
            'eu_ES.UTF8', 
            'fr_FR.UTF8', 
            'pt_PT.UTF8', 
            'ca_ES.UTF8',
            'gl_ES.UTF8',
            'UTF8'
    );

    public function filter($value)
    {
        $this->_saveCurrentLocales();

        setlocale(LC_ALL, $this->_locales);
        $asciiValue = iconv('UTF-8', 'ASCII//TRANSLIT', $value);

        $this->_restoreCurrentLocales();
        return $asciiValue;
    }

    protected function _saveCurrentLocales()
    {
        $localesString = setlocale(LC_ALL, 0);
        if (false === strpos($localesString, ';')) {
            $this->_currentLocales = $localesString;
        } else {
            $localesArray = explode(';', $localesString);
            $this->_currentLocales = array_map(
                function ($locale) {
                    return explode('=', $locale);
                },
                $localesArray
            );
        }
    }

    protected function _restoreCurrentLocales()
    {
        if (!is_array($this->_currentLocales)) {
            setlocale(LC_ALL, $this->_currentLocales);
        } else {
            array_walk(
                $this->_currentLocales,
                function ($locale) {
                    if (defined($locale[0])) {
                        setlocale(constant($locale[0]), $locale[1]);
                    }
                }
            );
        }
    }
}
