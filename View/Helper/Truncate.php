<?php
//require_once('Zend/View/Helper/Abstract.php');

class Iron_View_Helper_Truncate extends Zend_View_Helper_Abstract
{
    protected $_tags = array();
    protected $_extraInline = false;

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
    public function truncate($text, $maxLength = 150, $extra = '...', $isHtml = true, $extraInline = false)
    {
        $this->_extraInline = $extraInline;

        $i = 0;
        $this->_tags = array();

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
                if ($tag[0] != '/' && ! $this->_isSelfClosingTag($object[0][0])) {
                    $this->_tags[] = $tag;
                } else if (end($this->_tags) == substr($tag, 1)) {
                    array_pop($this->_tags);
                }
                $i += $object[1][1] - $object[0][1];
            }
        }

        $shortenedText = substr($text, 0, $maxLength = min(strlen($text), $maxLength + $i));
        $shortenedText .= $this->_getExtraText($text, $maxLength, $extra);

        return $shortenedText;
    }

    protected function _isSelfClosingTag($tag)
    {
        return preg_match("/<[^>]+?\/>/", $tag) == 1;

    }

    protected function _getExtraText($text, $maxLength, $extra)
    {
        if ($this->_extraInline) {
            return $this->_getExtra($text, $maxLength, $extra)
                 . $this->_getClosingTags();
        } else {
            return $this->_getClosingTags()
                 . $this->_getExtra($text, $maxLength, $extra);
        }

    }

    protected function _getClosingTags()
    {
        if (count($this->_tags)) {
            $tags = array_reverse($this->_tags);
            return '</' . implode('></', $tags) . '>';
        }
        return '';
    }

    protected function _getExtra($text, $maxLength, $extra) {
        if (strlen($text) > $maxLength) {
            return $extra;
        }
        return '';
    }
}
