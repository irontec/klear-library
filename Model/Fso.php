<?php
/***
 * File system object
 * FIXME: Hacer funcionar correctamente fetch y remove.
*/
class Iron_Model_Fso
{
    protected $_model;
    protected $_modelSpecs;

    /**
     * @var string path to FSO file in FS
     */
    protected $_filePath;

    protected $_size = null;
    protected $_mimeType;
    protected $_baseName = '';
    protected $_md5Sum = '';

    protected $_fileToBeFlushed;
    protected $_mustFlush = false;

    /**
     * @var string previously stored file path
     */
    protected $_originalFilePath;

    /**
     * @var Iron_Model_Fso_Adapter_StoragePathResolver_Interface
     */
    protected $_pathResolverAdapter;

    public function __construct($model, $specs, $config = array())
    {
        $this->_model = $model;
        $this->_modelSpecs = $specs;

        $fsoConfiguration = $this->_buildConfiguration($config);
        $adapters = $fsoConfiguration['adapters'];

        $adapterInstances = $this->_adapterBuilder($model, $specs, $fsoConfiguration);
        if (isset($adapterInstances['storagePathResolver'])) {
            $this->setPathResolver($adapterInstances['storagePathResolver']);
        }
    }

    /**
     * @return array of adapters
     */
    protected function _adapterBuilder($model, $specs, $fsoConfiguration) 
    {
        $localStoragePath = $fsoConfiguration['localStoragePath'];
        $adapters = $fsoConfiguration['adapters'];

        if (!isset($adapters)) {
            return array (
                'storagePathResolver' => $this->_getDefaultAdapter($model, $specs, $localStoragePath)
            );
        }

        $classBase = "Iron_Model_Fso_Adapter_";

        $autoLoader = Zend_Loader_Autoloader::getInstance();
        $autoLoader->suppressNotFoundWarnings(true);
        set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
           //Avoid custom warning handlers  
        }, E_WARNING); 

        $adapterInstances = array();
        foreach ($adapters as $adapterType => $config) {

            $modifiers = isset($config['params']) ? $config['params'] : array(); 
            $driver = ucfirst($config['driver']);

            $adapterClass = ucfirst($adapterType) . '_' . $driver;
            $ironAdapter = $classBase . $adapterClass;

            if (class_exists($ironAdapter)) {
                $adapterInstances[$adapterType] = new $ironAdapter($model, $specs, $localStoragePath, $modifiers);
                continue;
            }

            if (class_exists($driver)) {
                $adapterInstances[$adapterType] = new $driver($model, $specs, $localStoragePath, $modifiers);
                continue;
            }

            throw new \Exception("Adapter not found: " . $driver);
        }

        restore_error_handler();
        $autoLoader->suppressNotFoundWarnings(false);
        return $adapterInstances;
    }

    protected function _getDefaultAdapter($model, $specs, $localStoragePath)
    {
        return new \Iron_Model_Fso_Adapter_StoragePathResolver_Default(
            $model, 
            $specs, 
            $localStoragePath
        );
    }

    protected function _buildConfiguration($config = array()) 
    {
        $defaultConfiguration = $this->_getDefaultConfig();
        $applicationConfig = $this->_getApplicationConfig();

        return array_merge(
            $defaultConfiguration, 
            $applicationConfig, //TODO Pensar ¿Switch orden de $applicationConfig & $config ?
            $config
        );
    }

    /**
     * @return array
     */
    protected function _getDefaultConfig()
    {
        $defaultStoragePath = APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage';
        return array(
            'localStoragePath' => $defaultStoragePath,
            'adapters'  => array(
                'storagePathResolver' => Array (
                        'driver' => 'Default',
                        'params' => array (
                            'keepExtension' => false,
                            'storeInBaseFolder' => false
                        )
                    )
            )
        );
    }

    /**
     * @return array
     */
    protected function _getApplicationConfig()
    {
        $bootstrap = \Zend_Controller_Front::getInstance()->getParam('bootstrap');
        if (is_null($bootstrap)) {
            $conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        } else {
            $conf = (Object) $bootstrap->getOptions();
        }

        if (!isset($conf->Iron) || !isset($conf->Iron['fso'])) {
            return array();
        }

        if (isset($conf->localStoragePath)) {
            trigger_error(
                "localStoragePath app configuration param is deprecated. Please use Iron.fso.localStoragePath instead", 
                E_USER_WARNING
            );

            $conf->Iron['fso']['localStoragePath'] = $conf->localStoragePath;
        }

        if (!isset($conf->Iron['fso']['entity'])) {
            return $conf->Iron['fso'];
        }
        
        $modelClass = get_class($this->_model);
        $entity = str_replace("\\", "_", $modelClass);

        $entityConfig = $this->_getEntityConfig($conf->Iron['fso']['entity'], $entity);
        unset($conf->Iron['fso']['entity']);

        return array_merge($conf->Iron['fso'], $entityConfig);
    }
    
    protected function _getEntityConfig($configuration, $entity) 
    {
        if (empty($configuration)) {
            return array();
        }
        $entity = strtolower($entity);

        foreach ($configuration as $modelClass => $modelConfig) {
            
            if (strtolower($modelClass) == $entity) {
                return $modelConfig;
            }
        }

        return array();
    }

    public function setPathResolver(Iron_Model_Fso_Adapter_StoragePathResolver_Interface $pathResolverAdapter) 
    {
        $this->_pathResolverAdapter = $pathResolverAdapter;
        return $this;
    }

    public function getPathResolver()
    {
        return $this->_pathResolverAdapter;
    }

    public function overwriteStoragePathResolver($className)
    {
        $this->_storagePathResolverName = $className;
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

        if (empty($this->_originalFilePath)) {
            try {
                $oldFilePath = $this->getFilePath();
                $this->_originalFilePath = $oldFilePath;
            } catch (\Exception $e) {
                //Go on 
            }
        }

        $this->setBaseName(basename($file));
        $this->_setFileToBeFlushed($file);
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

    protected function _setFileToBeFlushed($filepath)
    {
        $this->_fileToBeFlushed = $filepath;
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

        //TO-DO remove $pk?
        $targetFile = $this->_buildFilePath($pk);

        $srcFileSize = filesize($this->_fileToBeFlushed);

        if ($this->getSize() != $srcFileSize) {
            unlink($this->_fileToBeFlushed);
            throw new Exception('Something went wrong. New filesize: ' . $srcFileSize . '. Expected: ' . $this->getSize());
        }

        if (true === copy($this->_fileToBeFlushed, $targetFile)) {
            unlink($this->_fileToBeFlushed);
        } else {
            throw new Exception("Could not rename file " . $this->_fileToBeFlushed . " to " . $targetFile);
        }

        $this->_mustFlush = false;

        //Trash control
        
        if (!empty($this->_originalFilePath)) {
            try {
                $currentFilePath = $this->getFilePath();
                //file_put_contents("/tmp/traza", "\n*Old file: " . $this->_originalFilePath . "\r\n", FILE_APPEND);
                if ($this->_originalFilePath != $currentFilePath)  {
                     $this->_removeFile($this->_originalFilePath);
                }
            } catch (\Exception $e) {
                
                throw $e;
                //Go on 
            }
        }

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
     * Prepara el módelo para permitir la descarga del fichero llamando a getBinary()
     * @return Iron_Model_Fso
     */
    public function fetch()
    {
        $pk = $this->_model->getPrimaryKey();

        $baseNameGetter = 'get' . ucfirst($this->_modelSpecs['baseNameName']);
        $this->setBaseName($this->_model->$baseNameGetter());

        // TO-DO remove pk?
        $file = $this->_buildFilePath($pk);
        if (!file_exists($file)) {
            throw new Exception("File $file not found");
        }

        $this->_setSize(filesize($file));
        $this->_setMimeType($file);
        $this->_setMd5Sum($file);

        return $this;
    }

    public function remove()
    {
        $pk = $this->_model->getPrimaryKey();

        //TO-DO remove PK
        $file = $this->_buildFilePath($pk);
        $this->_removeFile($file);
        $this->_size = null;
        $this->_mimeType = null;
        $this->_binary = null;

        $this->_updateModelSpecs();
        return $this;
    }
    
    protected function _removeFile($file)
    {
        if (file_exists($file)) {
            unlink($file);
        } else {
            //TODO: loggear que el fichero que se intenta borrar no existe...
        }
    }

    public function getBinary()
    {
        return file_get_contents($this->getFilePath());
    }

    public function getFilePath()
    {
        return $this->_buildFilePath();
    }

    public function _buildFilePath()
    {
        if ($this->_filePath === null || $this->mustFlush()) {
            $this->_filePath = $this->_pathResolverAdapter->getPath();
        }

        return $this->_filePath;
    }
}
