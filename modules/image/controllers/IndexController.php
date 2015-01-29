<?php
/**
 * Image
 * Este modulo se encarga de gestionar las diferentes opciones de tratado de imagen (original/crop/resize)
 * y las respectivas cabeceras, para el sistema de cache.
 * Este sistema funciona con los Models-Mappers generadores por https://github.com/irontec/klear-generator/
 *
 * Inicialización en el application.ini
 * resources.frontController.moduleDirectory.image = "/opt/klear/library/Iron/modules/"
 *
 * En el mismo directorio que el application.ini tiene que estar el fichero images.ini
 * donde se configuran los diferentes perfiles que tendran las images que se usaran.
 *
 * Ejemplo del archivo images.ini:
 *
 * config.routeMap = {id}-{name}.{ext} ;"Requerido"
 * El "routeMap" se definen la estructura que va a tener la imagen.
 * En el ejemplo se pide el Primary Key separado por '-', seguido del nombre que se le a dado con
 * una separación de '.' para la extención de archivo. Con esto se buscara por Primary Key y por "name"
 * y al crear la imagen se dara la extención asignada con el respectivo mime type en las cabeceras.
 *
 * images.profile
 * "profiel" es el elemento donde se escribira la configuración de la imagen.
 *
 * images.profile.model = model ;"Requerido"
 * "model" es el nombre del model que tiene la información de la imagen.
 *
 * images.profile.fso = fso ;"Requerido"
 * "fso" es el tag con el que los generadores crear BaseName/FileSize/MimeType
 *
 * images.profileList.extend = profile
 * "extend" obtiene los parametros de otro "profile" para no definirlos de nuevo si se quiere otras medidas
 * de una misma imagen.
 *
 * @author ddniel16 <daniel@irontec.com>
 */
class Image_IndexController extends Zend_Controller_Action
{

    protected $_frontInstance;
    protected $_imageCacheConfig;
    protected $_configImages;
    protected $_namespace;
    protected $_routeMap;
    protected $_defaultRoute;
    protected $_life;

    protected $_currentProfile;
    protected $_ext;

    public function init()
    {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $this->_frontInstance = \Zend_Controller_Front::getInstance();

        $this->_imageCacheConfig = Zend_Registry::get('imageCacheConfig');
        $this->_configImages = $this->_imageCacheConfig->images;

        $this->_namespace = $this->_frontInstance->getParam(
            'bootstrap'
        )->getOption('appnamespace');

        if (!$this->_imageCacheConfig->config->life) {
            $this->_imageCacheConfig->config->life = 9999990;
        }

        $this->_routeMap = $this->_imageCacheConfig->config->routeMap;
        $this->_defaultRoute = $this->_imageCacheConfig->config->defaultRoute;
        $this->_life = $this->_imageCacheConfig->config->life;
        $this->_cacheDir = APPLICATION_PATH . '/cache/';

        $this->_initParams();

    }

    public function indexAction()
    {

        $config = $this->_setConfiguration($this->_currentProfile);

        try {

            $fetchFso = 'fetch' . ucwords($this->getFso());
            $getBaseName = 'get' . ucwords($this->getFso()) . 'BaseName';
            $getMimeType = 'get' . $this->getFso() . 'MimeType';

            $this->setFilePath(
                $this->_model->$fetchFso()->getFilePath()
            );

            $this->setBasename(
                $this->_model->$getBaseName()
            );

            $this->setMimeType(
                $this->_model->$getMimeType()
            );

        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), 404);
        }

        $frontend = array(
            'lifetime' => $this->_life,
            'automatic_serialization' => true
        );

        $backend = array(
            'cache_dir' => $this->_cacheDir,
        );

        $cache = Zend_Cache::factory(
            'core',
            'File',
            $frontend,
            $backend
        );

        $piecesKey = array(
            ucfirst(str_replace('-', '', $this->_currentProfile->changeSize)),
            ucfirst($this->getFso())
        );

        $key = implode('', $piecesKey);

        $cacheKey = md5_file(
            $this->getFilePath()
        ) . $key;

        Zend_Registry::set('cache', $cache);

        $cache = Zend_Registry::get('cache');

        $loadCache = $cache->load($cacheKey);

        if (!is_null($this->_ext)) {
            $extension = $this->_ext;
        } else {
            $extension = substr(strrchr($this->getBasename(), '.'), 1);
        }

        if (empty($loadCache)) {

            $image = new Imagick($this->getFilePath());
            $image->setImageFormat($extension);

            \Iron_Utils_PngFix::process($image);

            if (isset($this->_currentProfile->negate)) {
                if ($this->_currentProfile->negate == 'yes') {
                    $image->negateImage(0, 134217727);
                }
            }

            if (isset($this->_currentProfile->flop)) {
                if ($this->_currentProfile->flop == 'yes') {
                    $image->flopImage();
                }
            }

            switch ($config['changeSize']) {
                case 'original':
                    continue;
                    break;

                case 'crop':
                    \Iron_Imagick_Crop::init($image, $config);
                    break;

                case 'resize':
                    \Iron_Imagick_Resize::init($image, $config);
                    break;

                case 'crop-resize':
                    \Iron_Imagick_CropResize::init($image, $config);
                    break;

                case 'resize-crop':
                    \Iron_Imagick_ResizeCrop::init($image, $config);
                    break;

                case 'scale':
                    \Iron_Imagick_Scale::init($image, $config);
                    break;

                case 'circle':
                    \Iron_Imagick_Circle::init($image, $config);
                    break;

                default:
                    throw new Exception(
                        'El parametro "changeSize" invalido.'
                    );
                    break;
            }

            if (isset($this->_currentProfile->vignette)) {
                \Iron_Imagick_Vignette::init($image, $config['vignette']);
            }

            if (isset($this->_currentProfile->border)) {
                \Iron_Imagick_Border::init($image, $config['border']);
            }

            if (isset($this->_currentProfile->framing)) {
                \Iron_Imagick_Framing::init($image, $config['framing']);
            }

            $cache->save($image->getImagesBlob(), $cacheKey);

            $this->getHeaders(false, $cache, $cacheKey, $extension);

        } else {

            $request = $this->_frontInstance->getRequest();

            if ($request->getHeader('IF-MODIFIED-SINCE')) {
                $this->getHeaders(true, $cache, $cacheKey, $extension);
            } else {
                $this->getHeaders(false, $cache, $cacheKey, $extension);
            }

        }

    }

    protected function _initParams()
    {

        $request = $this->_frontInstance->getRequest();

        $profile = $request->getParam(
            'profile',
            false
        );

        $routeMap = $request->getParam(
            'routeMap',
            false
        );

        $currentProfile = new stdClass();

        foreach ($this->_configImages->$profile as $key => $val) {
            $currentProfile->$key = $val;
        }

        if (isset($currentProfile->extend)) {
            $extend = $currentProfile->extend;
            $profileExtend = $this->_configImages->$extend;

            foreach ($profileExtend as $key => $val) {
                if (!isset($currentProfile->$key)) {
                    $currentProfile->$key = $val;
                }
            }

        }

        $this->_currentProfile = $currentProfile;
        if (!$currentProfile) {
            $this->_defaultImage();
        }

        $mappers = $this->_namespace . '\\Mapper\\Sql\\' . $currentProfile->model;
        $mapper = new $mappers();

        $models = $this->_namespace . '\\Model\\' . $currentProfile->model;
        $model = new $models();

        $pattern = "/\{[^\}]+\}/";
        $resultNum = preg_match_all($pattern, $this->_routeMap, $resultados);

        $resultados = $resultados[0];

        $this->_routeMap = str_replace(
            $resultados,
            '#',
            $this->_routeMap
        );
        $this->_routeMap = array_values(
            array_filter(
                explode(
                    '#',
                    $this->_routeMap
                )
            )
        );

        $paramsResult = array();

        if (sizeof($this->_routeMap) > 0) {
            for ($i = 0; $i < sizeof($this->_routeMap); $i++) {

                $result = explode($this->_routeMap[$i], $routeMap, 2);
                $routeMap = $result[1];

                if ($i + 1 === sizeof($this->_routeMap)) {
                    $paramsResult[] = $result[0];
                    $paramsResult[] = $result[1];
                } else {
                    $paramsResult[] = $result[0];
                }

            }
        } else {
            $paramsResult[] = $routeMap;
        }

        $this->setFso(
            $currentProfile->fso
        );

        $where = $this->_prepareWhere(
            $resultados,
            $paramsResult,
            $model
        );

        $modelImage = $mapper->fetchList(
            $where,
            NULL,
            1
        );

        if (!empty($modelImage)) {
            $this->_model = $modelImage;
        } else {
            $this->_defaultImage();
        }

    }

    protected function _setConfiguration($currentProfile)
    {

        $config = array();

        if (isset($currentProfile->changeSize)) {
            $config['changeSize'] = $currentProfile->changeSize;
        } else {
            throw new Exception(
                'El parametro "changeSize" es obligatorio.'
            );
        }

        if (isset($currentProfile->size)) {
            $config['size'] = $currentProfile->size;
        }

        if (isset($currentProfile->width)) {
            $config['width'] = $currentProfile->width;
        }

        if (isset($currentProfile->height)) {
            $config['height'] = $currentProfile->height;
        }

        if (isset($currentProfile->vignette)) {

            $vignette = $currentProfile->vignette;

            $config['vignette'] = array();

            if (isset($vignette->blackPoint)) {
                $config['vignette']['blackPoint'] = $vignette->blackPoint;
            }
            if (isset($vignette->whitePoint)) {
                $config['vignette']['whitePoint'] = $vignette->whitePoint;
            }
            if (isset($vignette->x)) {
                $config['vignette']['x'] = $vignette->x;
            }
            if (isset($vignette->y)) {
                $config['vignette']['y'] = $vignette->y;
            }

        }

        if (isset($currentProfile->border)) {

            $border = $currentProfile->border;

            $config['border'] = array();

            if (isset($border->color)) {
                $config['border']['color'] = $border->color;
            }
            if (isset($border->width)) {
                $config['border']['width'] = $border->width;
            }
            if (isset($border->height)) {
                $config['border']['height'] = $border->height;
            }

        }

        if (isset($currentProfile->framing)) {

            $framing = $currentProfile->framing;

            $config['framing'] = array();

            if (isset($framing->color)) {
                $config['framing']['color'] = $framing->color;
            }

            if (isset($framing->width)) {
                $config['framing']['width'] = $framing->width;
            }

            if (isset($framing->height)) {
                $config['framing']['height'] = $framing->height;
            }

            if (isset($framing->innerBevel)) {
                $config['framing']['innerBevel'] = $framing->innerBevel;
            }

            if (isset($framing->outerBevel)) {
                $config['framing']['outerBevel'] = $framing->outerBevel;
            }

        }

        return $config;

    }

    /**
     * Crea o Carga los Headers para la cache
     * @param Boolena $isCache
     * @param Zend_Cache $cache
     * @param String $cacheKey
     */
    public function getHeaders($isCache, $cache, $cacheKey, $extencion)
    {

        $mimeType = $this->_getMimeTypeByExtencion($extencion);

        $response = $this->_frontInstance->getResponse();

        $expire = gmdate('D, d M Y H:i:s', time() + $this->_life);
        $modified = gmdate('D, d M Y H:i:s', time()).' GMT';

        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'public', true);
        $response->setHeader('Cache-control', 'max-age=' . 60*60*24*14, true);
        $response->setHeader('Expires', $expire .' GMT', true);
        $response->setHeader('Last-Modified', $modified, true);
        $response->setHeader('If-Modified-Since', $modified, true);
        $response->setHeader('ETag', $cacheKey, true);
        $response->setHeader('Content-Type', $mimeType, true);
        $response->setHeader('Content-transfer-encoding', 'binary', true);

        if ($isCache) {
            $response->setHttpResponseCode(304);
            $response->sendHeaders();
        } else {
            $response->setBody($cache->load($cacheKey));
            $response->sendHeaders();
        }

    }

    /**
     * MimeType to Image
     * @param String $mimeType
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * MimeType to Image
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Base name to Image
     * @param String $basename
     */
    public function setBasename($basename)
    {
        $this->basename = $basename;
        return $this;
    }

    /**
     * Base name to Image
     */
    public function getBasename()
    {
        return $this->basename;
    }

    /**
     * File path to Image
     * @param String $filePath
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * File path to Image
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Height to Image
     * @param String $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Height to Image
     */
    public function getHeight()
    {
        if (isset($this->height)) {
            return $this->height;
        } else {
            return null;
        }
    }

    /**
     * Width to Image
     * @param String $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Width to Image
     * @return String
     */
    public function getWidth()
    {
        if (isset($this->width)) {
            return $this->width;
        } else {
            return null;
        }
    }

    /**
     * FSO de la imagen que se quiere obtener
     * @param String $fso
     */
    public function setFso($fso)
    {
        $this->fso = $fso;
        return $this;
    }

    /**
     * FSO de la imagen que se quiere obtener
     * @return String
     */
    public function getFso()
    {
        return $this->fso;
    }

    /**
     * Prepare el where que buscara el modelo de de la imagen.
     * @param array $resultados
     * @param array $paramsResult
     * @param Object $model
     * @return array
     */
    protected function _prepareWhere($resultados, $paramsResult, $model)
    {

        $front = $this->_frontInstance
            ->getRouter()
            ->getFrontController();


        $availableLangs = $model->getAvailableLangs();

        if (count($availableLangs) > 0) {

            $bootstrap = \Zend_Controller_Front::getInstance()->getParam('bootstrap');

            if (is_null($bootstrap)) {
                $conf = new \Zend_Config_Ini(
                    APPLICATION_PATH . '/configs/application.ini',
                    APPLICATION_ENV
                );
                $conf = (Object) $conf->toArray();
            } else {
                $conf = (Object) $bootstrap->getOptions();
            }

            if (isset($conf->translate['requestParam'])) {
                $langParam = $conf->translate['requestParam'];
            } else {
                /**
                 * Iron_Controller_Plugin_PublicTranslator
                 * DEFAULT_REQUEST_LANGUAGE_PARAM
                 */
                $langParam = 'language';
            }

            $lang = $front->getRequest()->getParam($langParam);

            if (is_null($lang)) {
                $request = $front->getRequest();
                $lang = $request->getCookie($langParam);
            }

            if (empty($lang)) {

                if (isset($conf->translate)) {
                    $lang = $conf->translate['defaultLanguage'];
                }
            }

        }

        $pieces = array();
        $where = array();

        foreach ($resultados as $result) {
            $result = trim($result, '{');
            $pieces[] = trim($result, '}');
        }

        $columnsList = $model->getColumnsList();
        $multiLangColumnsList = $model->getMultiLangColumnsList();

        foreach ($pieces as $key => $piece) {
            if ($piece !== 'ext') {
                if ($piece === 'basename') {

                    $basename = $this->getFso() . 'BaseName';

                    $extension = substr(strrchr($paramsResult[$key], '.'), 1);
                    if ($extension === false) {
                        $where[$basename . ' like ?'] = $paramsResult[$key] . '.%';
                    } else {
                        $searchBasename = str_replace('.' . $extension, '', $paramsResult[$key]);
                        $where[$basename . ' like ?'] = $searchBasename . '.%';
                    }
                } else {

                    if (isset($columnsList[$piece])) {
                        if (!empty($multiLangColumnsList)) {
                            if (isset($multiLangColumnsList[$piece])) {
                                $where[$piece . '_' . $lang . ' = ?'] = $paramsResult[$key];
                            } else {
                                $where[$piece . ' = ?'] = $paramsResult[$key];
                            }
                        }
                    }

                }
            } else {
                $this->_ext = $paramsResult[$key];
            }
        }

        return $where;

    }

    protected function _getMimeTypeByExtencion($extension)
    {

        switch ($extension) {
            case 'js':
                return 'application/x-javascript';
            case 'json':
                return 'application/json';
            case 'jpg':
            case 'jpeg':
            case 'jpe':
                return 'image/jpg';
            case 'png':
            case 'gif':
            case 'bmp':
            case 'tiff':
                return 'image/' . strtolower($extension);
            case 'css':
                return 'text/css';
            case 'xml':
                return 'application/xml';
            case 'doc':
            case 'docx':
                return 'application/msword';
            case 'xls':
            case 'xlt':
            case 'xlm':
            case 'xld':
            case 'xla':
            case 'xlc':
            case 'xlw':
            case 'xll':
                return 'application/vnd.ms-excel';
            case 'ppt':
            case 'pps':
                return 'application/vnd.ms-powerpoint';
            case 'rtf':
                return 'application/rtf';
            case 'pdf':
                return 'application/pdf';
            case 'html':
            case 'htm':
            case 'php':
                return 'text/html';
            case 'txt':
                return 'text/plain';
            case 'mpeg':
            case 'mpg':
            case 'mpe':
                return 'video/mpeg';
            case 'mp3':
                return 'audio/mpeg3';
            case 'wav':
                return 'audio/wav';
            case 'aiff':
            case 'aif':
                return 'audio/aiff';
            case 'avi':
                return 'video/msvideo';
            case 'wmv':
                return 'video/x-ms-wmv';
            case 'mov':
                return 'video/quicktime';
            case 'zip':
                return 'application/zip';
            case 'tar':
                return 'application/x-tar';
            case 'swf':
                return 'application/x-shockwave-flash';
        }

    }

    /**
     * Crea una imagen por defecto cuando no hay errores.
     */
    protected function _defaultImage()
    {

        $image = new Imagick();
        $image->newImage(100, 100, new ImagickPixel('#F2F2F2'));
        $image->setImageFormat('png');

        header('Content-type: image/png');

        echo $image;
        die();

    }

}