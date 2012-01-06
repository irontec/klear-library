<?php


class Iron_View_Helper_Truncate extends Zend_View_Helper_Abstract
{

    
    /**
     * Recibe $texto y devuelve $texto truncado a $max_length caracteres.
     * Si es html ($isHTML), se encargará de cerrar todos los tags abiertos (y no contarlos para max_length)
     * Siempre que se trunque el texto, se añadirá $extra al final del string devuelto
     * 
     * Uso (Desde la vista)
     * $this->truncate($registro,20,'<a href="#more">[más]</a>',true); 
     * 
     * @param string $texto
     * @param int $max_length
     * @param string $extra
     * @param bool $isHTML
     * @return string
     */
    public function truncate($texto, $max_length, $extra = '...', $isHTML = true){
        $i = 0;
        $tags = array();
        if($isHTML){
            preg_match_all('/<[^>]+>([^<]*)/', $string, $res, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
            foreach($res as $o){
                if($o[0][1] - $i >= $length)
                    break;
                $t = substr(strtok($o[0][0], " \t\n\r\0\x0B>"), 1);
                if($t[0] != '/')
                    $tags[] = $t;
                elseif(end($tags) == substr($t, 1))
                    array_pop($tags);
                $i += $o[1][1] - $o[0][1];
            }
        }
        return substr($string, 0, $length = min(strlen($string),  $length + $i)) . (count($tags = array_reverse($tags)) ? '</' . implode('></', $tags) . '>' : '') . (strlen($string) > $length ? $extra : '');
    }
}
