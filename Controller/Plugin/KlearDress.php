<?php

/**
 * Plugin que sobre-escribe la petición a un / para que ésta vaya directamente a klear
 * (sin mostrar /klear en la URL; aunque si en el resto de peticiones por debajo)
 *
 */
class Iron_Controller_Plugin_KlearDress extends Zend_Controller_Plugin_Abstract
{
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        if ($request->getModuleName() == 'default' && $request->getControllerName() == 'index' ) {
            $request->setModuleName('klear')
                ->setControllerName('index')
                ->setDispatched(false);
        }

    }
}