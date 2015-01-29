<?php
/**
 * Crea un borde al rededor de la imagen.
 *
 * El parametro "color" acepta hexadeciman "#C3C3C3" y rgb "rgba(0,0,0,.7)" con lo que crea
 * bordes con transparencia.
 *
 * @param Imagick $imagick
 * @param Array $config
 * @author ddniel16 <daniel@irontec.com>
 */

class Iron_Imagick_Border
{

    public function init(Imagick $imagick, $config = array())
    {

        if (
            !isset($config['color'])
        ||
            !isset($config['width'])
        ||
            !isset($config['height'])
        ) {
            throw new Exception(
                'Es necesario especificar los tamaños con los parametros "color", "width" y "height"'
            );
        }

        if (!is_numeric($config['width']) || !is_numeric($config['width'])) {
            throw new Exception(
                'Los parametros "width" y "width" tienen que ser numéricos'
            );
        }

        $imagick->borderImage(
            $config['color'],
            $config['width'],
            $config['height']
        );

    }

}