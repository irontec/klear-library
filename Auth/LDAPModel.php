<?php
/**
 * Model la clase Iron\Auth\LDAP
 */
namespace Iron\Auth;

class LDAPModel
{

    /**
     * Configuración de conexión con LDAP
     * @var array
     */
    protected $_ldapConf = null;

    protected $_host;
    protected $_port;
    protected $_usersBasedn;
    protected $_usersFilterKey;
    protected $_groupsFilter;
    protected $_groupsBasedn;
    protected $_applicationUser;
    protected $_applicationPassword;
    protected $_connected = false;

    public function getConnected()
    {
        return $this->_connected;
    }

    public function setConnected($connected)
    {
        $this->_connected = $connected;
        return $this;
    }

    public function getHost()
    {
        return $this->_host;
    }

    public function getPort()
    {
        return $this->_port;
    }

    public function getUsersBasedn()
    {
        return $this->_usersBasedn;
    }

    public function getUsersFilterKey()
    {
        return $this->_usersFilterKey;
    }

    public function getGroupsFilter()
    {
        return $this->_groupsFilter;
    }

    public function getGroupsBasedn()
    {
        return $this->_groupsBasedn;
    }

    public function getApplicationUser()
    {
        return $this->_applicationUser;
    }

    public function getApplicationPassword()
    {
        return $this->_applicationPassword;
    }

    public function setHost($_host)
    {
        $this->_host = $_host;
    }

    public function setPort($_port)
    {
        $this->_port = $_port;
    }

    public function setUsersBasedn($_usersBasedn)
    {
        $this->_usersBasedn = $_usersBasedn;
    }

    public function setUsersFilterKey($usersFilterKey)
    {
        $this->_usersFilterKey = $usersFilterKey;
    }

    public function setGroupsFilter($groupsFilter)
    {
        $this->_groupsFilter = $groupsFilter;
    }

    public function setGroupsBasedn($groupsBasedn)
    {
        $this->_groupsBasedn = $groupsBasedn;
    }

    public function setApplicationUser($applicationUser)
    {
        $this->_applicationUser = $applicationUser;
    }

    public function setApplicationPassword($applicationPassword)
    {
        $this->_applicationPassword = $applicationPassword;
    }

    public function setLdapConf($ldapConf)
    {
        $this->_ldapConf = $ldapConf;
        return $this;
    }

    public function getLdapConf($data = null)
    {

        if (is_null($data)) {
            return $this->_ldapConf;
        }

        if (!is_null($this->_ldapConf->$data)) {
            return $this->_ldapConf->$data;
        }

        return false;

    }

}