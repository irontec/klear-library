<?php
class Iron_Images
{
    private $_imageSrc;

    private $_image;
    private $_width;
    private $_height;

    private $_resolution;
    /**
     * @param string $filePath
     * @Throws Exception
     */
    function __construct($filePath = null)
    {
        if (! is_null($filePath)) {

            $this->_imageSrc = $filePath;
            $this->_loadImageAndDimensions($this->_imageSrc);
        }
    }

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $this->_imageSrc = $filePath;
    }

    /**
     * @Throws Exception
     */
    public function load()
    {
        $this->_loadImageAndDimensions($this->_imageSrc);
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->_width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->_height;
    }

    /**
     * @return array (x,y)
     */
    public function getResolution()
    {
        return $this->_resolution;
    }

    public function setResolution($x, $y)
    {
        $this->_resolution = array($x, $y);
        //$this->_image->setResolution($x, $y);
        $this->_image->resampleImage($x, $y, imagick::FILTER_UNDEFINED, 1);

        return $this;
    }

    public function setFormat($format, $quality = 100)
    {
        switch(strtolower($format)) {

            case 'jpg':
            case 'jpeg':

                $this->_image->setCompression(Imagick::COMPRESSION_JPEG);
                $this->_image->setCompressionQuality($quality);
                $this->_image->setImageFormat("jpg");
                break;

            default:

             Throw new Exception("Unknown format " . $format);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function resize($newWidth, $newHeight, $strategy = "auto")
    {
        $dimensions = $this->_getOptimalDimensions($newWidth, $newHeight, $strategy);

        $optimalWidth  = $dimensions->optimalWidth;
        $optimalHeight = $dimensions->optimalHeight;

        $resp = false;
        while ($this->_image->previousImage()) {

            $resp = $this->_image->resizeImage(
                $dimensions->optimalWidth,
                $dimensions->optimalHeight,
                \Imagick::FILTER_LANCZOS,
                0.9,
                false
            );

        }

        $this->_loadGeometry();
        return $resp;
    }

    /**
     * @return bool
     * @Throws ImagickException
     */
    public function crop($newWidth, $newHeight, $x = 0, $y = 0, $strategy = "exact")
    {
        $dimensions = $this->_getOptimalDimensions($newWidth, $newHeight, $strategy);
        $optimalWidth  = $dimensions->optimalWidth;
        $optimalHeight = $dimensions->optimalHeight;

        if ($this->_image->getNumberImages() > 1) {

            $this->_image = $this->_image->coalesceImages();
        }

        $resp = false;
        while ($this->_image->previousImage()) {

            $resp = $this->_image->cropImage($optimalWidth, $optimalHeight, $x, $y);
            $this->_image->setImagePage(0, 0, 0, 0);
        }

        $this->_loadGeometry();
        return $resp;
    }

    /**
     * Ambas caras serán escaladas a una proporción menor hasta que la
     * comparación sea menor que el parámetro dado para la cara.
     *
     * @param int $width
     * @param int $height
     * @return bool
     */
    public function thumbnailImage($maxWidth, $maxHeight)
    {
        $resp = false;

        $resp = $this->_image->thumbnailImage($maxWidth, $maxHeight, true);
        while ($this->_image->previousImage()) {

            $resp = $this->_image->thumbnailImage($maxWidth, $maxHeight, true);
        }

        $this->_loadGeometry();
        return $resp;
    }

    /**
     * Crea una miniatura de tamaño fijo ampliando o reduciendo de escala la imagen
     * y recortando un área específica desde el centro.
     *
     * @param int $width
     * @param int $height
     * @return bool
     */
    public function cropThumbnailImage($width, $height)
    {
        $resp = $this->_image->cropThumbnailImage($width, $height);
        while ($this->_image->previousImage()) {

            $resp = $this->_image->cropThumbnailImage($width, $height);
        }

        $this->_loadGeometry();
        return $resp;
    }

    /**
     * Escala una imagen proporcionalmente.
     * @return bool
     */
    public function scale($escala = 0.5)
    {
        $width = round($this->_width * $escala);
        $height = round($this->_height * $escala);

        $resp = $this->resize($width, $height);
        $this->_loadGeometry();
        return $resp;
    }

    /**
     * Añade una marca de agua a la imagen.
     * Si la marca de agua es mayor que la imagen se escala antes de ser insertada
     *
     * @param string ruta a la marca de agua
     * @param string $position : center, topleft, topright, bottomleft o bottomright
     * @return bool
     */
    public function addWatermark($waterMarkPath, $position = 'bottomright')
    {
        $watermark = new Imagick();
        $watermark->readImage($waterMarkPath);

        $width = $watermark->getImageWidth();
        $height = $watermark->getImageHeight();

        if ($this->_height < $height || $this->_width < $width) {

            // resize the watermark
            $watermark->scaleImage($this->_height, $this->_width, true);
            $width = $watermark->getImageWidth();
            $height = $watermark->getImageHeight();
        }

        list($x, $y) = $this->_calculateWatermarkPosition($width, $height, $position);

        return $this->_image->compositeImage($watermark, Imagick::COMPOSITE_OVER, $x, $y);
    }

    /**
     * @return array x,y
     */
    private function _calculateWatermarkPosition($width, $height, $position)
    {
        switch ($position) {

            case 'topleft':
                $x = 0;
                $y = 0;
                break;

            case 'topright':
                $x = $this->_width - $width;
                $y = 0;
                break;

            case 'bottomleft':
                $x = 0;
                $y = $this->_height - $height;
                break;

            case 'bottomright':
                $x = $this->_width - $width;
                $y = $this->_height - $height;
                break;

            case 'center':
            default :
                $x = ($this->_width - $width) / 2;
                $y = ($this->_height  - $height) / 2;
        }

        return array($x, $y);
    }

    /**
     * @return bool
     */
    public function saveImage($savePath)
    {
        return $this->_image->writeImages($savePath, true);
    }

    /**
     * @return string
     */
    public function getRaw()
    {
        return $this->_image->getImagesBlob();
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->_image->getImageFormat();
    }

    private function _loadImageAndDimensions($file) 
    {
        $this->_image = new \Imagick($file);
        $this->_loadGeometry();
    }

    private function _loadGeometry()
    {
        $geometry = $this->_image->getImageGeometry();

        $this->_width  = $geometry['width'];
        $this->_height = $geometry['height'];

        $this->_resolution = $this->_image->getImageResolution();
    }

    private function _getOptimalDimensions($newWidth, $newHeight, $option)
    {
        $results = new stdClass;

        switch ($option)
        {
            case 'exact':

                $results->optimalWidth = $newWidth;
                $results->optimalHeight= $newHeight;
                break;
            case 'portrait':

                $results->optimalWidth = $this->_getSizeByFixedHeight($newHeight);
                $results->optimalHeight= $newHeight;
                break;
            case 'landscape':

                $results->optimalWidth = $newWidth;
                $results->optimalHeight= $this->_getSizeByFixedWidth($newWidth);
                break;
            case 'auto':

                $dimensions = $this->_getSizeByAuto($newWidth, $newHeight);
                $results->optimalWidth = $dimensions->optimalWidth;
                $results->optimalHeight = $dimensions->optimalHeight;
                break;
        }

        return $results;
    }

    private function _getSizeByFixedHeight($newHeight)
    {
        $ratio = $this->_width / $this->_height;
        $newWidth = $newHeight * $ratio;
        return $newWidth;
    }

    private function _getSizeByFixedWidth($newWidth)
    {
        $ratio = $this->_height / $this->_width;
        $newHeight = $newWidth * $ratio;
        return $newHeight;
    }

    private function _getSizeByAuto($newWidth, $newHeight)
    {
        $results = new stdClass;

        if ($this->_height < $this->_width) {
            //Image is wider (landscape)

            $results->optimalWidth = $newWidth;
            $results->optimalHeight= $this->_getSizeByFixedWidth($newWidth);

        } elseif ($this->_height > $this->_width) {
            //Image is taller (portrait)

            $results->optimalWidth = $this->_getSizeByFixedHeight($newHeight);
            $results->optimalHeight= $newHeight;

        } else {
            // Square

            if ($newHeight < $newWidth) {

                $results->optimalWidth = $newWidth;
                $results->optimalHeight= $this->_getSizeByFixedWidth($newWidth);

            } else if ($newHeight > $newWidth) {

                $results->optimalWidth = $this->_getSizeByFixedHeight($newHeight);
                $results->optimalHeight= $newHeight;

            } else {

                $results->optimalWidth = $newWidth;
                $results->optimalHeight= $newHeight;
            }
        }

        return $results;
    }
}