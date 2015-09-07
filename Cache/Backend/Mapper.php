<?php
/**
 *
 * @author ddniel16 <dani@irontec.com>
 */
namespace Iron\Cache\Backend;
class Mapper
{

    protected $_namespace;
    protected $_frontend = array(
        'lifetime' => 1800,
        'automatic_serialization' => true
    );

    public function __construct($frontend = array(), $backend = array())
    {

        $front = \Zend_Controller_Front::getInstance();
        $this->_namespace = $front
            ->getParam('bootstrap')
            ->getOption('appNamespace');

        if (empty($frontend)) {
            $frontend = $this->_frontend;
        } else {
            $frontend = array_replace($this->_frontend, $frontend);
        }

        $defaultBackend = array(
            'cache_dir' => APPLICATION_PATH . '/cache/',
            'file_name_prefix' => $this->_namespace,
            'read_control_type' => 'adler32'
        );

        if (empty($backend)) {
            $backend = $defaultBackend;
        } else {
            $backend = array_replace($defaultBackend, $backend);
        }

        $cache = \Zend_Cache::factory(
            'Core',
            'File',
            $frontend,
            $backend
        );

        $this->_cache = $cache;

    }

    public function setEtagVersions($etagsList)
    {

        $token = 'EtagVersion';
        $this->_cache->save($etagsList, $token);

    }

    public function getEtagVersions($table)
    {

        $token = 'EtagVersion';
        $etags = $this->_cache->load($token);

        if ($etags !== false) {
            foreach ($etags as $etag) {
                if ($etag->getTable() === $table) {
                    return $etag->getEtag();
                }
            }
        }

        return $this->_etagChange($table);

    }

    public function getData($etag)
    {

        $data = $this->_cache->load($etag);
        return $data;

    }

    public function saveData($data, $etag)
    {

        $this->_cache->save($data, $etag);
        return $data;

    }

    public function clean()
    {

        $cache = Zend_Registry::get('cache');
        $cache->clean();
        $cache->remove('cache');
    }

    protected function _etagChange($table)
    {

        $date = new \Zend_Date();
        $date->setTimezone('UTC');
        $nowUTC = $date->toString('yyyy-MM-dd HH:mm:ss');

        $mappper = $this->_namespace . "\\Model\\EtagVersions";
        $etag = new $mappper();
        $etag->setTable($table);

        $random = substr(
            str_shuffle(
                str_repeat(
                    'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
                    5
                )
            ), 0, 5
        );

        $tokenEtag = md5($nowUTC . $random);

        $etag->setEtag($tokenEtag);
        $etag->setLastChange($nowUTC);
        $etag->save();

        return $tokenEtag;

    }

}