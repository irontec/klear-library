<?php
require_once 'Iron/View/Helper/FormHtml5.php';

/**
 * Base Helper to generate a html5 "email" text element
 *
 * @category   Iron
 * @package    Iron_View
 * @subpackage Helper
 */
class Iron_View_Helper_FormNumber extends Iron_View_Helper_FormHtml5
{
    /**
     * @var string
     */
    protected $_type = 'number';

    /**
     * Generates a 'text' element.
     *
     * @access public
     *
     * @param string|array $name If a string, the element name.  If an
     * array, all other parameters are ignored, and the array elements
     * are used in place of added parameters.
     *
     * @param mixed $value The element value.
     *
     * @param array $attribs Attributes for the element tag.
     *
     * @return string The element XHTML.
     */
    public function formNumber($name, $value = null, $attribs = null)
    {
        return $this->_formHtml5Element($name, $value, $attribs);
    }
}
