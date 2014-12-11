<?php
/**
 * @author ddniel16 <daniel@irontec.com>
 */
class Image_Bootstrap extends Zend_Application_Module_Bootstrap
{

    protected function _initImage()
    {

        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new Image_Plugin_Init());

    }

    protected function _initModuleRoutes()
    {

        $frontController = Zend_Controller_Front::getInstance();

        $imagesPath = APPLICATION_PATH . '/configs/images.ini';
        $defaultsRoutes = array(
            'controller' => 'index',
            'action' => 'index',
            'module' => 'image',
            'profiel' => NULL,
            'routeMap' => NULL
        );

        if (!file_exists($imagesPath)) {
            throw new Exception(
                'No Existe el fichelo de configuracion images.ini',
                404
            );
        }

        $imageCacheConfig = new \Zend_Config_Ini(
            $imagesPath,
            APPLICATION_ENV
        );

        $route = new Zend_Controller_Router_Route(
            '/image/:profile/:routeMap',
            $defaultsRoutes
        );

        $frontController->getRouter()->addRoute(
            'image',
            $route
        );

        Zend_Registry::set(
            'imageCacheConfig',
            $imageCacheConfig
        );

    }

}