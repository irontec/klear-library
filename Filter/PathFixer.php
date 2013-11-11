<?php
/**
 * @author Mikel Madariaga
 *
 * Comprueba las rutas de imágenes internas, reemplazando las rutas en caso de discordancia de baseUrl
 *
 */
class Iron_Filter_PathFixer implements Zend_Filter_Interface
{
    public function filter($value)
    {
        return $this->fix($value);
    }

    public function fix($html)
    {
        if (empty($html)) {
            return $html;
        }
        $fixedContent = false;

        $dom = new \DomDocument;
        @$dom->loadHTML(utf8_decode($html));
        $images = $dom->getElementsByTagName('img');

        $view = \Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
        $baseUrl = $view->baseUrl("/");

        foreach ($images as $image) {
            if ($image->hasAttribute('data-uri')) {
                $uri = $image->getAttribute('data-uri');
                $src = $image->getAttribute('src');

                if ($src !== $baseUrl . $uri) {
                    $image->setAttribute('src', $baseUrl . $uri);
                    $fixedContent = true;
                }
            }
        }

        if (!$fixedContent) {
            return $html;
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return $html;
        }

        $wrapperChildList = $dom->firstChild->childNodes;
        $fixedContent = '';
        foreach ($body->childNodes as $node) {
            $fixedContent .= $dom->saveHTML($node);
        }

        return $fixedContent;
    }
}
