<?php
/**
 * @author ddniel16 <daniel@irontec.com>
 */

class Fso_Bootstrap extends Zend_Application_Module_Bootstrap
{

    protected function _initFso()
    {

        $front = Zend_Controller_Front::getInstance();

        $fsoPlugin = new Fso_Plugin_Init();

        $front->registerPlugin($fsoPlugin);

    }

    protected function _initModuleRoutes()
    {

        $app = $this->getApplication();
        $ironModule = $app->getOption('IronModule');

        if (is_null($ironModule)) {
            return;
        }

        if (!isset($ironModule['fso'])) {
            return;
        }

        if ($ironModule['fso'] != true) {
            return;
        }

        $frontController = \Zend_Controller_Front::getInstance();

        $fsoPath = APPLICATION_PATH . '/configs/fso.ini';
        $defaultsRoutes = array(
            'controller' => 'index',
            'action' => 'index',
            'module' => 'fso',
            'profile' => NULL,
            'routeMap' => NULL
        );

        if (!file_exists($fsoPath)) {
            throw new Exception(
                'No Existe el fichelo de configuracion fso.ini',
                404
            );
        }

        $fsoConfig = new \Zend_Config_Ini(
            $fsoPath,
            APPLICATION_ENV
        );

        $route = new Zend_Controller_Router_Route(
            '/fso/:profile/:routeMap',
            $defaultsRoutes
        );

        $frontController->getRouter()->addRoute(
            'fso',
            $route
        );

        Zend_Registry::set(
            'fsoConfig',
            $fsoConfig
        );

    }

}