<?php
//require_once('Zend/Controller/Action/Helper/Abstract.php');
//require_once('Zend/Controller/Action/Exception.php');
//require_once 'Zend/Controller/Action/HelperBroker.php';
//require_once 'Zend/Layout.php';

/**
 *
 * Action Helper para enviar ficheros al cliente (downloads)
 * @author Javier Infante <jabi@irontec.com>
 * @uses Iron_Controller_Action_Helper_SendFileToClient
 *
 */
class Iron_Controller_Action_Helper_SendPartialFileToClient extends Iron_Controller_Action_Helper_SendFileToClient
{

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

        $request = \Zend_Controller_Front::getInstance()->getRequest();

        if (!$request instanceof \Zend_Controller_Request_Abstract) {
            return $this->_sendCompleteFile();
        }

        if ($this->_isRaw) {

            $fp = tmpfile();
            fwrite($fp, $this->_file);
            fseek($fp, 0);

            $size = strlen($this->_file);

        } else {

            $fp = fopen($this->_file, 'rb');

            $size = filesize($this->_file);
        }


        if (!$fp) {

            throw new Zend_Controller_Action_Exception('Internal Server Error', 500);

        }

        $begin  = 0;
        $end    = $size - 1;

        if ($request->getServer('HTTP_RANGE')) {

            $range = $request->getServer('HTTP_RANGE');

            if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i',$range, $matches)) {
                $begin  = intval($matches[1]);
                if (!empty($matches[2])) {
                    $end = intval($matches[2]);
                }
            }
        }


        if ($begin > 0 || $end < $size) {
            $this->getResponse()->setHttpResponseCode(206);
            //header('HTTP/1.1 206 Partial Content');
        } else {
            $this->getResponse()->setHttpResponseCode(200);
        }

        $options = array(
                'Cache-Control' => 'public, must-revalidate, max-age=0',
                'X-Pad' => 'avoid browser bug',
                'Pragma' => 'no-cache',
                'Accept-Ranges' => 'bytes',
                'Content-length' => $size,
                'Content-Range' => "bytes $begin-$end/$size"
                );

        if ($end != ($size-1)) {
            $options['Content-Length'] = ($end - $begin) + 1;
        }

        $this->setOptions($options);
        $this->_sendHeaders($this->_options);



        $cur = $begin;
        fseek($fp, $begin, 0);

        while(!feof($fp) && $cur < $end && (connection_status() == 0)) {
            print fread($fp, min(1024 * 16, ($end + 1) - $cur));
            $cur += 1024 * 16;
        }

        fclose($fp);

    }


}
