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
    
    protected $_modifiers = array(
        // modificadores de fso soportados (ninguno de momento)
        'fso'           => array(
                
        ),
        // modificadores de pathName soportados
        'pathResolver'  => array(
            'keepExtension' => false,
            'baseFolder' => false
        )
    );
    
    protected $_storagePathResolverName = '\Iron_Model_Fso_Adapter_StoragePathResolver_Default';
    
    public function __construct($model, $specs)
    {
        $this->_model = $model;
        $this->_modelSpecs = $specs;
    }
    
    public function overwriteStoragePathResolver($className)
    {
        $this->_storagePathResolverName = $className;
    }
    
    public function addModifier($modifier) 
    {
        if (isset($this->_modifiers['fso'][$modifier])) {
            $this->_modifiers['fso'][$modifier] = true;
        }
        
        if (isset($this->_modifiers['pathResolver'][$modifier])) {
            $this->_modifiers['pathResolver'][$modifier] = true;
        }
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
        $this->_setSrcFile($file);
        $this->_setMimeType($file);
        $this->_setMd5Sum($file);
        
        return $this;
    }

    public function remove()
    {
        $pk = $this->_model->getPrimaryKey();

        //TO-DO remove PK
        $file = $this->_buildFilePath($pk);

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
        return $this->_buildFilePath();
    }

    public function _buildFilePath($pk)
    {
        if ($this->_filePath === null) {
            
            $className = $this->_storagePathResolverName;
            
            $resolver = new $className();
            
            $resolver->setModel($this->_model);
            $resolver->setModelSpecs($this->_modelSpecs);
            $resolver->setModifiers($this->_modifiers['pathResolver']);
            
            $this->_filePath = $resolver->getPath();
        }
        
        return $this->_filePath;
    }
}
