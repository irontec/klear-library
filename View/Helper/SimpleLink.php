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
        $finalUrl = trim($url);
        $finalText = trim($text);

        if (!$finalUrl) {
            return '';
        }

        if (!$finalText) {
            if (isset($attribs['domainOnly']) && $attribs['domainOnly']) {
                $uri = Zend_Uri_Http::fromString($finalUrl);
                $showText = $uri->getHost();
            } else {
                $showText = $finalUrl;
            }
        } else{
            $showText = $finalText;
        }

        if (isset($attribs['hash'])) {
            $finalUrl .= '#' . $attribs['hash'];
            unset ($attribs['hash']);
        }

        $attrString = ' ';
        foreach ($attribs as $key => $value) {
            $attrString .= $key . '="' . $value . '"';
        }

        return '<a href="' . $finalUrl .'" ' . $attrString . '>' . $this->view->escape($showText) . '</a>';

    }
}