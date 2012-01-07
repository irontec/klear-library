<?php
include_once('Zend/View/Helper/Abstract.php');

class Iron_View_Helper_Truncate extends Zend_View_Helper_Abstract
{


    /**
     * Recibe $text y devuelve $text truncado a $maxLength caracteres.
     * Si es html ($isHTML), se encargará de cerrar todos los tags abiertos (y no contarlos para max_length)
     * Siempre que se trunque el texto, se añadirá $extra al final del string devuelto
     *
     * Uso (Desde la vista)
     * $this->truncate($registro,20,'<a href="#more">[más]</a>',true);
     *
     * @param string $text
     * @param int $maxLength
     * @param string $extra
     * @param bool $isHTML
     * @return string
     */
    public function truncate($text, $maxLength = 150, $extra = '...', $isHTML = true){
        $i = 0;
        $tags = array();
        if($isHTML){
            preg_match_all('/<[^>]+>([^<]*)/', $text, $res, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
            foreach($res as $o){
                if($o[0][1] - $i >= $maxLength)
                    break;
                $t = substr(strtok($o[0][0], " \t\n\r\0\x0B>"), 1);
                if($t[0] != '/')
                    $tags[] = $t;
                elseif(end($tags) == substr($t, 1))
                    array_pop($tags);
                $i += $o[1][1] - $o[0][1];
            }
        }
        return substr($text, 0, $maxLength = min(strlen($text),  $maxLength + $i)) . (count($tags = array_reverse($tags)) ? '</' . implode('></', $tags) . '>' : '') . (strlen($text) > $maxLength ? $extra : '');
    }
}
