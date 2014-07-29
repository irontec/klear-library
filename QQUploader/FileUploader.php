<?php
class Iron_QQUploader_FileUploader {

    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;

    protected $_translator;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760)
    {

        $allowedExtensions = array_map("strtolower", $allowedExtensions);
        $this->allowedExtensions = $allowedExtensions;

        if (!empty($sizeLimit)) {
            $this->sizeLimit = $this->_toBytes($sizeLimit);
        }

        $this->checkServerSettings();

        if (isset($_GET['qqfile'])) {
            $this->file = new Iron_QQUploader_FileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new Iron_QQUploader_FileForm();
        } else {
            $this->file = false;
        }

        if (Zend_Registry::isRegistered(Iron_Plugin_Translator::DEFAULT_REGISTRY_KEY)) {
            $this->_translator = Zend_Registry::get(Iron_Plugin_Translator::DEFAULT_REGISTRY_KEY);
        } else if (Zend_Registry::isRegistered('Zend_Translate')) {
            $this->_translator = Zend_Registry::get('Zend_Translate');
        }
    }

    private function checkServerSettings()
    {
        $postSize = $this->_toBytes(ini_get('post_max_size'));
        $uploadSize = $this->_toBytes(ini_get('upload_max_filesize'));

        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            $msg = $this->_translate("Increase post_max_size and upload_max_filesize to");
            Throw new Zend_Exception($msg . " " . $size, 1001);
        }
    }

    private function _toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }

    /**
     *
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = false, $newFileName = false, $extension = false)
    {
        if (!is_writable($uploadDirectory)) {
            $msg = $this->_translate("Server error. Upload directory isn't writable.");
            Throw new Zend_Exception($msg, 1002);
        }

        if (!$this->file){
            $msg = $this->_translate("No files were uploaded.");
            Throw new Zend_Exception($msg, 1003);
        }

        $size = $this->file->getSize();

        if ($size == 0) {
            $msg = $this->_translate("No files were uploaded.");
            Throw new Zend_Exception($msg, 1004);
        }

        if ($size > $this->sizeLimit) {
            $msg = $this->_translate("File is too large.");
            Throw new Zend_Exception($msg, 1005);
        }

        $pathinfo = pathinfo($this->file->getName());
        if ($newFileName !== false) {
            $filename = $newFileName;
        } else {
            $filename = $pathinfo['filename'];
        }

        $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            $msg = $this->_translate('File has an invalid extension, it should be one of ');
            return array('error' => $msg. $these . '.',1006);
        }

        // Debemos renombrar la extensión del archivo? O quitarla?
        if ($extension !== false) {
            if ($extension == '') {
                $ext = '';
            } else {
                $ext = '.' . $extension;
            }
        } else {
            $ext = '.' . $ext;
        }

        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            $cont = 1;
            $curFileName = $filename;

            while (file_exists($uploadDirectory . $filename . $ext)) {
                $filename = $curFileName . '.' . $cont++;
            }
        }

        $path = explode(DIRECTORY_SEPARATOR, $uploadDirectory);
        $path[] = $filename . $ext;

        if ($this->file->save(implode(DIRECTORY_SEPARATOR,$path))) {

            $baneName = $pathinfo['filename'];
            if (isset($pathinfo['extension'])) {

                $baneName .= '.' . $pathinfo['extension'];
            }

            return array(
                        'success'=>true,
                        'path'=>implode(DIRECTORY_SEPARATOR,$path),
                        'filename' => $filename . $ext,
                        'basename' => $baneName
                   );
        } else {

            $msg = $this->_translate('Could not save uploaded file.');
            Throw new Zend_Exception($msg, 1007);
        }
    }

    protected function _translate($str)
    {
        if (is_null($this->_translator) || empty($str)) {
            return $str;
        }

        return $this->_translator->translate($str);
    }
}