<?php

class Iron_Translate_Adapter_Translator extends Zend_Translate_Adapter_Array
{

    
    protected $_translationFile;
    protected $_directory;
    protected $_translationLanguagePath;
    protected $_translationFileName;
    protected $_locales;
    protected $_currentLocale;
    
    protected $_message;
    protected $_locale;
    
    protected $_currentModuleName;

    /**
     * @var Zend_Controller_Front
     */
    protected $_front;
    
    protected $_baseTranslations = array();
    
	/**
	 * Logs a message when the log option is set
	 *
	 * @param string $message Message to log
	 * @param String $locale  Locale to log
	 */
	
	protected function _log($message, $locale) {

	    parent::_log($message, $locale);
		
	    $this->_front = Zend_Controller_Front::getInstance();
	    
	    $this->_locale = $locale;
	    
	    $this->_message = $message;
	    
	    $this->_currentModuleName = $this->_front->getRequest()->getModuleName();
	    
	    $this->_translationLog();
	    
	    $baseTranslationFile = dirname($this->_directory)
	    . DIRECTORY_SEPARATOR
	    . $this->_currentModuleName
	    . DIRECTORY_SEPARATOR
	    . $this->_translationLanguagePath
	    . DIRECTORY_SEPARATOR
	    . $this->_translationFileName;
	    
	    

	    
	    $this->_updateLocaleTranslation($this->_translationFile, $baseTranslationFile);
	    
	    foreach ($this->_locales as $sysLocale) {
	        if ($this->_currentLocale == $sysLocale) continue;

	        $this->_updateLocaleTranslation(
	        dirname($this->_directory)
            . DIRECTORY_SEPARATOR
            . $this->_currentModuleName
	        . DIRECTORY_SEPARATOR
	        . $this->_translationLanguagePath
	        . DIRECTORY_SEPARATOR
	        . (string) $sysLocale
	        . DIRECTORY_SEPARATOR
	        . $this->_translationFileName
	         , dirname($this->_directory)
            . DIRECTORY_SEPARATOR
            . $this->_currentModuleName
	        . DIRECTORY_SEPARATOR
	        . $this->_translationLanguagePath
	        . DIRECTORY_SEPARATOR
	        . $this->_translationFileName);
	    }
	}
	
	
	
	public function setTranslationFile($translationFile)
	{
	    $this->_translationFile = $translationFile;
	}
	
	public function setDirectory($directory)
	{
	    $this->_directory = $directory;
	}
	
	public function setTranslationLanguagePath($translationLanguagePath)
	{
	    $this->_translationLanguagePath = $translationLanguagePath;
	}
	
	public function setTranslationFileName($translationFileName)
	{
	    $this->_translationFileName = $translationFileName;
	}
	
	public function setAvailableLocales($locales)
	{
	    $this->_locales = $locales;
	}
	
	public function setCurrentLocale($locale)
	{
	    $this->_currentLocale = $locale;
	}
	
	protected function _translationLog()
	{
	    
	    //$msg = "[".$this->_front->getRequest()->getModuleName()."] Nuevo registro '".$this->_message."': ".$this->_translationFile;
	    
	    //$this->_options['log']->log($msg , $this->_options['logPriority']);
	    
	}
	
	protected function _updateLocaleTranslation($translationFile, $baseTranslationFile)
	{
	    if (!file_exists($baseTranslationFile)) {
	        $dir = dirname($baseTranslationFile);
	        if (!is_dir($dir)) {
	            mkdir($dir, 0777, true);
	        }
	        $file = "<?php\n\n";
	        $file.= "return array(\n";
	        $file.= ");\n";
	        file_put_contents($baseTranslationFile, $file);
	    }
	    
	    $baseTranslations = include $baseTranslationFile;
	    
	    if (!isset($this->_baseTranslations[$baseTranslationFile])) {
	        $this->_baseTranslations[$baseTranslationFile] = $baseTranslations;
	    }
	    
	    
	    $found = false;
	    foreach ($this->_baseTranslations as $otherBases) {
	        if (in_array($this->_message, $otherBases)) {
	            $found = true;
	        }
	    }

	    if ($found===false) {
	        $this->_baseTranslations[$baseTranslationFile][] = $this->_message;
	        $file = "<?php\n\n";
	        $file.= "return array(\n";
	        foreach ($this->_baseTranslations[$baseTranslationFile] as $key) {
	            if (strpos($key, "'") !==false) {
	                $file.= "\t\"".$key."\" , \n";
	            } else {
	                $file.= "\t'".$key."' , \n";
	            }
	        }
	        $file.= ");\n";
	        file_put_contents($baseTranslationFile, $file);
	    }
	    
	    if (!file_exists($translationFile)) {
	        $dir = dirname($translationFile);
    	    if (!is_dir($dir)) {
    	        mkdir($dir, 0777, true);
	        }
            $file = "<?php\n\n";
    	    $file.= "return array(\n";
    	    foreach ($this->_baseTranslations[$baseTranslationFile] as $key) {
    	        if (strpos($key, "'") !==false) {
	                $file.= "\t\"".$key."\" => ";
	            } else {
	                $file.= "\t'".$key."' => ";
	            }
	            $file.= "false,\n";
	        }
	        $file.= ");\n";
	        file_put_contents($translationFile, $file);
	    } else {
	        
	        $translations = include $translationFile;
	        
	        $file = "<?php\n\n";
	        $file.= "return array(\n";
	        foreach ($this->_baseTranslations[$baseTranslationFile] as $key) {
	            
	            if (strpos($key, "'") !==false) {
	                $file.= "\t\"".$key."\" => ";
	            } else {
	                $file.= "\t'".$key."' => ";
	            }
	            
	            if (isset($translations[$key]) && $translations[$key]!==false) {
	                if (strpos($translations[$key], "'") !==false) {
	                    $file.= "\"".$translations[$key]."\",\n";
	                } else {
	                    $file.= "'".$translations[$key]."',\n";
	                }        
	            } else {
	                $file.= "false,\n";
	            }
	            
	            
	        }
	        $file.= ");\n";
	        file_put_contents($translationFile, $file);
	    }
	}
	
}