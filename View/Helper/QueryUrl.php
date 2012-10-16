<?php
/**
 * @author Alayn Gortazar <alayn+karma@irontec.com>
 *
 */
class Iron_View_Helper_QueryUrl extends Zend_View_Helper_Url
{
    /**
     * Returns current url with correct lang parameter for language links
     * @param array $values associative array with key => value pairs
     * @param bool $reset true if previously existing values must be deleted
     * @param string $url url generated with $this->url()
     * @return string
     */
    public function queryUrl(array $values, $reset = false, $url = null)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();

        if ($reset) {
            $query = $values;
        } else {
            $query = $request->getQuery();
            if (!empty($values)) {
                foreach ($values as $key => $value) {
                    $query[$key] = $value;
                }
            }
        }

        $queryStrings = $this->_getQueryStrings($query);
        if (is_null($url)) {
            $url = $this->url();
        }
        return $url . '?' . implode('&amp;', $queryStrings);
    }



    /**
     * @param array $query
     * @return array("key=>value")
     */
    protected function _getQueryStrings(array $query)
    {
        $queryStrings = array();
        if (!empty($query)) {
            foreach ($query as $key => $value) {
                $queryStrings[$key] = $key . '=' . $value;
            }
        }
        return $queryStrings;
    }
}