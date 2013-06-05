<?php
//require_once('Zend/Controller/Action/Helper/Abstract.php');
//require_once('Zend/Controller/Action/Exception.php');
//require_once 'Zend/Controller/Action/HelperBroker.php';
//require_once 'Zend/Layout.php';

/**
 *
 * Action Helper para enviar ficheros al cliente (downloads)
 * @author Alayn Gortazar <alayn+karma@irontec.com>
 * @uses Iron_Controller_Action_Helper_SendFileToClient
 *
 */
class Iron_Controller_Action_Helper_SendFileToClient extends Zend_Controller_Action_Helper_Abstract
{
    protected $_sendHeaders = true;
    protected $_options = array();
    protected $_isRaw;
    protected $_file;
    protected $_outputBufferingAllowed = false;

    public function supressHeaders()
    {
        $this->_sendHeaders = false;
    }

    /**
     * Envia el fichero al cliente
     *
     * @param  string|binary $file Ruta al archivo que se quiere descargar ó
     *          contenido del fichero (depende del parámetro $isRaw)
     * @param  array $options array con opciones para el fichero (name, filetype, etc...)
     * @param  bool $isRaw indica si el primer parametro es de tipo Raw/Binario
     *          (true) o si se trata del path al fichero (false). False por defecto
     * @param  bool $partialDownload indica si la descarga acepta HTTP_RANGE
     * @return string true si todo va bien, false en caso contrario
     */
    public function sendFile($file, $options = array(), $isRaw = false)
    {
        if ($this->_fileNotFound($file, $isRaw)) {
            throw new Zend_Controller_Action_Exception('File not found', 404);
        }

        set_time_limit(0);
        $this->_isRaw = $isRaw;
        $this->_file = $file;

        $this->setOptions($options);
        $this->_disableOtherOutput();
        
        $this->_sendHeaders($this->_options);
        
        if ($this->_isRaw) {
            echo $this->_file;
        } else {
            $f = fopen($this->_file, 'r');
            while(!feof($f)){
                print fgets($f, 1024);
            }
            fclose($f);
        }
    }
    
    protected function _fileNotFound($file, $isRaw)
    {
        return !$isRaw && !file_exists($file);
    }


    
    public function setOptions($options)
    {
        if (isset($options['disposition'])) {

            $options['Content-Disposition'] = $options['disposition'];
            unset($options['disposition']);
        }

        if (isset($options['type'])) {

            $options['Content-type'] = $options['type'];
            unset($options['type']);
        }

        $this->_options = $options;

        $size = $this->_isRaw? strlen($this->_file) : filesize($this->_file);
        
        $defaultOptions = array(
                'filename' => $this->_isRaw? 'file' : basename($this->_file),
                'Content-Disposition' => 'attachment',
                'Content-Transfer-Encoding' => 'binary',
                'Content-Length' => $size,
                'Pragma' =>'no-cache',
                'Expires' => '0'
        );

        // Metemos los valores por defecto en el array de options
        foreach ($defaultOptions as $key => $value) {

            if (!isset($this->_options[$key])) {

                $this->_options[$key] = $value;
            }
        }

        $this->_setMimetype();
        return $this;
    }

    protected function _sendHeaders($options)
    {
        $response = $this->getResponse();

        if ($this->_sendHeaders) {

            $this->_setHeaders($response, $options);
            $response->sendHeaders();
        }

        $response->clearHeaders();
    }

    protected function _setHeaders($response, $options)
    {
        $response->setHeader(
            'Content-Disposition',
            $options['Content-Disposition'] . ';filename="' . str_replace('"', '', $options['filename']) . '"',
            true
        );

        foreach ($options as $key => $value) {

            if (in_array($key, array('filename', 'Content-Disposition'))) {

                continue;
            }

            $response->setHeader($key, $value, true);
        }
    }

    /**
     * Setea el mimetype en caso de no estar entre las options
     */
    protected function _setMimetype()
    {
        if (!isset($this->_options['Content-type'])) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($this->_isRaw) {
                $this->_options['Content-type'] = $finfo->buffer($this->_file);
            } else {
                $this->_options['Content-type'] = $finfo->file($this->_file);
            }
        }
        return $this;
    }


    protected function _disableOtherOutput()
    {
        Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setNoRender(true);
        $layout = Zend_Layout::getMvcInstance();
        if (null !== $layout) {
            $layout->disableLayout();
        }

        $this->_cleanOutputBuffers();
    }

    protected function _cleanOutputBuffers()
    {
        if (!$this->_outputBufferingAllowed) {
            while (ob_get_level()) {
                ob_end_clean();
            }
        }
    }

    public function allowOutputBuffering($flag = true)
    {
        $this->_outputBufferingAllowed = (bool)$flag;
    }


    /**
     * Envia el fichero al cliente
     *
     * @param  string|binary $file Ruta al archivo que se quiere descargar ó
     *          contenido del fichero (depende del parámetro $isRaw)
     * @param  array $options array con opciones para el fichero (name, filetype, etc...)
     * @param  bool $isRaw indica si el primer parametro es de tipo Raw/Binario
     *          (true) o si se trata del path al fichero (false). False por defecto
     * @return string true si todo va bien, false en caso contrario
     */
    public function direct($file, $options = array(), $isRaw = false)
    {
        return $this->sendFile($file, $options, $isRaw);
    }
}
