<?php
/**
 * @author ddniel16 <daniel@irontec.com>
 */
class Image_Plugin_Init extends Zend_Controller_Plugin_Abstract
{

    protected $_bootstrap;

    /**
     * Este método que se ejecuta una vez se ha matcheado la ruta adecuada
     * (non-PHPdoc)
     * @see Zend_Controller_Plugin_Abstract::routeShutdown()
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {

        if (!preg_match("/^image/", $request->getModuleName())) {
            return;
        }

        $this->_initPlugin();

    }

    public function _initPlugin()
    {

        $front = Zend_Controller_Front::getInstance();

        $this->_bootstrap = $front
            ->getParam('bootstrap')
            ->getResource('modules')
            ->offsetGet('image');

    }

}