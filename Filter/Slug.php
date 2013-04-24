<?php
class Iron_Filter_Slug implements Zend_Filter_Interface
{
    public function filter($value)
    {
        $asciiFilter = new Iron_Filter_Ascii();
        $asciiValue = $asciiFilter->filter(trim($value));

        $slug = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $asciiValue);
        $slug = strtolower(trim($slug, '-'));
        $slug = preg_replace("/[\/_|+ -]+/", '-', $slug);
        return $slug;

    }
}