<?php

class Iron_Translate_Adapter_Klear extends Zend_Translate_Adapter_Array
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
	protected function _log($message, $locale)
	{
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
    	        . $this->_translationFileName,
                dirname($this->_directory)
                . DIRECTORY_SEPARATOR
                . $this->_currentModuleName
    	        . DIRECTORY_SEPARATOR
    	        . $this->_translationLanguagePath
    	        . DIRECTORY_SEPARATOR
    	        . $this->_translationFileName
            );
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

	/**
	 * Creates/Updates module translation files
	 * @param string $translationFile File with current language translations
	 * @param string $baseTranslationFile File with all the translatable strings
	 */
	protected function _updateLocaleTranslation($translationFile, $baseTranslationFile)
	{
	    $baseTranslations = $this->_getDataFromFile($baseTranslationFile);

	    if (!isset($this->_baseTranslations[$baseTranslationFile])) {
	        $this->_baseTranslations[$baseTranslationFile] = $baseTranslations;
	    }

	    $found = false;
	    foreach ($this->_baseTranslations as $otherBases) {
	        if (in_array($this->_message, $otherBases)) {
	            $found = true;
	        }
	    }

	    if (!$found) {
	        $this->_baseTranslations[$baseTranslationFile][] = $this->_message;
	        $this->_writeToFile($this->_baseTranslations[$baseTranslationFile], $baseTranslationFile);
	    }

	    $translationData = $this->_getDataFromFile($translationFile);
        foreach ($this->_baseTranslations[$baseTranslationFile] as $key) {
            if (!isset($translationData[$key]) || $translationData[$key] === false) {
                $translationData[$key] = false;
            }
        }
        $this->_writeToFile($translationData, $translationFile);
	}

	/**
	 * Writes array's content into a file to be able to include it later
	 * @param array $contents array to save
	 * @param string $filename
	 */
	protected function _writeToFile(array $contents, $filename)
	{
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
	    $fileContents = "<?php\n\n";
	    $fileContents .= "return " . var_export($contents, true) . ";\n";
	    file_put_contents($filename, $fileContents);
	}

	/**
	 * If file exists includes the file and returns it's value
	 * If file does no exist returns an empty array
	 * @param string $filename
	 * @return array:
	 */
	protected function _getDataFromFile($filename)
	{
	    if (!file_exists($filename)) {
	        return array();
	    }
	    return include($filename);
	}

}
