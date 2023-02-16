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

        $options = \Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOptions();

        if (isset($options['restConfig']["moduleName"])) {
            if (!in_array($request->getModuleName(), $options['restConfig']["moduleName"])) {
                return;
            }
        } else if (!preg_match("/^rest/", $request->getModuleName())) {
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

            case (strstr((string) $contentType, 'application/json')):
                $this->_setBodyParams(
                    $request,
                    \Zend_Json::decode($rawBody)
                );
                break;

            case (strstr((string) $contentType, 'application/x-www-form-urlencoded')):
                parse_str((string) $rawBody, $params);
                $this->_setBodyParams($request, $params);
                break;

            case (strstr((string) $contentType, 'application/xml')):
                $config = new \Zend_Config_Xml($rawBody);
                $this->_setBodyParams($request, $config->toArray());
                break;

            default:
                if (
                    $request->isPut()
                ||
                    $request->isDelete()
                ||
                    $request->isPost()
                ) {
                    $this->_setBodyParams(
                        $request,
                        \Zend_Json::decode($rawBody)
                    );
                }

                break;
        }
    }

    protected function _setBodyParams(
        \Zend_Controller_Request_Abstract $request,
        $params
    )
    {
        $request->setParams($params);
    }

}