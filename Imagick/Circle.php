<?php
/**
 * En base a la imagen cargada con el imagick,
 * genera un png circular con transparencias y un tamaño proporcional.
 *
 * @param Imagick $imagick
 * @param Array $config
 * @author ddniel16 <daniel@irontec.com>
 */

class Iron_Imagick_Circle
{

    public function init(Imagick $imagick, $config = array())
    {

        if (!isset($config['size'])) {
            throw new Exception(
                'Es necesario especificar un tamaño con el parametro "size"'
            );
        }

        if (
            empty($config['size'])
        ||
            is_null($config['size'])
        ||
            !is_numeric($config['size'])
        ) {
            throw new Exception(
                'El parametro "size" tiene que ser numérico'
            );
        }

        $size = $config['size'];

        $circle = new Imagick();
        $circle->newImage($size, $size, 'none');
        $circle->setimageformat('png');
        $circle->setimagematte(true);

        $draw = new ImagickDraw();
        $draw->setfillcolor('#ffffff');
        $draw->circle($size/2, $size/2, $size/2, $size);
        $circle->drawimage($draw);

        $imagick->setImageFormat('png');
        $imagick->setimagematte(true);
        $imagick->cropthumbnailimage($size, $size);
        $imagick->compositeimage($circle, Imagick::COMPOSITE_DSTIN, 0, 0);

    }

}