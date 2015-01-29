<?php
/**
 * Escala la imagen de una forma uniforme sin romper las porciones
 * hasta una medida que se adapte a las propocionadas con los parametros "width" y "height"
 *
 * @param Imagick $imagick
 * @param Array $config
 * @author ddniel16 <daniel@irontec.com>
 */

class Iron_Imagick_Scale
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

        $imagick->scaleImage(
            $config['width'],
            $config['height'],
            true
        );

    }

}