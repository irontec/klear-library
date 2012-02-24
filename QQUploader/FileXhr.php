<?php

class Iron_QQUploader_FileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        
        $target = fopen($path, "w");

        $realSize = 0;
        while (!feof($input)) {
            $realSize += fwrite($target,fread($input,8192));
        }
        fclose($target);
        return true;
        
    }
    
    function getName() {
        return $_GET['qqfile'];
    }
    
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }   
}