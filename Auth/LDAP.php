<?php

namespace Iron\Auth;

class LDAP extends LDAPModel
{

    /**
     * Zend_Log SysLog
     * @var object
     */
    protected $_logger;

    /**
     * Zend_Ldap
     * @var object
     */
    protected $_ldap;

    protected $_userName;
    protected $_password;
    protected $_ad;
    protected $_config;


    public function __construct()
    {

        $params = array(
            array(
                'writerName' => 'Syslog',
                'writerParams' => array(
                    'facility' => LOG_SYSLOG,
                    'application' => 'Login-LDAP'
                )
            )
        );
        $this->_logger = \Zend_Log::factory($params);

        $this->_checkLdapSettings();

    }

    /**
     * Establece una conexión con LDAP
     *
     * Se comprueba que todos los parametros para establecer una conexión
     * esten definidos y luego se hace una conexión con el
     * ususario y contraseña
     *
     * @param string $userName
     * @param string $password
     */
    public function connect($userName, $password)
    {

        $options = array(
            'host' => $this->getHost(),
            'baseDn' => $this->getUsersBasedn(),
            'port' => $this->getPort(),
            'bindRequiresDn' => true,
            'accountFilterFormat' => '(&(cn=%s))'
        );

        $this->_ldap = new \Zend_Ldap($options);

        $errorMsg = 'Error al conectar con el Directorio Activo: ';
        $infoMsg  = 'Conectando con el Directorio Activo: ';

        if (empty($userName) || empty($password)) {
            $errorMsg .= 'Debes introducir nombre de usuario y contraseña';

            $this->_log($errorMsg, \Zend_Log::ERR);
            return false;
        }

        $this->_log(
            $infoMsg . 'Host: ' . $this->getHost() . ' Usuario: ' . $userName,
            \Zend_Log::INFO
        );

        try {

            $this->_ldap->bind($userName, $password);

            $this->_log(
                'Conexión realizada para el Usuario: ' . $userName,
                \Zend_Log::INFO
            );

            $this->setConnected(true);

        } catch (\Zend_Ldap_Exception $e) {
            $this->_log(
                $errorMsg . $e->getMessage(),
                \Zend_Log::ERR
            );
            $this->setConnected(false);

        }

        return $this->getConnected();

    }

    /**
     * Comprueba si existe el usuario.
     * @param string $user
     * @throws Exception
     * @return array
     */
    public function checkUser($user)
    {

        $search = 'cn=' . $user . ',' . $this->getUsersBasedn();
        $this->_log(
            'Buscando el usuario: ' . $user,
            \Zend_Log::INFO
        );

        $exists = $this->_ldap->exists($search);

        $this->_log('Busqueda finalizada', \Zend_Log::INFO);

        return $exists;


    }

    /**
     * Busca y devuelve la información del usuario
     * @param string $user
     * @throws \Exception
     * @return array
     */
    public function getUserInfo($user)
    {

        if (!$this->getConnected()) {
            throw new \Exception('No estás conectado.');
        }

        $this->_log(
            'Recopilando información del usuario ' . $user,
            \Zend_Log::INFO
        );

        $search = 'cn=' . $user . ',' . $this->getUsersBasedn();
        $user = $this->_ldap->getEntry($search);

        return $user;

    }

    /**
     * Devuelve toda la información del Grupo configurado
     * @return array
     */
    public function getGroupsInfo()
    {

        $results = $this->_ldap->search(
            $this->getGroupsFilter(),
            $this->getGroupsBasedn()
        );

        $info = $this->_ldap->getEntry($results);

        return $info;

    }

    /**
     * Conector con el logger de Zend
     * @param string $message
     * @param constant $priority \Zend_Log::INFO
     */
    protected function _log($message, $priority)
    {
        $this->_logger->log($message, $priority);
    }

    /**
     * Valida los parametros para establecer una conexión con LDAP
     * @throws \Exception
     */
    protected function _checkLdapSettings()
    {

        if (is_null($this->getLdapConf())) {

            $conf = new \Zend_Config_Ini(
                APPLICATION_PATH . '/configs/application.ini',
                APPLICATION_ENV
            );

            if (is_null($conf->ldap)) {
                throw new \Exception(
                    'No existe la configuración ldap en el application.ini'
                );
            }

            $this->setLdapConf($conf->ldap);

        }

        $errors = array();

        if (!$this->getLdapConf('host')) {
            $errors[] = "ldap.host";
        } else {
            $this->setHost($this->getLdapConf('host'));
        }

        if (!$this->getLdapConf('port')) {
            $errors[] = "ldap.port";
        } else {
            $this->setPort($this->getLdapConf('port'));
        }

        if (!$this->getLdapConf('users')) {
            $errors[] = 'ldap.users.basedn';
            $errors[] = 'ldap.users.filterKey';
        } else {

            $user = $this->getLdapConf('users');

            if (is_null($user->basedn)) {
                $errors[] = 'ldap.users.basedn';
            } else {
                $this->setUsersBasedn($user->basedn);
            }

            if (is_null($user->filterKey)) {
                $errors[] = 'ldap.users.filterKey';
            } else {
                $this->setUsersFilterKey($user->filterKey);
            }

        }

        if (!$this->getLdapConf('groups')) {
            $errors[] = 'ldap.groups.filter';
            $errors[] = 'ldap.groups.basedn';
        } else {

            $groups = $this->getLdapConf('groups');

            if (is_null($groups->filter)) {
                $errors[] = 'ldap.groups.filter';
            } else {
                $this->setGroupsFilter($groups->filter);
            }

            if (is_null($groups->basedn)) {
                $errors[] = 'ldap.groups.basedn';
            } else {
                $this->setGroupsBasedn($groups->basedn);
            }

        }

        if (!$this->getLdapConf('applicationUser')) {
            $errors[] = 'ldap.applicationUser.name';
            $errors[] = 'ldap.applicationUser.password';
        } else {

            $appUser = $this->getLdapConf('applicationUser');

            if (is_null($appUser->name)) {
                $errors[] = 'ldap.applicationUser.name';
            } else {
                $this->setApplicationUser($appUser->name);
            }

            if (is_null($appUser->password)) {
                $errors[] = 'ldap.applicationUser.password';
            } else {
                $this->setApplicationPassword($appUser->password);
            }

        }

        if (!empty($errors)) {

            $message = 'Faltan los siguientes ajustes en application.ini:';
            $this->_log($message, \Zend_Log::ERR);

            foreach ($errors as $error) {
                $this->_log($error, \Zend_Log::ERR);
                $message .= "\n" . $error;
            }

            throw new \Exception($message);

        }

    }

}