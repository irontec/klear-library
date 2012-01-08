<?php
require_once('Zend/View/Helper/Abstract.php');

class Iron_View_Helper_Truncate extends Zend_View_Helper_Abstract
{


    /**
     * Recibe $text y devuelve $text truncado a $maxLength caracteres.
     * Si es html ($isHtml), se encargará de cerrar todos los tags abiertos (y no contarlos para max_length)
     * Siempre que se trunque el texto, se añadirá $extra al final del string devuelto
     *
     * Uso (Desde la vista)
     * $this->truncate($registro,20,'<a href="#more">[más]</a>',true);
     *
     * @param string $text
     * @param int $maxLength
     * @param string $extra
     * @param bool $isHtml
     * @return string
     */
    public function truncate($text, $maxLength = 150, $extra = '...', $isHtml = true)
    {
        $i = 0;
        $tags = array();
        if ($isHtml) {
            preg_match_all(
                '/<[^>]+>([^<]*)/',
                $text,
                $result,
                PREG_OFFSET_CAPTURE | PREG_SET_ORDER
            );

            foreach ($result as $object) {
                if ($object[0][1] - $i >= $maxLength) {
                    break;
                }
                $tag = substr(strtok($object[0][0], " \t\n\r\0\x0B>"), 1);
                if ($tag[0] != '/') {
                    $tags[] = $tag;
                } else if (end($tags) == substr($tag, 1)) {
                    array_pop($tags);
                }
                $i += $object[1][1] - $object[0][1];
            }
        }
        return substr($text, 0, $maxLength = min(strlen($text), $maxLength + $i))
                . (count($tags = array_reverse($tags)) ? '</' . implode('></', $tags) . '>' : '')
                . (strlen($text) > $maxLength ? $extra : '');
    }
}
