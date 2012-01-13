<?php
require_once('Zend/Controller/Plugin/Abstract.php');
require_once('Zend/Session/Namespace.php');

/**
 * Plugin para gestionar el idioma en la web.
 * Cualquier parámetro con nombre "lang" hace que el idioma cambie al seleccionado,
 * solo si existe entre los "availableLanguage".
 * @author Alayn Gortazar <alayn+karma@irontec.com>
 *
 */
class Iron_Controller_Plugin_Language extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $registry = Zend_Registry::getInstance();
        $translate = $registry->get('Zend_Translate');

        $currLocale = $translate->getLocale();
        $session = new Zend_Session_Namespace('session');

        $lang = $request->getParam('lang', '');

        $availableLanguages = $translate->getOptions('availableLanguage');
        if (sizeof($availableLanguages)) {
            if (in_array($lang, $availableLanguages)) {
                $langLocale = $lang;
            } else {
                $langLocale = isset($session->lang) ? $session->lang : $currLocale;
            }
        }

        $newLocale = new Zend_Locale();
        $newLocale->setLocale($langLocale);
        $registry->set('Zend_Locale', $newLocale);

        $translate->setLocale($langLocale);
        $session->lang = $langLocale;

        // Save the modified translate back to registry
        $registry->set('Zend_Translate', $translate);
    }
}