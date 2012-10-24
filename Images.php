<?php
class Iron_Images
{
    private $_imageSrc;

    private $_image;
    private $_width;
    private $_height;

    /**
     * @param string $filePath
     * @Throws Exception
     */
    function __construct($filePath = null) {
        if (! is_null($filePath)) {

            $this->_imageSrc = $filePath;
            $this->image = $this->_loadImageAndDimensions($this->_imageSrc);
        }
    }

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath) {
        $this->_imageSrc = $filePath;
    }

    /**
     * @Throws Exception
     */
    public function load() {
        $this->image = $this->_loadImageAndDimensions($this->_imageSrc);
    }

    /**
     * @return int
     */
    public function getWidth() {
        return $this->_width;
    }

    /**
     * @return int
     */
    public function getHeight() {
        return $this->_height;
    }

    /**
     * @return void
     */
    public function resize($newWidth, $newHeight, $strategy = "auto") {
        $dimensions = $this->_getOptimalDimensions($newWidth, $newHeight, $strategy);

        $optimalWidth  = $dimensions->optimalWidth;
        $optimalHeight = $dimensions->optimalHeight;

        $this->_image->resizeImage($optimalWidth,$optimalHeight, \Imagick::FILTER_LANCZOS, 0.9, false);
    }

    /**
     * @return int número de bytes escritos o FALSE
     */
    public function saveImage($savePath) {
        file_put_contents($savePath, $this->_image->getimageblob());
    }

    /**
     * @return string
     */
    public function getRaw() {
        return $this->_image->getimageblob();
    }

    /**
     * @return string
     */
    public function getFormat() {
        return $this->_image->getFormat();
    }

    private function _loadImageAndDimensions($file) {

        $this->_image = new \Imagick($file);
        $geometry = $this->_image->getImageGeometry();

        $this->_width  = $geometry['width'];
        $this->_height = $geometry['height'];
    }

    private function _getOptimalDimensions($newWidth, $newHeight, $option) {
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

    private function _getSizeByFixedHeight($newHeight) {
        $ratio = $this->_width / $this->_height;
        $newWidth = $newHeight * $ratio;
        return $newWidth;
    }

    private function _getSizeByFixedWidth($newWidth) {
        $ratio = $this->_height / $this->_width;
        $newHeight = $newWidth * $ratio;
        return $newHeight;
    }

    private function _getSizeByAuto($newWidth, $newHeight) {
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