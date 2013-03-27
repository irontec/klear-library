<?php
/**
 *
 *
 * @author Lander Ontoria Gardeazabal <lander+dev@irontec.com>
 *
 */
class Iron_Auth_PublicAdapter extends Zend_Controller_Plugin_Abstract
{
    
    protected $_usernameFieldName;
    protected $_passwordFieldName;
    protected $_username;
    protected $_password;
    protected $_pk;
    
    protected $_userMapperName;
    protected $_userMapper;
    
    
    
    public function __construct(Zend_Controller_Request_Abstract $request, $authConfig)
    {
        $this->_userMapperName = $authConfig['userMapper'];
        $this->_usernameFieldName = $authConfig['username'];
        $this->_passwordFieldName = $authConfig['password'];
        
        $this->_username = $request->getPost($authConfig['username'], '');
        $this->_password = $request->getPost($authConfig['password'], '');
        
        $this->_initUserMapper();
    }
    
    protected function _initUserMapper()
    {
        $this->_userMapper = new $this->_userMapperName;
    }
    
    public function authenticate()
    {
        try {
            $user = $this->_userMapper->findByLogin($this->_username);
            
            if ($this->_userHasValidCredentials($user)) {
                $this->_user = $user;
                $authResult = Zend_Auth_Result::SUCCESS;
                $authMessage = array("message"=>"Welcome!");
    
            } else {
                $authResult = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
                $authMessage = array("message"=>"Usuario o contraseña incorrectos.");
            }
    
            return new Zend_Auth_Result($authResult, $this->_username, $authMessage);
        } catch (Exception $e) {
    
            $authResult = Zend_Auth_Result::FAILURE_UNCATEGORIZED;
            $authMessage['message'] = $e->getMessage();
            return new Zend_Auth_Result($authResult, $this->_username, $authMessage);
        }
    }
    
    protected function _userHasValidCredentials($user = null)
    {
        if (!is_null($user)) {
            $hash = $user->{'get' . ucfirst($this->_passwordFieldName)}();
            if ($this->_checkPassword($this->_password, $hash)) {
                return true;
            }
        }
        return false;
    }
    
    protected function _checkPassword($clearPass, $hash)
    {
        $hashParts = explode('$', trim($hash, '$'), 2);
        switch ($hashParts[0]) {
            case '1': //md5
                list(,,$salt,) = explode("$", $hash);
                $salt = '$1$' . $salt . '$';
                break;
    
            case '5': //sha
                list(,,$rounds,$salt,) = explode("$", $hash);
                $salt = '$5$' . $rounds . '$' . $salt . '$';
                break;
    
            case '2a': //blowfish
                $salt = substr($hash, 0, 29);
                break;
        }
    
        $res = crypt($clearPass, $salt . '$');
        return $res == $hash;
    }
    
    public function saveStorage()
    {
        $auth = Zend_Auth::getInstance();
        $authStorage = $auth->getStorage();
        $authStorage->write($this->_user);
    }

}