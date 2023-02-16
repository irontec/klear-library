<?php
/**
 * Autenticación con Asterisk de Ivoz
 */

namespace Iron\Auth;

/**
 * Clase para la autenticación con Asterisk de Ivoz.
 * Este login se esta usando en las apps de CTS
 * @author ddniel16 <dani@irontec.com>
 */
class Asterisk
{

    protected $_db;
    protected $_user = NULL;
    protected $_password = NULL;

    /**
     * @param array $connectOptions Parametros para conectar con el MySQL del Asterisk
     */
    public function __construct($connectOptions)
    {
        $this->_db = new \Zend_Db_Adapter_Pdo_Mysql($connectOptions);
        $this->_db->getConnection();
    }

    /**
     * Termina la conección con Asterisk
     */
    public function __destruct()
    {
        $this->_db->closeConnection();
    }

    /**
     * @param int $user Extención del usuario.
     * @return \Iron\Auth\Asterisk
     */
    public function setUser($user)
    {
        $this->_user = $user;
        return $this;
    }

    /**
     * @param string $password Contraseña de usuario.
     * @return \Iron\Auth\Asterisk
     */
    public function setPassword($password)
    {
        $this->_password = $password;
        return $this;
    }

    public function getUser()
    {
        return $this->_user;
    }

    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Comprueba que se a podido establecer conección con MySQL y que se han
     * definido el user y la password.
     * Si faltan parametros, no hay conección o la actenticación es incorrecta
     * se devuelve FALSE.
     * Si hay una autenticación correcta devuelve el nombre y apellido.
     * @return boolean|array
     */
    public function authenticate(): bool|array
    {

        if ($this->getUser() === NULL || $this->getPassword() === NULL) {
            return false;
        }

        if ($this->_db->isConnected() === false) {
            return false;
        }

        $fields = 'nombre, apellidos';
        $join = 'INNER JOIN shared_agents_interfaces AS s ON k.login_num = s.agent';
        $where = sprintf(
            'login_num = "%s" AND pass = encrypt("%s" , SUBSTRING_INDEX(pass, "$", 3))',
            $this->getUser(),
            $this->getPassword()
        );

        $query = sprintf(
            'SELECT %s FROM karma_usuarios AS k %s WHERE %s',
            $fields,
            $join,
            $where
        );

        $result = $this->_db->fetchAssoc($query);
        if (empty($result)) {
            return false;
        }

        $userResult = reset($result);
        return $userResult;

    }

}