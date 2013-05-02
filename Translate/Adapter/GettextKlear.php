<?php
//include_once('Zend/Translate/Adapter/Gettext.php');
class Iron_Translate_Adapter_GettextKlear extends Zend_Translate_Adapter_Gettext
{

    // Internal variables
    private $_bigEndian   = false;
    private $_file        = false;
    private $_adapterInfo = array();
    private $_data        = array();

    /**
     * Read values from the MO file
     *
     * @param  string  $bytes
     */
    private function _readMOData($bytes)
    {
        if ($this->_bigEndian === false) {
            return unpack('V' . $bytes, fread($this->_file, 4 * $bytes));
        } else {
            return unpack('N' . $bytes, fread($this->_file, 4 * $bytes));
        }
    }

    /**
     * Load translation data (MO file reader)
     *
     * @param  string  $filename  MO file to add, full path must be given for access
     * @param  string  $locale    New Locale/Language to set, identical with locale identifier,
     *                            see Zend_Locale for more information
     * @param  array   $option    OPTIONAL Options to use
     * @throws Zend_Translation_Exception
     * @return array
     */
    protected function _loadTranslationData($filename, $locale, array $options = array())
    {
        $this->_data      = array();
        $this->_bigEndian = false;
        $this->_file      = @fopen($filename, 'rb');
        if (!$this->_file) {
            require_once 'Zend/Translate/Exception.php';
            throw new Zend_Translate_Exception('Error opening translation file \'' . $filename . '\'.');
        }
        if (@filesize($filename) < 10) {
            @fclose($this->_file);
            require_once 'Zend/Translate/Exception.php';
            throw new Zend_Translate_Exception('\'' . $filename . '\' is not a gettext file');
        }

        // get Endian
        $input = $this->_readMOData(1);
        if (strtolower(substr(dechex($input[1]), -8)) == "950412de") {
            $this->_bigEndian = false;
        } else if (strtolower(substr(dechex($input[1]), -8)) == "de120495") {
            $this->_bigEndian = true;
        } else {
            @fclose($this->_file);
            require_once 'Zend/Translate/Exception.php';
            throw new Zend_Translate_Exception('\'' . $filename . '\' is not a gettext file');
        }
        // read revision - not supported for now
        $input = $this->_readMOData(1);

        // number of bytes
        $input = $this->_readMOData(1);
        $total = $input[1];

        // number of original strings
        $input = $this->_readMOData(1);
        $OOffset = $input[1];

        // number of translation strings
        $input = $this->_readMOData(1);
        $TOffset = $input[1];

        // fill the original table
        fseek($this->_file, $OOffset);
        $origtemp = $this->_readMOData(2 * $total);
        fseek($this->_file, $TOffset);
        $transtemp = $this->_readMOData(2 * $total);

        for($count = 0; $count < $total; ++$count) {
            if ($origtemp[$count * 2 + 1] != 0) {
                fseek($this->_file, $origtemp[$count * 2 + 2]);
                $original = @fread($this->_file, $origtemp[$count * 2 + 1]);
                $original = explode("\0", $original);
            } else {
                $original[0] = '';
            }


            if ($transtemp[$count * 2 + 1] != 0) {
                fseek($this->_file, $transtemp[$count * 2 + 2]);
                $translate = fread($this->_file, $transtemp[$count * 2 + 1]);
                $translate = explode("\0", $translate);
                if ((count($original) > 1) && (count($translate) > 1)) {
                    $this->_data[$locale][$original[0]] = $translate;
                    array_shift($original);
                    $c=0;
                    foreach ($original as $orig) {
                        $this->_data[$locale][$orig] = $translate[++$c];
                    }
                } else {
                    $this->_data[$locale][$original[0]] = $translate[0];
                }
            }
        }


        @fclose($this->_file);

        $this->_data[$locale][''] = trim($this->_data[$locale]['']);
        if (empty($this->_data[$locale][''])) {
            $this->_adapterInfo[$filename] = 'No adapter information available';
        } else {
            $this->_adapterInfo[$filename] = $this->_data[$locale][''];
        }

        unset($this->_data[$locale]['']);
        return $this->_data;
    }

    /**
     * Returns the adapter informations
     *
     * @return array Each loaded adapter information as array value
     */
    public function getAdapterInfo()
    {
        return $this->_adapterInfo;
    }

    /**
     * Returns the adapter name
     *
     * @return string
     */
    public function toString()
    {
        return "Gettext";
    }

    protected function _doTranslate($messageId, $locale = null)
    {
        $translation = parent::translate($messageId, $locale);
        if ($locale === null) {
            $locale = $this->_options['locale'];
        }
        $locale = (string) $locale;

        if ($this->isTranslated($messageId) && is_array($translation)) {

            if (isset($translation[0])) {
                return $translation[0];
            } else {
                return $messageId;
            }
        }

        return $translation;
    }


    public function translate($messageId, $locale = null)
    {
        $translation = $this->_doTranslate($messageId, $locale);
        if (!$this->isTranslated($messageId) && !is_array($messageId) ) {
            $lcString = lcfirst($messageId);
            $ucString = ucfirst($messageId);
            if ($this->isTranslated($lcString)) {
                $translation = ucfirst($this->_doTranslate($lcString, $locale));
            } elseif ($this->isTranslated($ucString)) {
                $translation = lcfirst($this->_doTranslate($ucString, $locale));
            }
        }
        return $translation;
    }
}
