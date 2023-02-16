<?php

/**
 * Plugin que comprueba el body de la peticición y completa la request con los campos encontrados
 */
class Iron_Controller_Plugin_AutoParseRequestBody extends Zend_Controller_Plugin_Abstract
{

    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {

        //TODO: Investigar porque en fpm rquest no viene con el rawBody en content, y si ocurre en mpm
        //TODO: Si existe el parametro 'qqfile' es que viene un fichero de klear en el body
        if (method_exists($request, 'getRawBody') && !isset($_GET['qqfile'])) {
            $requestBody = $request->getRawBody();
            if (!empty($requestBody)) {

                //TODO: Comprobar Content-type del request para soportar JSON, XML, etc...
                $requestBody = json_decode((string) $requestBody, true);
                if (!is_null($requestBody)) {
                    foreach ($requestBody as $param => $value) {
                        $request->setParam($param, $value);
                    }
                }
            }
        }

    }

}
