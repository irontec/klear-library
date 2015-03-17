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
     * @var string old file path
     */
    protected $_originalFilePath;

    /**
     * check _setDefaultModifiers()
     * @var array
     */
    protected $_modifiers = array();

    protected $_storagePathResolverName = '\Iron_Model_Fso_Adapter_StoragePathResolver_Default';

    public function __construct($model, $specs)
    {
        $this->_model = $model;
        $this->_modelSpecs = $specs;
        $this->_setDefaultModifiers();
    }

    protected function _setDefaultModifiers()
    {
        $this->_modifiers = array(
            // Modificadores soportados 
            'fso'           => array(
                // Ninguno de momento
            ),
            // modificadores de pathName soportados
            'pathResolver'  => array(
                'keepExtension' => false,
                'baseFolder' => false
            )
        );
    }

    public function overwriteStoragePathResolver($className)
    {
        $this->_storagePathResolverName = $className;
    }

    /**
     * @param array $modifiers
     */
    public function setModifiers(array $modifiers)
    {
        $this->resetModifiers();
        foreach ($modifiers as $modifier) {
            $this->addModifier($modifier);
        }
    }

    public function addModifier($modifier) 
    {
        if (isset($this->_modifiers['fso'][$modifier])) {
            $this->_modifiers['fso'][$modifier] = true;

        } else if (isset($this->_modifiers['pathResolver'][$modifier])) {
            $this->_modifiers['pathResolver'][$modifier] = true;

        } else {
            throw new \Exception("Unknown modifier " . $modifier);
        }
    }

    /**
     * @return void
     */
    public function resetModifiers()
    {
        $this->_setDefaultModifiers();
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
        //$this->_setSrcFile($file);
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
        return file_get_contents($this->_srcFile);
    }

    public function getFilePath()
    {
        return $this->_buildFilePath();
    }

    public function _buildFilePath()
    {
        if ($this->_filePath === null || $this->mustFlush()) {
            
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
