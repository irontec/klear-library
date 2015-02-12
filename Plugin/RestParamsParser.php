<?php
/**
 * Conversión parámetros PUT/DELETE en UserParams
 * @author Jabi Infante
 *
 */
class Iron_Plugin_RestParamsParser extends \Zend_Controller_Plugin_Abstract
{

    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {

        if (!preg_match("/^rest/", $request->getModuleName())) {
            return;
        }

        if (get_class($request) == 'Zend_Controller_Request_Http') {
            $this->_initParser($request);
        }

    }

    protected function _initParser(Zend_Controller_Request_Abstract $request)
    {
        if (
            !$request->isPut()
            && !$request->isDelete()
            && !$request->isPost()
        ) {
            return;
        }

        $contentType = $request->getHeader('Content-Type');
        $rawBody     = $request->getRawBody();

        if (!$rawBody) {
            return;
        }

        switch (true) {
            case (strstr($contentType, 'application/json')):
                $this->_setBodyParams($request, Zend_Json::decode($rawBody));
                break;
            case (strstr($contentType, 'application/xml')):
                $config = new Zend_Config_Xml($rawBody);
                $this->_setBodyParams($request, $config->toArray());
                break;
            default:
                if ($request->isPut() || $request->isDelete()) {
                    parse_str($rawBody, $params);
                    $this->_setBodyParams($request, $params);
                }
                break;
        }
    }

    protected function _setBodyParams(Zend_Controller_Request_Abstract $request, $params)
    {
        $request->setParams($params);
    }

}