<?php
/**
 * Hace un crop y resize del Imagick en base a los parametros "width" y "height"
 *
 *
 * @param Imagick $imagick
 * @param Array $config
 * @author ddniel16 <daniel@irontec.com>
 */

class Iron_Imagick_CropResize
{

    public function init(Imagick $imagick, $config = array())
    {

        if (!isset($config['width']) || !isset($config['height'])) {
            throw new Exception(
                'Es necesario especificar los tamaños con los parametros "width" y "height"'
            );
        }

        if (
            !is_numeric($config['width'])
        ||
            !is_numeric($config['height'])
        ) {
            throw new Exception(
                'Los parametros "width" y "height" tienen que ser numéricos'
            );
        }

        $imagick->cropthumbnailimage(
            $config['width'],
            $config['height']
        );

        $imagick->resizeImage(
            $config['width'],
            $config['height'],
            imagick::FILTER_LANCZOS,
            1
        );

    }

}