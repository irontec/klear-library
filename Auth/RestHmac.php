<?php
/**
 *
 * Validación de autenticación REST con en metodo Hmac
 * @author ddniel16 <dani@irontec.com>
 */
class Iron_Auth_RestHmac extends Zend_Controller_Plugin_Abstract
{

    protected $_life = 36000;

    public function __construct()
    {

    }

    /**
     *
     * @param $token
     * @param $requestDate
     * @param $mapper
     * @param Array $getData user and pass check validation
     * @return $model | JSON error
     */
    public function authenticate(
        $token,
        $requestDate,
        $mapper,
        $getData = array()
    )
    {

        if (!$requestDate) {
            $this->_errorAuth();
        }

        try {
            $dateHash = new \Zend_Date($requestDate);
            $dateHash->setTimezone('UTC');
            $dateHash = $dateHash->toString(Zend_Date::W3C);
        } catch (\Exception $e) {
            $this->_errorAuth($e->getMessage());
        }

        $date = new \Zend_Date();
        $date->setTimezone('UTC');
        $nowUTC = $date->toString(Zend_Date::W3C);

        if (!$this->_validDate($nowUTC, $requestDate)) {
            $this->_errorAuth('Token Expired');
        }

        if (empty($getData)) {
            $getData = array(
                'user' => 'username',
                'pass' => 'pass'
            );
        }

        $tokenParts = explode(':', $token);

        if (sizeof($tokenParts) !== 3) {
            $this->_errorAuth();
        }

        $userName = $tokenParts[0];
        $secret = $tokenParts[1];
        $digest = $tokenParts[2];

        $user = $mapper->findOneByField(
            $getData['user'],
            $userName
        );

        if (empty($user)) {
            $this->_errorAuth();
        }

        $tokenKey = $user->getTokenKey();
        if (empty($tokenKey)) {
            $this->_errorAuth();
        }

        $serverDigest = hash_hmac(
            'sha256',
            $tokenKey . '+' . $requestDate . '+' . $secret,
            $tokenKey
        );

        $digest = trim($digest, '[');
        $digest = trim($digest, ']');

        if ($digest !== $serverDigest) {
            $this->_errorAuth();
        }

        return $user;

    }

    /**
     * Comprueba que el token este en una fecha valida.
     * @param Zend_Date $nowUTC
     * @param Zend_Date $dateHash
     * @return boolean
     */
    protected function _validDate($nowUTC, $dateHash)
    {
        $timeHash = strtotime($dateHash);
        $timeNow = strtotime($nowUTC);

        $diff = $timeNow - $timeHash;

        if ($diff > $this->_life || $diff < (-$this->_life)) {
            return false;
        }

        return true;

    }

    /**
     * Mensaje de error en la autenticación.
     */
    protected function _errorAuth($msg = 'Authorization incorrecta')
    {

        $front = Zend_Controller_Front::getInstance();

        $resutl = array(
            'success' => false,
            'message' => $msg
        );

        $response = $front->getResponse();
        $response->setHttpResponseCode(401);
        $response->setBody(json_encode($resutl));
        $response->sendResponse();
        exit();

    }

}