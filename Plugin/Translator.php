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
     * @var Zend_Controller_Front
     */
    protected $_frontController;

    /**
     *
     * @var Zend_Translate
     */
    protected $_translate;

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        
        $this->_initTranslator();
    }

    protected function _initTranslator()
    {
        $this->_frontController = Zend_Controller_Front::getInstance();

        
        $sesion = Zend_Registry::get('currentSystemLanguage');
        
        
        $locale = new Zend_Locale($sesion['locale']);
        
        $translationPath = $this->_getTranslationPath(APPLICATION_PATH, $locale);
        
        if (!Zend_Registry::isRegistered(self::DEFAULT_REGISTRY_KEY)) {
        
            $this->_translate = new Zend_Translate(
                    array(
                            'disableNotices' => true,
                            'adapter' => 'Iron_Translate_Adapter_GettextKlear',
                            'content' => $translationPath
                    )
            );
        
            Zend_Registry::set(self::DEFAULT_REGISTRY_KEY, $this->_translate);
            $this->_setViewHelperTranslator();
            $this->_setActionHelperTranslator();
        
        } else {
        
            $this->_translate->getAdapter()->addTranslation($translationPath);
        
        }
        
        
        Zend_Form::setDefaultTranslator($this->_translate);
        Zend_Validate_Abstract::setDefaultTranslator($this->_translate);
        
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
        $view = $this->_frontController->getParam("bootstrap")->getResource('view');

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
}
