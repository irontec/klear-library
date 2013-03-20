<?php
/**
 *
 *
 * @author Lander Ontoria Gardeazabal <lander+dev@irontec.com>
 *
 */
class Iron_Plugin_Init extends Zend_Controller_Plugin_Abstract
{



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
        
        $defaultLang = 'es';
        $configLangs = array(
                'es' => array(
                        'title' => 'Español',
                        'language' => 'es',
                        'locale' => 'es_ES'),
                'en' => array(
                        'title' => 'English',
                        'language' => 'en',
                        'locale' => 'en_US'),
                'eu' => array(
                        'title' => 'Euskera',
                        'language' => 'eu',
                        'locale' => 'eu_ES')
        );

        /*
         * Loading System Languages
        */
        foreach ($configLangs as $_langIden => $lang) {
            $language = new \Iron_Model_Language();
            $language->setIden($_langIden);
            $language->setConfig($lang);
            $langs[$language->getIden()] = $language;
        }

        /*
         * Resquested Language // SESSION Language
        */

        $session = new Zend_Session_Namespace('PublicUserSettings');

        $front = Zend_Controller_Front::getInstance();

        $req = $front->getRequest();
        
        $requestedLanguage = false;
        if ($req) {
            $requestedLanguage = $front->getRequest()->getParam('language', false);
        }


        $lang = null;
        
        if ($requestedLanguage && (array_key_exists($requestedLanguage, $configLangs)) ) {
            $lang = $requestedLanguage;
        }
        if ((!$lang)
                && ($session->currentSystemLanguage!=null)
                && (array_key_exists($session->currentSystemLanguage, $configLangs)) ) {
            $lang = $session->currentSystemLanguage;
        }

        if (!$lang) {
            $lang = $defaultLang;
        }

        $session->currentSystemLanguage = $lang;

        /*
         * Setting language Object
        */
        $this->_lang = $configLangs[$session->currentSystemLanguage];

        Zend_Registry::set('currentSystemLanguage', $this->_lang);
        Zend_Registry::set('SystemDefaultLanguage', $configLangs[$defaultLang]);
        Zend_Registry::set('SystemLanguages', $configLangs);
        Zend_Registry::set('defaultLang', $this->_lang['language']);



        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new Iron_Plugin_Translator());


    }

}