<?php

/**
 * Plugin para servir images con cache automaticamente.
 * teniendo en cuenta la configuración del images.ini donde se define:
 *     El Model donde esta los datos necesarios
 *     El FSO, por si tiene mas de uno
 *     EL changeSize que puede ser:
 *         crop   = Hace un "crop"
 *         resize = Hace un "resize"
 *         FALSE  = Muesta la imagen en su tamaño original
 * @author Daniel Rendon <dani@irontec.com>
 */

class Iron_Controller_Plugin_ImageCache extends Zend_Controller_Plugin_Abstract
{

    protected $_frontInstance;
    protected $_imageCacheConfig;
    protected $_configImages;
    protected $_namespace;
    protected $_currentConfig;
    protected $_life;
    protected $_cacheDir;

    public function __construct()
    {

        $this->_frontInstance = \Zend_Controller_Front::getInstance();

        $this->_imageCacheConfig = Zend_Registry::get('imageCacheConfig');

        $this->_configImages = $this->_imageCacheConfig->images;

        $this->_namespace = $this->_frontInstance->getParam(
            'bootstrap'
        )->getOption('appnamespace');

        $this->_initParams();

        if (!$this->_imageCacheConfig->config->life) {
            throw new Exception('life not defined');
        }

        $this->_life = $this->_imageCacheConfig->config->life;
        $this->_cacheDir = APPLICATION_PATH . '/cache/';

    }

    protected function _initParams()
    {

        $request = $this->_frontInstance->getRequest();

        $modelParam = $request->getParam('model', false);
        if (!$modelParam) {
            throw new Exception('Model not Exists');
        }
        if (!$this->_configImages->$modelParam) {
            throw new Exception('Model not defined');
        }

        $this->_currentConfig = $this->_configImages->$modelParam;

        /**
         * Comprobar que existe el Mapper
         * @var unknown_type
         */
        $modelConfig = $this->_currentConfig->model;
        $mappers = $this->_namespace . '\\Mapper\\Sql\\' . $modelConfig;
        $mapper = new $mappers();

        $sizeParam = $request->getParam('size', false);
        if (!$sizeParam) {
            throw new Exception('Size not Exists');
        }

        if (!$this->_currentConfig->$sizeParam) {
            throw new Exception('Size not defined');
        }
        $this->_sizeInstance = $sizeParam;

        $slugParam = $request->getParam('routeMap', false);
        if (!$slugParam) {
            throw new Exception('Slug is Required');
        }

        $routeMap = $this->_imageCacheConfig->config->routeMap;
        $explodeSlug = explode('-', $slugParam, 2);

        $params = array();

        foreach ($routeMap as $key => $route) {
            $params[$route] = $explodeSlug[$key];
        }

        $this->setFso(
            $this->_configImages->$modelParam->$sizeParam->fso
        );

        $where = array(
            'id = ? ' => $params['id'],
            $this->getFso() . 'BaseName = ? ' => $params['basename']
        );

        $modelCollection = $mapper->fetchList($where);
        if (sizeof($modelCollection) > 1) {
            throw new Exception(
                'your config for images is broken! more than 1 picture'
            );
        }
        $this->_model = array_shift($modelCollection);

        if (empty($this->_model)) {
            throw new Exception('Image Not Found', 404);
        }

    }

    public function serveCachedImage()
    {

        $sizeInstance = $this->_sizeInstance;

        $sizeCongif = $this->_currentConfig->$sizeInstance;

        if ($sizeCongif) {
            $this->getConfigChangeSize($sizeCongif);
        } else {
            throw new Exception(
                'size not defined'
            );
        }

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

        $cacheKey = md5_file(
            $this->getFilePath()
        ) . $this->_sizeInstance . 'x' . $this->getFso();

        Zend_Registry::set('cache', $cache);

        $cache = Zend_Registry::get('cache');

        $loadCache = $cache->load($cacheKey);

        if (empty($loadCache)) {

            $extension = substr(strrchr($this->getBasename(), "."), 1);

            $image = new Imagick($this->getFilePath());
            $image->setImageFormat($extension);

            \Iron_Utils_PngFix::process($image);

            if ($sizeCongif->changeSize == 'crop') {
                $image->cropthumbnailimage(
                    $this->getWidth(),
                    $this->getHeight()
                );
            } else {
                $image->resizeImage(
                    $this->getWidth(),
                    $this->getHeight(),
                    imagick::FILTER_LANCZOS,
                    1
                );
            }

            $cache->save($image->getImagesBlob(), $cacheKey);

            $this->getHeaders(false, $cache, $cacheKey);

        } else {

            $request = $this->_frontInstance->getRequest();

            if ($request->getHeader('IF-MODIFIED-SINCE')) {
                $this->getHeaders(true, $cache, $cacheKey);
            } else {
                $this->getHeaders(false, $cache, $cacheKey);
            }

        }

    }

    /**
     *
     * @param Boolena $isCache
     * @param Zend_Cache $cache
     * @param String $cacheKey
     */
    public function getHeaders($isCache, $cache, $cacheKey)
    {

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
        $response->setHeader('Content-Type', $this->getMimeType(), true);
        $response->setHeader('Content-transfer-encoding', 'binary', true);

        if ($isCache) {
            $response->setHttpResponseCode(304);
            $response->sendHeaders();
        } else {
            $response->setBody($cache->load($cacheKey));
            $response->sendHeaders();
        }

    }

    public function getFso()
    {
        return $this->fso;
    }

    public function setFso($fso)
    {
        $this->fso = $fso;
        return $this;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getBasename()
    {
        return $this->basename;
    }

    public function setBasename($basename)
    {
        $this->basename = $basename;
        return $this;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param String $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param String $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Segun el metodo hay el "height" y "width" son obligatorios u opcionales
     * @param $sizeCongif
     */
    public function getConfigChangeSize($sizeCongif)
    {

        switch ($sizeCongif->changeSize) {
            case 'crop':

                if (
                    is_null($sizeCongif->height)
                    ||
                    is_null($sizeCongif->width)
                ) {
                    var_dump('Crop required height and width');
                } else {
                    $this->setHeight($sizeCongif->height);
                    $this->setWidth($sizeCongif->width);
                }

                break;

            case 'resize':

                if (is_null($sizeCongif->height)) {
                    $this->setHeight(0);
                } else {
                    $this->setHeight($sizeCongif->height);
                }

                if (is_null($sizeCongif->width)) {
                    $this->setWidth(0);
                } else {
                    $this->setWidth($sizeCongif->width);
                }

                break;

            default:
                $this->setHeight('auto');
                $this->setWidth('auto');
                break;
        }

    }

}