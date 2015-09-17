<?php
/**
 *
 * @author ddniel16 <daniel@irontec.com>
 */
class Iron_Controller_Action_Helper_Templates extends Zend_Controller_Action_Helper_Abstract
{

    public function getCustomTempate()
    {

        $zendView = new \Zend_View();
        $zendView->layout()->render();

        $this->_front = Zend_Controller_Front::getInstance();

        $bootstrap = $this->_front->getParam('bootstrap');

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        if (null === $viewRenderer->view) {
            $viewRenderer->initView();
        }

        $mustache = $bootstrap->getResource('Mustache');
        $viewRenderer->setView($mustache->setDataTemlate($bootstrap->view));

    }

    public function direct()
    {
        return $this->getCustomTempate();
    }

}