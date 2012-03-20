<?php
require_once('Zend/Controller/Action/Helper/ContextSwitch.php');

/**
 * @author Alayn Gortazar <alayn+karma@irontec.com>
 *
 * Helper que extiende el ContextSwitch y se encarga de convertir en arrays todos los objetos de la vista
 * que contengan el método "toArray" siempre y cuando se encuentre bajo el contexto "Json"
 *
 */
class Iron_Controller_Action_Helper_IronContextSwitch extends Zend_Controller_Action_Helper_ContextSwitch
{
    /**
     * JSON post processing
     *
     * JSON serialize view variables to response body
     *
     * @return void
     */
    public function postJsonContext()
    {
        if (!$this->getAutoJsonSerialization()) {
            return;
        }
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view = $viewRenderer->view;
        if ($view instanceof Zend_View_Interface) {
            /**
             * @see Zend_Json
             */
            if(method_exists($view, 'getVars')) {

                $viewVars = $view->getVars();
                foreach ($viewVars as $name => $param) {
                    if (is_array($param)) {
                        foreach ($param as $arName => $arValue) {
                            $viewVars[$name][$arName] = $this->_normalizeParam($arValue);
                        }
                    } else {
                        $viewVars[$name] = $this->_normalizeParam($param);
                    }
                }

                require_once 'Zend/Json.php';
                $vars = Zend_Json::encode($viewVars);
                $this->getResponse()->setBody($vars);
            } else {
                require_once 'Zend/Controller/Action/Exception.php';
                throw new Zend_Controller_Action_Exception('View does not implement the getVars() method needed to encode the view into JSON');
            }
        }
    }

    protected function _normalizeParam($param)
    {
        if (is_object($param) && method_exists($param, 'serializeData')) {
            return $param->serializeData();
       }
       return $param;
    }
}