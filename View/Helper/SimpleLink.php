<?php
class Iron_View_Helper_SimpleLink extends Zend_View_Helper_Abstract
{
    /**
     * Returns a link generated with suplied data
     * @param string $url Url of the link
     * @param string $text Text to show on the link. If null, the $url will be shown
     * @param array $attribs Attribs for the anchor tag. (class, title, etc...). It may also admit some
     * @return string
     */
    public function simpleLink($url, $text = null, $attribs = array())
    {
        $finalUrl = trim($url);
        $finalText = trim($text);

        if (!$finalUrl) {
            return '';
        }

        $attrString = ' ';
        foreach ($attribs as $key => $value) {
            $attrString .= $key . '="' . $value . '"';
        }

        if (!$finalText) {
            $finalText = $finalUrl;
        }

        return '<a href="' . $finalUrl .'" ' . $attrString . '>' . $this->view->escape($finalText) . '</a>';

    }
}