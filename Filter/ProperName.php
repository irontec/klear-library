<?php
class Iron_Filter_ProperName implements Zend_Filter_Interface
{
    protected $_blackList = array(
        'de',
        'la',
        'del'
    );
    
    public function filter($data) 
    {
        $normalizedData = preg_replace('/\s+/', ' ', $data);
        $tokens = explode(' ', $normalizedData);
        foreach ($tokens as $token) {
            if (!in_array($token, $this->_blackList)) {
                $normalizedTokens[] = ucfirst(mb_strtolower($token, 'utf8'));
            } else {
                $normalizedTokens[] = mb_strtolower($token, 'utf8');
            }
        }
        return ucfirst(implode(' ', $normalizedTokens));
    }
}
