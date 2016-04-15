<?php

/**
 * Plugin que comprueba el body de la peticición y completa la request con los campos encontrados

 */
class Iron_Controller_Plugin_AutoParseRequestBody extends Zend_Controller_Plugin_Abstract
{
    public function routeShutdown(Zend_Controller_Request_Abstract $request) {

        //TODO: Investigar porque en fpm rquest no viene con el rawBody en content, y si ocurre en mpm
        if (method_exists($request,'getRawBody')) {
            $requestBody = $request->getRawBody();
            if (!empty($requestBody)) {

                //TODO: Comprobar Content-type del request para soportar JSON, XML, etc...
                $requestBody = json_decode($requestBody, true);
                if (!is_null($requestBody)) {
                    foreach($requestBody as $param => $value) {
                        $request->setParam($param, $value);
                    }
                }
            }
        }
    }
}
