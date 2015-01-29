<?php
/**
 * Crea un borde con estilo de enmarcado al rededor de la imagen.
 *
 * El parametro "color" acepta hexadeciman "#C3C3C3" y rgb "rgba(0,0,0,.7)" con lo que crea
 * bordes con transparencia.
 *
 * @param Imagick $imagick
 * @param Array $config
 * @author ddniel16 <daniel@irontec.com>
 */

class Iron_Imagick_Framing
{

    public function init(Imagick $imagick, $config = array())
    {

        if (
            !isset($config['color'])
        ||
            !isset($config['width'])
        ||
            !isset($config['height'])
        ||
            !isset($config['innerBevel'])
        ||
            !isset($config['outerBevel'])
        ) {
            throw new Exception(
                'Es necesario especificar los tamaños con los parametros "color", "width", "height", "innerBevel" y "outerBevel"'
            );
        }

        if (!is_numeric($config['width']) || !is_numeric($config['width'])) {
            throw new Exception(
                'Los parametros "width", "height", "innerBevel" y "outerBevel" tienen que ser numéricos'
            );
        }

         $innerBevel = $config['innerBevel'];
         $outerBevel = $config['outerBevel'];
         $width = $config['width'] + $innerBevel + $outerBevel;
         $height = $config['height'] + $innerBevel + $outerBevel;

         $imagick->frameimage(
             $config['color'],
             $width,
             $height,
             $innerBevel,
             $outerBevel
         );

    }

}