<?php
require_once('Zend/Controller/Plugin/Abstract.php');

/**
 * Plugin megasimple que sirve para desactivar el Layout en las peticiones Ajax
 * @author Alayn Gortazar <alayn+karma@irontec.com>
 *
 */
class Iron_Controller_Plugin_AjaxLayout extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        if (($request->isXmlHttpRequest()) &&
            ($layout = Zend_Layout::getMvcInstance()) ) {

            $layout->disableLayout();
        }
    }
}