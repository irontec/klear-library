<?php
/***
 * File system object
*/
class Iron_Model_Fso_Adapter_StoragePathResolver_Default implements Iron_Model_Fso_Adapter_StoragePathResolver_Interface
{
    const CLASS_ATTR_SEPARATOR = '.';

    protected $_model;
    protected $_primaryKey;

    protected $_modelSpecs;

    protected $_localStoragePath;
    protected $_modifiers = array(
        'keepExtension' => false,
        'storeInBaseFolder' => false,
        'uniqueBaseName' => false,
    );

    /**
     * @var obj $model
     * @var array $modelSpecs
     * @var array $modifiers
     */
    public function __construct($model, $modelSpecs, $localStoragePath, $modifiers = array())
    {
        $this->setModel($model)
             ->setModelSpecs($modelSpecs)
             ->setLocalStoragePath($localStoragePath)
             ->setModifiers($modifiers);
    }

    public function setModel($model)
    {
        $this->_model = $model;
        $this->setPrimaryKey($model->getPrimaryKey());
        return $this;
    }

    public function setPrimaryKey($pk)
    {
        $this->_primaryKey = $pk;
        return $this;
    }

    public function setLocalStoragePath($path)
    {
        if (empty($path)) {
            throw new \Exception("Local storage path cannot be empty");
        }

        $this->_localStoragePath = $path;
        return $this;
    }

    public function setModelSpecs(array $modelSpecs)
    {
        $this->_modelSpecs = $modelSpecs;
        return $this;
    }

    /**
     * @param array $modifiers
     */
    public function setModifiers(array $modifiers)
    {
        foreach ($modifiers as $name => $value) {
            $this->setModifier($name, $value);
        }
        return $this;
    }

    public function setModifier($name, $value)
    {
        if (!array_key_exists($name, $this->_modifiers)) {
            throw new \Exception("Unknown path resolver modifier: " . $name);
        }

        $this->_modifiers[$name] = $value;
    }

    public function getPath()
    {
        if (!$this->isResoluble()) {
            throw new Exception('File path not resoluble!!');
        }

        $path = array(
            $this->_buildStoragePath(),
            $this->_buildBasePath(),
            $this->_buildFileTree(),
            $this->_buildRealBaseName()
        );
        // Eliminamos elementos vacíos
        $path = array_filter($path);

        $filePath = implode(DIRECTORY_SEPARATOR, $path);
        $this->_buildDirectoryTree($filePath);

        return $filePath;
    }

    public function isResoluble()
    {
        return
        is_object($this->_model) &&
        is_array($this->_modelSpecs);
    }

    protected function _buildStoragePath()
    {
        $storagePath = $this->_localStoragePath;
        if (substr($storagePath, -1) === DIRECTORY_SEPARATOR) {
            $storagePath = substr($storagePath,0,-1);
        }

        return $storagePath;
    }

    protected function _buildBasePath()
    {
        $modelClassName = str_replace('\\', '_', get_class($this->_model));

        return strtolower(
                $modelClassName .
                    self::CLASS_ATTR_SEPARATOR .
                        $this->_modelSpecs['basePath']);

    }

    protected function _buildFileTree()
    {

        if (
            isset($this->_modifiers['storeInBaseFolder']) &&
            true === $this->_modifiers['storeInBaseFolder']
        ) {
            return null;
        }

        $pk = $this->_model->getPrimaryKey();
        return $this->_pk2path($pk);
    }

    protected function _buildRealBaseName()
    {
        $pk = $this->_model->getPrimaryKey();

        if(empty($pk)) {
            throw new \Exception("Cannot build filepath before it has a primary key");
        }

        if ($this->_modifiers['keepExtension'] !== false) {
            $baseNamegetter = 'get' . $this->_modelSpecs['baseNameName'];
            $ext = '.' . pathinfo($this->_model->{$baseNamegetter}(), PATHINFO_EXTENSION);
        } else {
            $ext = '';
        }

        return $pk . $ext;
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
        if (preg_match("/^([0-9a-f]{8})\-([0-9a-f]{4})\-([0-9a-f]{4})\-([0-9a-f]{4})\-[0-9a-f]{12}$/i", $pk, $result)) {

            return $result[1] . DIRECTORY_SEPARATOR .
                     $result[2] . DIRECTORY_SEPARATOR .
                       $result[3] . DIRECTORY_SEPARATOR .
                         $result[4];
        }

        if (is_numeric($pk)) {

            $aId = str_split((string)$pk);
            array_pop($aId);
            if (!sizeof($aId)) {
                $aId = array('0');
            }

            return implode(DIRECTORY_SEPARATOR, $aId) . DIRECTORY_SEPARATOR;
        }

        throw new \Exception("unsupported pk received!");
    }

    protected function _buildDirectoryTree($filePath)
    {
        $targetDir = dirname($filePath);
        $filePathParts = explode(DIRECTORY_SEPARATOR, $targetDir);
        $mkdirMode = $this->_getMkdirMode();

        $currentDir = "";
        foreach ($filePathParts as $dir) {
            $currentDir = $currentDir. DIRECTORY_SEPARATOR. $dir;
            if (!file_exists($currentDir)) {
                if (!@mkdir($currentDir, $mkdirMode, true)) {
                    if (!file_exists($currentDir)) {
                        throw new Exception('Could not create dir ' . $currentDir);
                    }
                } else {
                    chmod($currentDir, $mkdirMode);
                }
            }
        }
    }

    protected function _getMkdirMode()
    {
        $mkdirMode = "0755";

        $conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        $applicationIni = $conf->toArray();

        if (isset($applicationIni['Iron']['fso']['localStorageChmod'])) {
            $mkdirMode = $applicationIni['Iron']['fso']['localStorageChmod'];
        }

        return octdec($mkdirMode);
    }

    protected function _getAppConfig()
    {
        $bootstrap = \Zend_Controller_Front::getInstance()->getParam('bootstrap');
        if (is_null($bootstrap)) {
            $conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        } else {
            $conf = (Object) $bootstrap->getOptions();
        }

        return $conf;
    }
}
