<?php

require_once "phing/Task.php";
require_once "Zend/Loader/Autoloader.php";

/**
 * Parser for application.ini invocked from phing
 * 
 * @author jabi
 *
 */
class ConfigurationParser extends Task {

    private $iniFile = null;
    private $stage  = null;
    private $targetProperty = null;

    protected $outputProperty;

    protected $_defaultSegments = array(
        'hostname'=>'localhost',
        'port'=>'3306'
    );

    /**
     * Setter for configs/application.ini path
     * @param unknown $str
     */
    public function setIniFile($str) {
        $this->iniFile = $str;
    }

    /**
     * Setter for stage
     * @param unknown $stage
     */
    public function setStage($stage) {
        $this->stage= $stage;
    }

    /**
     * Setter for variable (dotted splitted)
     * @param unknown $prop
     */
    public function setTargetProperty($prop) {
        $this->targetProperty = $prop;
    }
    
    
    public function init() {
        // nothing to do here
        $l = Zend_Loader_Autoloader::getInstance();
    }


    public function setOutputProperty($prop)
    {
        $this->outputProperty = $prop;
    }



    public function main() {

        $config = new Zend_Config_Ini($this->iniFile, $this->stage);
        
        $value = $config;
        $segments = explode(".", $this->targetProperty);

        foreach($segments as $segment) {
            if (isset($value->{$segment})) {
                $value = $value->{$segment};
            } else {
                if (isset($this->_defaultSegments[$segment])) {
                    $value = $this->_defaultSegments[$segment];
                    break;
               }
               throw new Exception("Segment:". $segment . " not found in <".$this->targetProperty.">!");
            }
        }

        $this->project->setProperty(
            $this->outputProperty,
            $value
        );

        return true;
    }
}
