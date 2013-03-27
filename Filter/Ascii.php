<?php
class Iron_Filter_Ascii implements Zend_Filter_Interface
{
    public function filter($value)
    {
        // TODO: Asegurarnos de que esto no afecta a otros asuntos de klear...
        setlocale(LC_ALL, 'en_US.UTF8');
        return iconv('UTF-8', 'ASCII//TRANSLIT', $value);
    }
}