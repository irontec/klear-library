<?php

class Iron_QQUploader_FileUploader {
	
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){        
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
    }
    
    private function checkServerSettings()
    {
        
        $postSize = $this->_toBytes(ini_get('post_max_size'));
        $uploadSize = $this->_toBytes(ini_get('upload_max_filesize'));        
        
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            
            Throw new Zend_Exception("Increase post_max_size and upload_max_filesize to $size", 1001);    
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
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = false, $newFileName = false, $extension = false)
    {
        
        if (!is_writable($uploadDirectory)) {
            Throw new Zend_Exception("Server error. Upload directory isn't writable.",1002);
        }
        
        if (!$this->file){
            Throw new Zend_Exception("No files were uploaded.",1003);
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            Throw new Zend_Exception("No files were uploaded.",1004);
        }
        
        if ($size > $this->sizeLimit) {
            Throw new Zend_Exception("File is too large.",1005);
        }
        
        $pathinfo = pathinfo($this->file->getName());
        if ($newFileName !== false) {
        	$filename = $newFileName;
        } else {
        	$filename = $pathinfo['filename'];
        }
        
        $ext = $pathinfo['extension'];
        
        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.',1006);
        }
        
        // Debemos renombrar la exztensión del archivo? O quitarla?
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
          
        
        if ($this->file->save(implode(DIRECTORY_SEPARATOR,$path))){
            return array(
                        'success'=>true,
                        'path'=>implode(DIRECTORY_SEPARATOR,$path),
                        'filename' => $filename . $ext,
                        'basename' => $pathinfo['filename'] . '.' . $pathinfo['extension']);
        } else {
            Throw new Zend_Exception('Could not save uploaded file.', 1007);
        }
        
    }    
}