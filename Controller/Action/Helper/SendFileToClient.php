<?php
require_once('Zend/Controller/Action/Helper/Abstract.php');

/**
 *
 * Action Helper para enviar ficheros al cliente (downloads)
 * @author Alayn Gortazar <alayn+karma@irontec.com>
 * @uses Iron_Controller_Action_Helper_SendFileToClient
 *
 */
class Iron_Controller_Action_Helper_SendFileToClient extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Envia el fichero al cliente
     *
     * @param  string|binary $file Ruta al archivo que se quiere descargar ó contenido del fichero (depende del parámetro $isRaw)
     * @param  array $options array con opciones para el fichero (name, filetype, etc...)
     * @param  bool $isRaw indica si el primer parametro es de tipo Raw/Binario (true) o si se trata del path al fichero (false). False por defecto
     * @return string true si todo va bien, false en caso contrario
     */
    public function sendFile($file, $options = array(), $isRaw = false)
    {
        if (!isRaw && !file_exists($file)) {
        	throw new Zend_Controller_Action_Exception('File not found', 404);
        }

        set_time_limit(0);
        $defaultOptions = array(
            'filename' => $isRaw? 'file' : basename($file),
            'disposition' => 'attachment'
        );

        // Metemos los valores por defecto en el array de options
        foreach ($defaultOptions as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }

        // Si no nos pasan el mimetype, intentamos obtenerlo nosotros
        if (!isset($options['type'])) {
        	$finfo = new finfo(FILEINFO_MIME_TYPE, __DIR__ . 'magic.mgc');
        	if ($isRaw) {
	            header('Content-type: ' . $finfo->buffer($file));
        	} else {
	            header('Content-type: ' . $finfo->file($file));
        	}
        }

        header('Content-type: ' . $options['type']);
        header('Content-Disposition: ' . $options['disposition']
        		. ';filename="' . str_replace('"','',$options['filename']) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Pragma: no-cache');
        header('Expires: 0');

        if ($isRaw) {
            echo $file;
        } else {
            readfile($file);
        }
        exit();
    }

    /**
     * Envia el fichero al cliente
     *
     * @param  string|binary $file Ruta al archivo que se quiere descargar ó contenido del fichero (depende del parámetro $isRaw)
     * @param  array $options array con opciones para el fichero (name, filetype, etc...)
     * @param  bool $isRaw indica si el primer parametro es de tipo Raw/Binario (true) o si se trata del path al fichero (false). False por defecto
     * @return string true si todo va bien, false en caso contrario
     */
    public function direct($file, $options = array(), $isRaw = false)
    {
        return $this->sendFile($file, $options, $isRaw);
    }
}
