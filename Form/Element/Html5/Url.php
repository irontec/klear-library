<?php
/** Zend_Form_Element_Xhtml */
require_once 'Iron/Form/Element/Html5/Abstract.php';

class Iron_Form_Element_Html5_Url extends Iron_Form_Element_Html5_Abstract
{
    /**
     * Default form view helper to use for rendering
     * @var string
     */
    public $helper = 'formUrl';
}
