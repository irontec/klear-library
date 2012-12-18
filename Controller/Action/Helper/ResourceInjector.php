<?php
/**
 * Resource Injector Helper
 *
 * Allows easy resource access from Controller Actions.
 *
 * It needs a "$dependencies" attribute in the Controller to specify which resources will be used.
 *
 * Taken from: http://mwop.net/blog/235-A-Simple-Resource-Injector-for-ZF-Action-Controllers.html
 *
 */
class Iron_Controller_Action_Helper_ResourceInjector extends Zend_Controller_Action_Helper_Abstract
{
    protected $_resources;

    public function preDispatch()
    {
        $bootstrap  = $this->getBootstrap();
        $controller = $this->getActionController();

        if (!isset($controller->dependencies)
            || !is_array($controller->dependencies)
        ) {
            return;
        }
        foreach ($controller->dependencies as $name) {
            if (!$bootstrap->hasResource($name)) {
                throw new Exception("Unable to find dependency by name '$name'");
            }
            $controller->$name = $bootstrap->getResource($name);
        }
    }

    public function getBootstrap()
    {
        return $this->getFrontController()->getParam('bootstrap');
    }
}