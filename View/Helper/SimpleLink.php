<?php
class Iron_View_Helper_SimpleLink extends Zend_View_Helper_Abstract
{
    /**
     * Returns a link generated with suplied data
     * @param string $url Url of the link
     * @param string $text Text to show on the link. If null, the $url will be shown
     * @param array $attribs Attribs for the anchor tag. (class, title, etc...). It may also admit some
     * @param string $escapeText Bool Escape text or not. By default text is escaped to avoid XSS
     * @return string
     */
    public function simpleLink($url, $text = null, $attribs = array(), $escapeText = true)
    {
        $finalUrl = trim($url);
        $finalText = trim($text);

        if (!$finalUrl) {
            return '';
        }

        if (!$finalText) {
            $finalText = $finalUrl;
        }

        if ($escapeText) {
            $finalText = $this->view->escape($finalText);
        }

        $attrString = ' ';
        foreach ($attribs as $key => $value) {
            $attrString .= $key . '="' . $value . '"';
        }

        return '<a href="' . $finalUrl .'" ' . $attrString . '>' . $finalText . '</a>';

    }
}