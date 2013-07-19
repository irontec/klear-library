<?php
/**
 * Implementación de Zend_Cache_Backend_File que permite un uso más
 * liviano de memoria. Permite jugar con rutas de ficheros para evitar
 * cargar continuamente el contenido en memoria
 *
 * Requiere settear 'write_control' a false en el frontend
 * o bien hacer una implementación custom de Zend_Cache_Core
 *
 * Ejemplo de uso:
 *
 *    Zend_Cache::factory(
 *       'Core',
 *       new Iron_Cache_Backend_File(
 *           array(
 *               'cache_dir' => APPLICATION_PATH . '/envios',
 *           )
 *       ),
 *       array(
 *           'lifetime' => null,
 *           'automatic_cleaning_factor' => 0,
 *           'automatic_serialization' => false,
 *           'write_control' => false
 *       )
 *    );
 */

class Iron_Cache_Backend_File extends Zend_Cache_Backend_File
{
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
     * Make a control key with the string containing datas
     *
     * @param  string $data        Data or filePath
     * @param  string $controlType Type of control 'md5', 'crc32' or 'strlen'
     * @throws Zend_Cache_Exception
     * @return string Control key
     */
    protected function _hash($data, $controlType)
    {
        if ($this->_validPathSintax($data) && file_exists($data)) {

            return $this->_hashFileData($data, $controlType);
        }

        return $this->_hashData($data, $controlType);
    }

    /**
     * Make a control key with the string containing datas
     *
     * @param  string $data        Data
     * @param  string $controlType Type of control 'md5', 'crc32', 'strlen' or 'adler32'
     * @throws Zend_Cache_Exception
     * @return string Control key
     */
    protected function _hashData($data, $controlType)
    {
        switch ($controlType) {
            case 'md5':
                return md5($data);
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
     * Make a control key with the string containing file path
     *
     * @param  string $filePath File complete path
     * @param  string $controlType Type of control 'md5', 'crc32', 'strlen' or 'adler32'
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

    /**
     * @return boolean
     */
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

    /**
     * @return boolean
     */
    protected function _fileCopy($filePathDestino, $filePathOrigen)
    {
        return copy($filePathOrigen, $filePathDestino);
    }
}