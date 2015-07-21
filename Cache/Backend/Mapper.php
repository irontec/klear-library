<?php
/**
 *
 * @author ddniel16 <dani@irontec.com>
 */

class Iron_Cache_Backend_Mapper
{

    protected $_namespace;
    protected $_frontend = array(
        'lifetime' => 1800,
        'automatic_serialization' => true
    );

    public function __construct($frontend = array(), $backend = array())
    {

        $front = Zend_Controller_Front::getInstance();
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

        $cache = Zend_Cache::factory(
            'Core',
            'File',
            $frontend,
            $backend
        );

        Zend_Registry::set('cache', $cache);

    }

    public function getData($etag)
    {

        $cache = Zend_Registry::get('cache');
        $data = $cache->load(md5($etag));

        return $data;

    }

    public function saveData($data, $etag)
    {

        $cache = Zend_Registry::get('cache');
        $data = $cache->load(md5($etag));

        $cache->save($data);

        return $data;

    }

    public function clean()
    {

        $cache = Zend_Registry::get('cache');
        $cache->clean();
        $cache->remove('cache');
    }

}