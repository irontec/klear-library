<?php
/***
 * File system object
*/
class Iron_Model_Fso_Adapter_BaseNameResolver_Default implements Iron_Model_Fso_Adapter_BaseNameResolver_Interface
{
    const CLASS_ATTR_SEPARATOR = '.';

    protected $_model;
    protected $_primaryKey;

    protected $_modelSpecs;

    protected $_localStoragePath;
    protected $_modifiers = array(
        'unique' => false,
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
            throw new \Exception("Unknown basename resolver modifier: " . $name);
        }

        $this->_modifiers[$name] = $value;
    }


     public function getBaseName($fileName) 
    {
        if( $this->_modifiers['unique'])
        {
            $mapper = $this->_model->getMapper();
            $model = $mapper->findOneByField($this->_modelSpecs['baseNameName'], $fileName);
            if($model && ($this->_model->getPrimaryKey() != $model->getPrimaryKey())) {
                $fileName = $this->_generateBaseName($fileName);
            }
            return $fileName;
        }
        else
        {
            return $fileName;
        }
    }
    
    protected function _generateBaseName($fileName)
    {
        $file = pathinfo($fileName);
        $cont = 0;

        if(isset($file['extension'])){
            $file['extension'] = ".".$file['extension'];
        }
        else $file['extension'] = '';

        $mapper = $this->_model->getMapper();
        $models = $mapper->fetchList($this->_modelSpecs['baseNameName'] . " like '" . $file['filename'] . "(%)" . $file['extension'] . "' or " . $this->_modelSpecs['baseNameName'] . " = '" . $file['filename'] . $file['extension'] . "'");

        foreach($models as $model) {
            $get = "get".ucfirst($this->_modelSpecs['baseNameName']);

            if(preg_match('/'.$file['filename'].'\(([0-9]+)\)'.$file['extension'].'/', $model->$get(), $matches)){
                if(isset($matches[1]) && $matches[1] > $cont){
                    $cont = $matches[1];
                }
            }
        }

        $number = $cont+1;
        $fileName = $file['filename'] . '(' . $number . ')' . $file['extension'];
        return $fileName;
    }
}