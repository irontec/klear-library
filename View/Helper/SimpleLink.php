<?php
class Iron_View_Helper_SimpleLink extends Zend_View_Helper_Abstract
{
    /**
     * Returns a link generated with suplied data
     * @param string $url Url of the link
     * @param string $text Text to show on the link. If null, the $url will be shown
     * @param array $attribs Attribs for the anchor tag. (class, title, etc...)
     * @return string
     */
    public function simpleLink($url, $text = null, $attribs = array())
    {
        $url = trim($url);
        $text = trim($text);

        if (!$url) {
            return '';
        }

        if (!$text) {
            $showText = $url;
        } else{
            $showText = $text;
        }

        $attrString = ' ';
        foreach ($attribs as $key => $value) {
            $attrString .= $key . '="' . $value . '"';
        }

        return '<a href="' . $url .'" ' . $attrString . '>' . $showText . '</a>';

    }
}