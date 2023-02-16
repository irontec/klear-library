<?php
/**
 * @author ddniel16 <dani@irontec.com>
 *
 */
class Iron_Auth_RestSession extends Zend_Controller_Plugin_Abstract
{

    public function __construct()
    {

    }

    public function authenticate($mapper)
    {
        $sessionName = null;
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        
        if (!is_null($bootstrap)) {
            $config = (Object) $bootstrap->getOptions();
        } else {
            $conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
            $config = (Object) $conf->toArray();
        }

        if (isset($config->auth['session']["name"])) {
            $sessionName = $config->auth['session']["name"];
        }
        
        $auth = Zend_Auth::getInstance();
        $authStorage = new \Zend_Auth_Storage_Session($sessionName);
        $auth->setStorage($authStorage);
        
        $identity = $auth->getIdentity();
        
        if (is_null($identity)) {
            $this->_errorAuth();
            return;
        }
        
        $id = $identity->getId();
        $user = $mapper->find($id);

        return $user;
    }

    /**
     * Mensaje de error en la autenticación.
     */
    protected function _errorAuth(): never
    {

        $front = Zend_Controller_Front::getInstance();

        $resutl = array(
            'success' => false,
            'message' => 'Authorization incorrecta'
        );

        $response = $front->getResponse();
        $response->setHttpResponseCode(401);
        $response->setBody(json_encode($resutl));
        $response->sendResponse();
        exit();

    }

}