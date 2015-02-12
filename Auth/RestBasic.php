<?php
/**
 * @author ddniel16 <dani@irontec.com>
 *
 */
class Iron_Auth_RestBasic extends Zend_Controller_Plugin_Abstract
{

    public function __construct()
    {

    }

    public function authenticate($token, $mapper, $getData)
    {

        $tokenDecode = base64_decode($token);

        $userData = explode(':', $tokenDecode);

        if (sizeof($userData) !== 2) {
            $this->_errorAuth();
        }

        $username = $userData[0];
        $password = $userData[1];

        $getPassword =  'get' . ucfirst($getData['pass']);

        $user = $mapper->findOneByField(
            $getData['user'],
            $username
        );

        if (empty($user)) {
            $this->_errorAuth();
        }

        $checkPass = $this->_checkPassword(
            $password,
            $user->$getPassword()
        );

        if (!$checkPass) {
            $this->_errorAuth();
        }

    }

    /**
     * Comprueba que el password del token coinsida con el del usuario.
     * @param String $clearPass
     * @param String $hash
     * @return boolean
     */
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

    /**
     * Mensaje de error en la autenticación.
     */
    protected function _errorAuth()
    {

        $resutl = array(
            'success' => false,
            'message' => 'Authorization incorrecta'
        );

        $response = $this->getResponse();
        $response->setHttpResponseCode(401);
        $response->setBody(json_encode($resutl));
        $response->sendResponse();
        exit();

    }

}