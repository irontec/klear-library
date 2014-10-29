<?php

class Iron_Utils_PngFix
{
    
    public static function process(Imagick $image)
    {
        if ($image->getImageFormat() == 'PNG') {
            $curColorSpace = $image->getColorspace();
            // Si La imagen no tiene colorspace definido, intentamos averigüarlo
            if ($curColorSpace == imagick::COLORSPACE_UNDEFINED) {
        
                $imageProps = $image->identifyImage();
                $candidateColorspace = imagick::COLORSPACE_RGB;
        
                if ($imageProps['colorSpace'] == 'sRBG') {
                    $candidateColorspace = imagick::COLORSPACE_SRGB;
                }
        
                // BLACK MAGIC AHEAD! (just guessed!)
                // Es posible que falle (o no)... mucho sentido no tiene. Simplemente funciona.
                $transparentImageTypes = array(
                        imagick::IMGTYPE_GRAYSCALEMATTE,
                        imagick::IMGTYPE_PALETTEMATTE,
                        imagick::IMGTYPE_PALETTE,
                        imagick::IMGTYPE_TRUECOLORMATTE,
                        imagick::IMGTYPE_COLORSEPARATIONMATTE
                );
        
                if (in_array($image->getImageType(), $transparentImageTypes)) {
                    $candidateColorspace = imagick::COLORSPACE_TRANSPARENT;
                }
                $image->setImageColorSpace($candidateColorspace);
            }
        }
        
        return $image;
    }
}