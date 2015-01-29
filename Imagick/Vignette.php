<?php
/**
 * Suaviza los extremos de la imagen al estilo viñeta.
 *
 * @param Imagick $imagick
 * @param Array $config
 * @author ddniel16 <daniel@irontec.com>
 */

class Iron_Imagick_Vignette
{

    public function init(Imagick $imagick, $config = array())
    {

        if (
            !isset($config['blackPoint']) || !isset($config['whitePoint'])
        ||
            !isset($config['x']) || !isset($config['y'])
        ) {
            throw new Exception(
                'Es necesario especificar los tamaños con los parametros "x", "y", "blackPoint" y "whitePoint"'
            );
        }

        if (
            !is_numeric($config['blackPoint']) || !is_numeric($config['whitePoint'])
        ||
            !is_numeric($config['x']) || !is_numeric($config['y'])
        ) {
            throw new Exception(
                'Los parametros "x", "y", "blackPoint" y "whitePoint" tienen que ser numéricos'
            );
        }

        $imagick->vignetteImage(
            $config['blackPoint'],
            $config['whitePoint'],
            $config['x'],
            $config['y']
        );

    }

}