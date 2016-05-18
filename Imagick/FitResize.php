<?php
/**
 *
 * @param Imagick $imagick
 * @param Array $config
 * @author ddniel16 <daniel@irontec.com>
 */

class Iron_Imagick_FitResize
{

    /**
     * @param \Imagick $imagick
     * @param int $size
     * @throws \Exception
     */
    public function init(\Imagick $imagick, $config)
    {

        $geometry = $imagick->getImageGeometry();

        $gWidth = $geometry['width'];
        $gHeight = $geometry['height'];

        $aspect = $gWidth / $gHeight;

        $width = $config['width'];
        $height = $config['height'];
        $air = $config['air'];
        $asp = $config['aspect'];

        if ($aspect < $asp) {

            $newHeight = $height - ($air * 2);
            if ($newHeight < $gWidth) {
                $imagick->resizeImage(
                    $newHeight,
                    $newHeight,
                    \Imagick::FILTER_LANCZOS,
                    1
                );
            } else {

            }

        } else {

            $newWidth = $width - ($air * 2);
            if ($newWidth > $gWidth) {

            } else {

                $imagick->resizeImage(
                    $newWidth,
                    $gHeight - ($air * 2) * 2,
                    \Imagick::FILTER_LANCZOS,
                    1
                );

            }
        }

        $geometry = $imagick->getImageGeometry();
        $newGeoWidth = $geometry['width'];
        $newGeoHeight = $geometry['height'];

        $extWidth = ($newGeoWidth - $width) / 2;
        $extHeight = ($newGeoHeight - $height) / 2;

        $imagick->setImageFormat('png');
        $imagick->setImageBackgroundColor(new \ImagickPixel('transparent'));
        $imagick->extentimage($width, $height, $extWidth, $extHeight);

    }

}