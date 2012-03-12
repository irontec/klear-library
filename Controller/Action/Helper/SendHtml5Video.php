<?php
require_once('Zend/Controller/Action/Helper/Abstract.php');
require_once('Zend/Controller/Action/Exception.php');

/**
 *
 * Action Helper para enviar videos al cliente (downloads)
 * @author Alayn Gortazar <alayn+karma@irontec.com>
 *
 * FIXME: Probablemente se puede mejorar mirar APPENDIX A - http://mobiforge.com/developing/story/content-delivery-mobile-devices
 *
 */
class Iron_Controller_Action_Helper_SendHtml5Video extends Zend_Controller_Action_Helper_Abstract
{
    protected $_sendHeaders = true;
    protected $_filePath = array();

    public function supressHeaders()
    {
        $this->_sendHeaders = false;
    }

    protected function _setHeaders($headers)
    {
        foreach ($headers as $name => $value) {
            $headers[strtolower($name)] = $value;
        }

        $this->getResponse()->clearAllHeaders();
        if (!isset($headers['content-type'])) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $headers['content-type'] = $finfo->file($this->_filePath);
        }

        $this->getResponse()->setHeader('Accept-Ranges', 'bytes');
        $this->getResponse()->setHeader('Connection', 'close');
        $this->getResponse()->setHeader('Content-Type', $headers['content-type']);
        return $this;

    }

    /**
     * Envia el fichero al cliente
     *
     * @param  string $filePath Ruta al video que se quiere servir
     * @param  array $headers array con cabeceras para el fichero (Si no se intentarán calcular sobre la marcha)
     * @return string true si todo va bien, false en caso contrario
     */
    public function sendFile($filePath, $headers = array())
    {
        $this->_disableOtherOutput();

        $this->_filePath = $filePath;
        $this->_setHeaders($headers);
        if ($filePath) {
            $filesize = filesize($filePath);
            if (!$this->getRequest()->getServer('HTTP_RANGE')) {
                $this->getResponse()->setHeader('Content-Length', $filesize);
                $this->getResponse()->sendHeaders();
                ob_end_flush();
                readfile($filePath);
            } else { //FIXME: violes rfc2616, which requires ignoring  the header if it's invalid
                $fp = fopen($filePath, 'r');
                preg_match("/^bytes=(\d+)-/i", $this->getRequest()->getServer('HTTP_RANGE'), $matches);
                $offset = (int) $matches[1];
                if ($offset < $filesize && $offset >= 0) {
                    if (@fseek($fp, $offset, SEEK_SET) != 0)
                        die("err");
                    $this->getResponse()->setHttpResponseCode(206);
                    $this->getResponse()->setHeader('Content-Length', $filesize - $offset - 1);
                    $this->getResponse()->setHeader('Content-Range', 'bytes ' . $offset . '-' . ($filesize - 1) . '/' . $filesize);
                    $this->getResponse()->sendHeaders();
                    ob_end_flush();
                    while (!feof($fp)) {
                        echo fread($fp, 8192);
                    }
                    fclose($fp);
                    return true;
                }
                else {
                    $this->getResponse()->setHttpResponseCode(416);
                    return false;
                }
                //fread in loop here
            }
        } else {
            throw new Zend_Controller_Action_Exception('File not found');
        }
    }

    protected function _disableOtherOutput()
    {
        require_once 'Zend/Controller/Action/HelperBroker.php';
        Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setNoRender(true);

        require_once 'Zend/Layout.php';
        $layout = Zend_Layout::getMvcInstance();
        if (null !== $layout) {
            $layout->disableLayout();
        }
    }

    /**
     * Envia el fichero al cliente
     *
     * @param  string $filePath Ruta al video que se quiere servir
     * @param  array $headers array con cabeceras para el fichero (Si no se intentarán calcular sobre la marcha)
     * @return string true si todo va bien, false en caso contrario
     */
    public function direct($file, $options = array(), $isRaw = false)
    {
        return $this->sendFile($file, $options, $isRaw);
    }
}
