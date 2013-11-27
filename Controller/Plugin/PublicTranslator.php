<?php
/**
 *
 *
 * @author Lander Ontoria Gardeazabal <lander+dev@irontec.com>
 *
 */
class Iron_Controller_Plugin_PublicTranslator extends Zend_Controller_Plugin_Abstract
{

    const DEFAULT_USER_SESSION_NAMESPACE = 'PublicUserSettings';
    const DEFAULT_REQUEST_LANGUAGE_PARAM = 'language';

    protected $_bootstrap;
    protected $_config;

    protected $_defaultLang;
    protected $_langsConfig;
    protected $_session;

    /**
     * Este método que se ejecuta una vez se ha matcheado la ruta adecuada
     * (non-PHPdoc)
     * @see Zend_Controller_Plugin_Abstract::routeShutdown()
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        if (preg_match("/^klear/", $request->getModuleName())) {
            return;
        }
        $this->_init();

        $currentLang = $this->_getCurrentLang();
        $currentLangConfig = $this->_langsConfig[$currentLang];
        $this->_session->currentSystemLanguage = $currentLang;
        Zend_Registry::set('currentSystemLanguage', $currentLangConfig);
        Zend_Registry::set('SystemDefaultLanguage', $this->_langsConfig[$this->_defaultLang]);
        Zend_Registry::set('SystemLanguages', $this->_langsConfig);
        Zend_Registry::set('defaultLang', $currentLangConfig['language']);

        if ($this->_cookiesEnabled()) {
            $this->_createCookie($currentLang);
        }

        $translatorPlugin = new Iron_Plugin_Translator();
        foreach ($this->_langsConfig as $lang) {
            $translatorPlugin->addLanguage($lang['locale']);
        }

        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin($translatorPlugin);
    }

    protected function _init()
    {
        $this->_bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $this->_config = $this->_bootstrap->getOption('translate');

        $this->_langsConfig = $this->_getLangsConfig();
        $this->_defaultLang = $this->_getDefaultLang($this->_langsConfig);
        $this->_session = new Zend_Session_Namespace($this->_getPublicUserSessionNamespace());
    }

    protected function _getLangsConfig()
    {
        if (isset($this->_config['language'])) {
            return $this->_config['language'];
        }

        
        return array(
                'es' => array(
                        'title' => 'Español',
                        'language' => 'es',
                        'locale' => 'es_ES'),
                'eu' => array(
                        'title' => 'Euskara',
                        'language' => 'eu',
                        'locale' => 'eu_ES'),
                'ca' => array(
                        'title' => 'Català',
                        'language' => 'ca',
                        'locale' => 'ca_ES'),
                'ga' => array(
                        'title' => 'Galego',
                        'language' => 'gl',
                        'locale' => 'gl_ES'),
                'en' => array(
                        'title' => 'English',
                        'language' => 'en',
                        'locale' => 'en_US'),
                'fr' => array(
                        'title' => 'Français',
                        'language' => 'fr',
                        'locale' => 'fr_FR'),
                'pt' => array(
                        'title' => 'Português',
                        'language' => 'pt',
                        'locale' => 'pt_PT')
        );
      
    }

    protected function _getDefaultLang(array $langsConfig)
    {
        $defaultLang = $this->_bootstrap->getOption('defaultLanguage'); //Deprecated
        if ($defaultLang) {
            return $defaultLang;
        }

        if (isset($this->_config['defaultLanguage'])) {
            return $this->_config['defaultLanguage'];
        }

        return key($langsConfig);
    }

    protected function _getPublicUserSessionNamespace()
    {
        if (isset($this->_config['userNamespace'])) {
            return $this->_config['userNamespace'];
        }
        return self::DEFAULT_USER_SESSION_NAMESPACE;
    }

    protected function _getCurrentLang()
    {
        // Take requested lang
        $requestedLanguage = $this->getRequest()->getParam($this->_getLanguageParam());
        if ($requestedLanguage && array_key_exists($requestedLanguage, $this->_langsConfig)) {
            return $requestedLanguage;
        }

        // Take session lang
        $currentSystemLanguage = $this->_session->currentSystemLanguage;
        if (!is_null($currentSystemLanguage) && array_key_exists($currentSystemLanguage, $this->_langsConfig)) {
            return $currentSystemLanguage;
        }


        if ($this->_cookiesEnabled()) {
            if (isset($_COOKIE[$this->_getLanguageParam()])) {
                return $_COOKIE[$this->_getLanguageParam()];
            }
        }

        if ($this->_detectFromBrowser()) {
            $locale = new Zend_Locale();
            $browserLanguage = $locale->getLanguage();
            if (!is_null($browserLanguage) && array_key_exists($browserLanguage, $this->_langsConfig)) {
                return $browserLanguage;
            }
        }

        //OK, take default lang
        return $this->_defaultLang;
    }

    protected function _detectFromBrowser()
    {
        return !isset($this->_config['detectBrowser']) || (bool)$this->_config['detectBrowser'];
    }

    protected function _cookiesEnabled()
    {
        return isset($this->_config['cookies']['enabled']) && (bool)$this->_config['cookies']['enabled'];
    }

    protected function _createCookie($currentLang)
    {
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $domain = $_SERVER['HTTP_HOST'];
        $expiration = $this->_getCookieExpirationTime();
        setcookie($this->_getLanguageParam(), $currentLang, $expiration, $baseUrl, $domain);
    }

    protected function _getCookieExpirationTime()
    {
        $lifetime = 3600 * 24 * 7;
        if (isset($this->_config['cookies']['lifetime'])) {
            $lifetime = $this->_config['cookies']['lifetime'];
        }
        return time() + $lifetime;
    }


    protected function _getLanguageParam()
    {
        if (isset($this->_config['requestParam'])) {
            return $this->_config['requestParam'];
        }
        return self::DEFAULT_REQUEST_LANGUAGE_PARAM;
    }
}