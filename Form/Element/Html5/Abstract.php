<?php

/** Zend_Form_Element_Xhtml */
require_once 'Zend/Form/Element/Xhtml.php';

/**
 * Text form html5 element
 * Browser compatibility: http://www.w3schools.com/html/html5_form_input_types.asp
 *
 * @category   Zend
 * @package    Zend_Form
 * @subpackage Element
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Text.php 23775 2011-03-01 17:25:24Z ralph $
 */
Abstract class Iron_Form_Element_Html5_Abstract extends Zend_Form_Element_Xhtml
{
    /**
     * Default form view helper to use for rendering
     * @var string
     */
    public $helper = 'input';

    /**
     * Initialize object; used by extending classes
     *
     * @return void
     */
    public function init()
    {
        $this->getView()->getPluginLoader('helper')->addPrefixPath("Iron_View_Helper_", "Iron/View/Helper/");
    }
}
