<?php
/**
 * Complemento del Modulo "ImageCache"
 *
 * Con este helper se crean automaticamente la url de una imagen que tenga perfil en el images.ini
 * Esta url se crea en base al routeMap.
 *
 * $this->_helper->UrlImageCache->generateUrl($model, 'profile');
 *
 * Si en el routeMap se declaran parametros que existen en el model, con pasar el model y en profile es suficiente.
 * En caso contrario, si hay parametros extras como {ext}, hay 2 opciones para construir la url.
 *
 * 1) Al generateUrl se le pasa como primer elemento el model y como segundo un array con el "profile" y los parametros
 *    extras. Ejemplo:
 *    $args = array(
 *        'profile' => 'example',
 *        'ext' => 'png',
 *    );
 *
 * 2) Otra opción es pasar como primer parametro un array vacío y como segundo un array con el profile y todos los
 *    parametros definidos en el routeMap. Ejemplo:
 *    $args = array(
 *        'profile' => 'example',
 *        'id' => 1,
 *        'slug' => 'image-example',
 *        'ext' => 'png',
 *    );
 *
 * @author ddniel16 <daniel@irontec.com>
 */
class Iron_Controller_Action_Helper_UrlImageCache extends Zend_Controller_Action_Helper_Abstract
{

    protected $_routeMap;
    protected $_images;
    protected $_view;

    public function init()
    {

        $imagesPath = APPLICATION_PATH . '/configs/images.ini';

        if (!file_exists($imagesPath)) {
            throw new Exception(
                'No Existe el fichelo de configuracion images.ini',
                404
            );
        }

        $imageConfig = new \Zend_Config_Ini(
            $imagesPath,
            APPLICATION_ENV
        );

        $this->_routeMap = $imageConfig->config->routeMap;
        $this->_images = $imageConfig->images;

        $this->_view = new \Zend_View();

    }

    /**
     *
     */
    public function generateUrl($model, $args)
    {

        if (is_array($args)) {
            $profile = $args['profile'];
        } else {
            $profile = $args;
        }

        if (!isset($this->_images->$profile)) {
            throw new Exception(
                'El profile "' . $profile . '" no existe.',
                404
            );
        }

        $fso = $this->_images->$profile->fso;

        $pattern = "/\{[^\}]+\}/";
        $resultNum = preg_match_all($pattern, $this->_routeMap, $resultados);

        $resultados = $resultados[0];

        $tags = str_replace(
            $resultados,
            '#',
            $this->_routeMap
        );
        $tags = array_values(
            array_filter(
                explode(
                    '#',
                    $tags
                )
            )
        );

        $paramsResult = array();

        $routeMap = $this->_routeMap;

        if (sizeof($tags) > 0) {
            for ($i = 0; $i < sizeof($tags); $i++) {

                $result = explode($tags[$i], $routeMap, 2);
                $routeMap = $result[1];

                if ($i + 1 === sizeof($tags)) {
                    $paramsResult[] = $result[0];
                    $paramsResult[] = $result[1];
                } else {
                    $paramsResult[] = $result[0];
                }

            }
        } else {
            $paramsResult[] = $routeMap;
        }

        $params = array();

        foreach ($paramsResult as $result) {
            $result = trim($result, '{');
            $params[] = trim($result, '}');
        }

        $paramsModel = array();
        $paramsExtras = array();

        if (!empty($model)) {
            $columnsList = $model->getColumnsList();
        } else {
            $columnsList = array();
        }

        foreach ($params as $key) {
            if ($key === 'basename') {
                if (isset($columnsList[$fso . 'BaseName'])) {
                    $paramsModel[$key] = 'get' . ucwords($fso)  . 'BaseName';
                }
            } else {
                if (isset($columnsList[$key])) {
                    $paramsModel[$key] = 'get' . ucwords($key);
                } else {
                    if (isset($args[$key])) {
                        $paramsExtras[$key] = $args[$key];
                    } else {
                        throw new Exception(
                            'El parametro "' . $key . '" no existe o no se a especificado ',
                            404
                        );
                    }
                }
            }
        }

        $prepareReplace = array();
        $routeMapFinal = $this->_routeMap;

        if (!empty($paramsModel)) {
            foreach ($paramsModel as $key => $gets) {
                $routeMapFinal = str_replace(
                    '{' . $key . '}',
                    $model->$gets(),
                    $routeMapFinal
                );
            }
        }

        if (!empty($paramsExtras)) {
            foreach ($paramsExtras as $key => $extra) {
                $routeMapFinal = str_replace(
                    '{' . $key . '}',
                    $extra,
                    $routeMapFinal
                );
            }
        }

        $pattern = "/\{[^\}]+\}/";
        $resultNum = preg_match_all($pattern, $routeMapFinal, $resultados);

        if ($resultNum > 0) {
            throw new Exception(
                'Hay parametros sin definir',
                404
            );
        }

        $url = $this->_view->url(
            array(
                'profile' => $profile,
                'routeMap' => $routeMapFinal
            ),
            'image'
        );

        return $url;

    }

}