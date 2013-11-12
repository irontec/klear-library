<?php
/**
 * Filtro que devuelve los nombres propios debidamente formateados.
 * En caso de encontrar un artículo o una preposición que composición de nombres los deja en minúsculas
 * 
 * Ver Apartado sobre Mayúsculas (sección 4.3) de la RAE
 * http://lema.rae.es/dpd/srv/search?id=BapzSnotjD6n0vZiTp#43
 */
class Iron_Filter_ProperName implements Zend_Filter_Interface
{
    protected $_blackList = array(
        'de',
        'la',
        'del',
        'los',
        'el'
    );
    
    public function filter($data) 
    {
        $normalizedData = preg_replace('/\s+/', ' ', $data);
        $tokens = explode(' ', $normalizedData);
        foreach ($tokens as $token) {
            if (!in_array(strtolower($token), $this->_blackList)) {
                $normalizedTokens[] = ucfirst(mb_strtolower($token, 'utf8'));
            } else {
                $normalizedTokens[] = mb_strtolower($token, 'utf8');
            }
        }
        return implode(' ', $normalizedTokens);
    }
}
