<?php
/**
 * Implementación de Zend_Cache_Backend_File que permite un uso más
 * liviano de memoria. Permite jugar con rutas de ficheros para evitar
 * cargar continuamente el contenido en memoria
 *
 *
 * Requiere de settear 'write_control' a false en el frontend
 * o bien hacer una implementación custom de Zend_Cache_Core
 */

class Iron_Cache_Backend_File extends Zend_Cache_Backend_File
{
    /**
     * Constructor
     *
     * @param  array $options associative array of options
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function __construct(array $options = array())
    {
        return parent::__construct($options);
    }

    /**
     * @return string | boolean
     */
    public function getCacheFilePath($id)
    {
        $filepath = $this->_file($id);

        if (! file_exists($filepath)) {

            return false;
        }

        return $filepath;
    }

    /**
     * @param  string| $data            Datas to cache or filePath
     * @param  string $id               Cache id
     * @param  array  $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean true if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        clearstatcache();
        $file = $this->_file($id);
        $path = $this->_path($id);
        if ($this->_options['hashed_directory_level'] > 0) {
            if (!is_writable($path)) {
                // maybe, we just have to build the directory structure
                $this->_recursiveMkdirAndChmod($id);
            }
            if (!is_writable($path)) {
                return false;
            }
        }

        if ($this->_options['read_control']) {
            $hash = $this->_hash($data, $this->_options['read_control_type']);
        } else {
            $hash = '';
        }

        $metadatas = array(
            'hash' => $hash,
            'mtime' => time(),
            'expire' => $this->_expireTime($this->getLifetime($specificLifetime)),
            'tags' => $tags
        );

        $res = $this->_setMetadatas($id, $metadatas);

        if (!$res) {
            $this->_log('Zend_Cache_Backend_File::save() / error on saving metadata');
            return false;
        }
        $res = $this->_filePutContents($file, $data);

        return $res;
    }

    /**
     * Make a control key with the string containing datas
     *
     * @param  string $data        Data or filePath
     * @param  string $controlType Type of control 'md5', 'crc32' or 'strlen'
     * @throws Zend_Cache_Exception
     * @return string Control key
     */
    protected function _hash($data, $controlType)
    {
        if (file_exists($data)) {

            return $this->_hashFileData($data, $controlType);
        }

        return $this->_hashData($data, $controlType);
    }

    /**
     * Make a control key with the string containing datas
     *
     * @param  string $data        Data
     * @param  string $controlType Type of control 'md5', 'crc32' or 'strlen'
     * @throws Zend_Cache_Exception
     * @return string Control key
     */
    protected function _hashData($data, $controlType)
    {
        switch ($controlType) {
            case 'md5':
                return file_exists($data) ? md5_file($data) : md5($data);
            case 'crc32':
                return crc32($data);
            case 'strlen':
                return strlen($data);
            case 'adler32':
                return hash('adler32', $data);
            default:
                Zend_Cache::throwException("Incorrect hash function : $controlType");
        }
    }

    /**
     * Make a control key with the string containing datas
     *
     * @param  string $data        Data
     * @param  string $controlType Type of control 'md5', 'crc32' or 'strlen'
     * @throws Zend_Cache_Exception
     * @return string Control key
     */
    protected function _hashFileData($filePath, $controlType)
    {
        switch ($controlType) {
            case 'md5':
                return md5_file($filePath);
            case 'crc32':
                $hash = hash_file('crc32b', $filePath);
                $array = unpack('N', pack('H*', $hash));
                return $array[1];
            case 'strlen':
                return filesize($filePath);
            case 'adler32':
                return hash_file('adler32', $filePath);
            default:
                Zend_Cache::throwException("Incorrect hash function : $controlType");
        }
    }

    /**
     * Put the given string into the given file
     *
     * @param  string $file   File complete path
     * @param  string $string String to put in file
     * @return boolean true if no problem
     */
    protected function _filePutContents($file, $data)
    {
        if ($this->_validPathSintax($data) && file_exists($data)) {

            return $this->_fileCopy($file, $data);

        } else {

            return parent::_filePutContents($file, $data);
        }
    }

    protected function _validPathSintax($path)
    {
        if (
            strpos($path, "{") !== false
            || strpos($path, ":") !== false
            || strpos($path, '"') !== false
        ) {

            return false;
        }

        return true;

    }

    protected function _fileCopy($filePathDestino, $filePathOrigen)
    {
        return copy($filePathOrigen ,$filePathDestino);
    }
}