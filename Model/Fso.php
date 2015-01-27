<?php
/***
 * File system object
 * FIXME: Hacer funcionar correctamente fetch y remove.
*/
class Iron_Model_Fso
{
    const CLASS_ATTR_SEPARATOR = '.';

    protected $_model;
    protected $_modelSpecs;
    protected $_basePath = '';

    protected $_srcFile;
    protected $_size = null;
    protected $_mimeType;
    protected $_baseName = '';
    protected $_md5Sum = '';

    protected $_mustFlush = false;

    public function __construct($model, $specs)
    {
        $storagePath = $this->_getLocalStorage($this->_getConfig());

        $this->_model = $model;
        $this->_modelSpecs = $specs;

        $modelClassName = $this->_getModelClassName();
        $modelAttrPath = strtolower($modelClassName . self::CLASS_ATTR_SEPARATOR . $specs['basePath']);
        $this->_basePath = $storagePath . $modelAttrPath;
    }

    protected function _getConfig()
    {
        $bootstrap = \Zend_Controller_Front::getInstance()->getParam('bootstrap');

        if (is_null($bootstrap)) {
            $conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        } else {
            $conf = (Object) $bootstrap->getOptions();
        }

        return $conf;
    }

    protected function _getLocalStorage($conf)
    {
        if (isset($conf->localStoragePath)) {

            $storagePath = $conf->localStoragePath;
            if (substr($storagePath, -1) != DIRECTORY_SEPARATOR) {
                $storagePath .= DIRECTORY_SEPARATOR;
            }

            return $storagePath;
        }
        return APPLICATION_PATH . '/../storage/';
    }

    protected function _getModelClassName()
    {
        return str_replace('\\', '_', get_class($this->_model));
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function getBaseName()
    {
        return $this->_baseName;
    }

    public function getMimeType()
    {
        return $this->_mimeType;
    }

    public function getMd5Sum()
    {
        return $this->_md5Sum;
    }

    /**
     * Prepara el módelo para poder guardar el fichero pasado como parámetro.
     * No guarda el fichero, lo prepara para guardarlo al llamar a flush
     * @var string $file Ruta al fichero
     * @return Iron_Model_Fso
     *
     * TODO: Comprobar que el $model implementa todo lo necesario para ser un módelo válido para ¿KlearMatrix?
     */
    public function put($file)
    {
        if (empty($file) or !file_exists($file)) {
            throw new Exception('File not found');
        }

        $this->setBaseName(basename($file));
        $this->_setSrcFile($file);
        $this->_setSize(filesize($file));
        $this->_setMimeType($file);
        $this->_setMd5Sum($file);
        $this->_updateModelSpecs();
        $this->_mustFlush = true;
        return $this;
    }

    public function setBaseName($name)
    {
        $this->_baseName = $name;
        return $this;
    }

    protected function _setSrcFile($filepath)
    {
        $this->_srcFile = $filepath;
        return $this;
    }

    protected function _setSize($size)
    {
        $this->_size = $size;
        return $this;
    }

    protected function _setMimeType($file)
    {
        if (!is_null($file)) {

            $finfo = new finfo(FILEINFO_MIME);
            if ($finfo) {
                $this->_mimeType = $finfo->file($file);
            }
        }

        return $this;
    }

    public function _setMd5Sum($file)
    {
        $this->_md5Sum = md5_file($file);
        return $this;
    }

    protected function _updateModelSpecs()
    {
        $sizeSetter = 'set' . $this->_modelSpecs['sizeName'];
        $mimeSetter = 'set' . $this->_modelSpecs['mimeName'];
        $nameSetter = 'set' . $this->_modelSpecs['baseNameName'];

        $this->_model->{$sizeSetter}($this->getSize());
        $this->_model->{$mimeSetter}($this->getMimeType());
        $this->_model->{$nameSetter}($this->getBaseName());

        if (isset($this->_modelSpecs['md5SumName'])) {

            $md5Setter = 'set' . $this->_modelSpecs['md5SumName'];

            if (method_exists($this->_model, $md5Setter)) {
                $this->_model->{$md5Setter}($this->getMd5Sum());
            }
        }
    }

    /**
     * @return Iron_Model_Fso
     */
    public function flush($pk)
    {
        if (!$this->mustFlush()) {
            throw new Exception('Nothing to flush');
        }

        if (!$this->_isValidPk($pk)) {
            throw new Exception('Invalid Primary Key');
        }

        $targetPath = $this->_basePath . DIRECTORY_SEPARATOR . $this->_pk2path($pk);

        $targetFile = $targetPath . $pk;

        if (!file_exists($targetPath)) {
            if (!mkdir($targetPath, 0755, true)) {
                throw new Exception('Could not create dir ' . $targetPath);
            }
        }

        $srcFileSize = filesize($this->_srcFile);

        if ($this->getSize() != $srcFileSize) {
            unlink($this->_srcFile);
            throw new Exception('Something went wrong. New filesize: ' . $srcFileSize . '. Expected: ' . $this->getSize());
        }

        if (true === copy($this->_srcFile, $targetFile)) {
            unlink($this->_srcFile);
        } else {
            throw new Exception("Could not rename file " . $this->_srcFile . " to " . $targetFile);
        }

        $this->_mustFlush = false;
        return $this;
    }

    /**
     * True if a new physic file has been set but is not still saved.
     * @return boolean
     */
    public function mustFlush()
    {
        return $this->_mustFlush;
    }

    /**
     * Converts id to path:
     *  1 => 0/1
     *  10 => 1/10
     *  15 => 1/15
     *  214 => 2/1/214
     * @return string
     */
    protected function _pk2path($pk)
    {

        if (!is_numeric($pk)) {
            return implode(DIRECTORY_SEPARATOR, array($pk)) . DIRECTORY_SEPARATOR;
        }

        $aId = str_split((string)$pk);
        array_pop($aId);
        if (!sizeof($aId)) {
            $aId = array('0');
        }

        return implode(DIRECTORY_SEPARATOR, $aId) . DIRECTORY_SEPARATOR;

    }

    /**
     * Prepara el módelo para permitir la descarga del fichero llamando a getBinary()
     * @return Iron_Model_Fso
     */
    public function fetch()
    {
        $pk = $this->_model->getPrimaryKey();
        if (!$this->_isValidPk($pk) ) {
            throw new Exception("Empty object. No PK found");
        }

        $file = $this->_basePath . DIRECTORY_SEPARATOR . $this->_pk2path($pk) . $pk;
        if (!file_exists($file)) {
            throw new Exception("File $file not found");
        }

        $this->_setSize(filesize($file));
        $this->_setSrcFile($file);
        $this->_setMimeType($file);
        $this->_setMd5Sum($file);

        $baseNameGetter = 'get' . ucfirst($this->_modelSpecs['baseNameName']);
        $this->setBaseName($this->_model->$baseNameGetter());

        return $this;
    }

    public function remove()
    {
        $pk = $this->_model->getPrimaryKey();

        if (!$this->_isValidPk($pk)) {
            throw new Exception('Empty object. No PK found');
        }

        $file = $this->_basePath . DIRECTORY_SEPARATOR . $this->_pk2path($pk) . $pk;

        if (file_exists($file)) {
            unlink($file);
        } else {
            //TODO: loggear que el fichero que se intenta borrar no existe...
        }

        $this->_size = null;
        $this->_mimeType = null;
        $this->_binary = null;

        $this->_updateModelSpecs();

        return $this;
    }

    public function getBinary()
    {
        return file_get_contents($this->_srcFile);
    }

    public function getFilePath()
    {
        return $this->_srcFile;
    }

    protected function _isValidPk($pk)
    {
        if (is_numeric($pk)) {
            return true;
        }

        if (count(explode("-", $pk)) == 5) {
            return true;
        }

        return false;
    }
}
