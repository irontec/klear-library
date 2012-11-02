<?php
//require_once('Zend/Controller/Plugin/Abstract.php');
//require_once('Zend/Session/Namespace.php');

/**
 * Plugin para gestionar el idioma en la web.
 * Cualquier parámetro con nombre "lang" hace que el idioma cambie al seleccionado,
 * solo si existe entre los "availableLanguage" que debe estar definido como opción en Zend_Translate.
 * @author Alayn Gortazar <alayn+karma@irontec.com>
 *
 */
class Iron_Controller_Plugin_Language extends Zend_Controller_Plugin_Abstract
{
    protected $_translate;
    protected $_session;
    protected $_request;
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_session = new Zend_Session_Namespace('session');
        $this->_translate = Zend_Registry::get('Zend_Translate');
        $this->_request = $request;

        $currentLanguage = $this->_getCurrentLanguage();

        $locale = new Zend_Locale($currentLanguage);
        Zend_Registry::set('Zend_Locale', $locale);

        $this->_session->lang = $locale->toString();
        $this->_translate->setLocale($locale);
    }

    /**
     * Returns current languages based on system's parameters. Preference:
     *     - lang param if present and compatible with availableLanguages
     *     - current session's language if present
     *     - Zend_Translate's default language
     * @param Zend_Controller_Request_Abstract $this->_request
     * @param Zend_Translate $this->_translate
     * @return Ambigous <mixed, multitype:>
     */
    protected function _getCurrentLanguage()
    {
        $availableLanguages = $this->_translate->getOptions('availableLanguage');
        if (sizeof($availableLanguages)) {
            $lang = $this->_request->getParam('lang');
            if (!is_null($lang) && in_array($lang, $availableLanguages)) {
                return $lang;
            }
        }

        if (isset($this->_session->lang)) {
            return $this->_session->lang;
        }

        return $this->_translate->getLocale();
    }
}