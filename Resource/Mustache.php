<?php

/**
 *
 * @author ddniel16 <daniel@irontec.com>
 */

class Iron_Resource_Mustache extends Zend_Application_Resource_ResourceAbstract
{

    /**
     * The default configuration
     * @var array
     */
    static $defaults = array(
        'suffix' => 'phtml',
        'enabled' => false,
        'basePath' => '../views/scripts'
    );

    /**
     * @see Zend_Application_Resource_Resource::init()
     * @return Mustache_View
     */
    public function init()
    {
        return $this;
    }

    /**
     *
     * @param unknown $viewData
     */
    public function setDataTemlate($viewData)
    {

        $cache = null;
        $cachePath = null;
        $partials = null;
        $suffix = null;
        $partialsPath = null;
        $loader = Zend_Loader_Autoloader::getInstance();
        $loader->registerNamespace('Mustache');

        $options = $this->mergeOptions(self::$defaults, $this->getOptions());

        extract($options);

        $mustacheEngine = array();

        if ($cache) {

            if (! file_exists($cachePath)) {
                mkdir($cachePath, 0755, true);
            }

            $mustacheEngine['template_class_prefix'] = $cachePrefix;
            $mustacheEngine['cache'] = $cachePath;
            $mustacheEngine['cache_file_mode'] = 0666;

        }

        if ($partials) {

            $options = array(
                'extension' => '.' . str_replace('.', '', $suffix)
            );

            $mustacheFilesLoader = new Mustache_Loader_FilesystemLoader(
                $partialsPath,
                $options
            );

            $mustacheEngine['partials_loader'] = $mustacheFilesLoader;

        }

        $mustacheEngine['charset'] = 'UTF-8';
        $mustacheEngine['escape'] = function ($value)
        {
            return $value;
            // return htmlspecialchars($value, ENT_IGNORE, 'UTF-8');
        };

        $view = new Mustache_View($mustacheEngine, $viewData);
        $view->setBasePath($basePath);

        if ($enabled) {
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
            $viewRenderer->setView($view)->setViewSuffix($suffix);
            Zend_Layout::getMvcInstance()->setViewSuffix($suffix);
        }

        return $view;
    }

}