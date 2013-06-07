<?php
/**
 *
 *
 * @author Lander Ontoria Gardeazabal <lander+dev@irontec.com>
 *
 */
class Iron_Plugin_Translator extends Zend_Controller_Plugin_Abstract
{
    const DEFAULT_REGISTRY_KEY = 'Application_Translate';

    /**
     *
     * @var Zend_Translate
     */
    protected $_translate;

    protected $_languages = array();

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_initTranslator();
    }

    protected function _initTranslator()
    {
        foreach ($this->_languages as $locale) {
            $this->_addTranslation($locale);
        }

        $this->_translate->getAdapter()->setLocale(Zend_Registry::get('defaultLang'));
    }

    protected function _addTranslation($locale)
    {

        if (!Zend_Registry::isRegistered(self::DEFAULT_REGISTRY_KEY)) {
            $this->_registerTranslator($locale);
        } else {
            $translationPath = $this->_getTranslationPath(APPLICATION_PATH, $locale);
            $this->_translate->getAdapter()->addTranslation(
                array(
                    'content' => $translationPath,
                    'locale' => $locale->getLanguage()
                )
            );
        }
    }

    protected function _registerTranslator($locale)
    {
        $translationPath = $this->_getTranslationPath(APPLICATION_PATH, $locale);
        $this->_translate = new Zend_Translate(
            array(
                'disableNotices' => true,
                'adapter' => 'Iron_Translate_Adapter_GettextKlear',
                'content' => $translationPath,
                'locale' => $locale->getLanguage()
            )
        );

        Zend_Registry::set(self::DEFAULT_REGISTRY_KEY, $this->_translate);
        Zend_Form::setDefaultTranslator($this->_translate);
        Zend_Validate_Abstract::setDefaultTranslator($this->_translate);
        $this->_setViewHelperTranslator();
        $this->_setActionHelperTranslator();
    }

    /**
     * Returns translation file path
     * @param unknown_type $moduleDirectory
     * @param unknown_type $locale
     * @return string
     */
    protected function _getTranslationPath($moduleDirectory, $locale)
    {

        $translationPath = array(
                $moduleDirectory,
                'languages',
                $locale->toString(),
                $locale->toString() . '.mo'
        );

        return implode(DIRECTORY_SEPARATOR, $translationPath);
    }

    /**
     * Sets Klear Translator into instanced view
     */
    protected function _setViewHelperTranslator()
    {
        $frontController = Zend_Controller_Front::getInstance();
        $view = $frontController->getParam("bootstrap")->getResource('view');

        if ($view) {
            $this->_translateHelper = $view->getHelper('Translate');
            $this->_translateHelper->setTranslator($this->_translate);
        } else {
            $logHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('log');
            $logHelper->warn('WARNING: No view resource detected. (resources.view[]="")');
        }
    }

    /**
     * Sets Klear Translator into instanced view
     */
    protected function _setActionHelperTranslator()
    {
        Zend_Controller_Action_HelperBroker::addHelper(
            new Klear_Controller_Helper_Translate($this->_translate)
        );
    }

    public function addLanguage($language)
    {
        $this->_languages[] = new Zend_Locale($language);
    }
}
