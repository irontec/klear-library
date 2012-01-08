<?php
require_once('Zend/Controller/Action/Helper/Abstract.php');

/**
 * Action Helper para obtener las rutas absolutas de los archivos subidos por Karma
 * @author alayn
 *
 */
class Iron_Controller_Action_Helper_KarmaDocPath extends Zend_Controller_Action_Helper_Abstract
{
    /**
     *
     * @param  string $docsRoot Directorio donde se encuentras los documentos (p.e. APPLICATION_PATH . '/data/documentos.plt/'
     * @param  $id Id del documento que se desea obtener
     * @return string|null Ruta real en la que se encuentra el fichero en el disco. Si no existe devuelve null
     */
    public function getDocumentPath($docsRoot, $id)
    {
        $aId = str_split((string)$id);
        array_pop($aId);
        if (!sizeof($aId)) {
            $aId = array('0');
        }
        $aId[] = $id;
        $filePath = $docsRoot . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $aId);

        if(is_file($filePath))
        {
            return realpath($filePath);
        }
        return null;
    }

    /**
     * Llamar directamente a la función de getDocument()
     *
     * @param  string $docsRoot Directorio donde se encuentras los documentos (p.e. APPLICATION_PATH . '/data/documentos.plt/'
     * @param  $id Id del documento que se desea obtener
     * @return string|null Ruta real en la que se encuentra el fichero en el disco. Si no existe devuelve null
     */
    public function direct($docsRoot, $id)
    {
        return $this->getDocumentPath($docsRoot, $id);
    }
}
