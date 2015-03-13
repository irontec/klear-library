<?php
/***
 * File system object
*/
class Iron_Model_Fso_Adapter_StoragePathResolver_Default
{
    const CLASS_ATTR_SEPARATOR = '.';
    
    protected $_model;
    protected $_modelSpecs;
    protected $_modifiers = array(); 
    
    public function setModel($model)
    {
        $this->_model = $model;
    }
    
    public function setModelSpecs($modelSpecs)
    {
        $this->_modelSpecs = $modelSpecs;
    }
    
    public function setModifiers($modifiers)
    {
        $this->_modifiers = $modifiers;
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
        $appConfig = $this->_getAppConfig();
        if (isset($conf->localStoragePath)) {
        
            $storagePath = $conf->localStoragePath;
            if (substr($storagePath, -1) === DIRECTORY_SEPARATOR) {
                $storagePath = substr($storagePath,0,-1);
            }

            return $storagePath;
        }
        
        return APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage';
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
        
        if (isset($this->_modifiers['baseFolder']) &&
                true === $this->_modifiers['baseFolder']) {
                    
            return null;
        }
        
        $pk = $this->_model->getPrimaryKey();
        return $this->_pk2path($pk);
    }
    
    protected function _buildRealBaseName()
    {
        $pk = $this->_model->getPrimaryKey();
        
        if ($this->_modifiers ['keepExtension'] !== false) {
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
        
        throw Exception("unsupported pk received!");
        

    }

    
    protected function _buildDirectoryTree($filePath)
    {

        $targetDir = dirname($filePath);
        
        if (!file_exists($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                throw new Exception('Could not create dir ' . $targetDir);
            }
        }
    
    }
        
    protected function _getAppConfig() {
        $bootstrap = \Zend_Controller_Front::getInstance()->getParam('bootstrap');
        if (is_null($bootstrap)) {
            $conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        } else {
            $conf = (Object) $bootstrap->getOptions();
        }
        
        return $conf;
    }
    
}