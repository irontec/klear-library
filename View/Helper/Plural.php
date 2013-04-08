<?php
/**
 * @author Alayn Gortazar <alayn+karma@irontec.com>
 *
 */
class Iron_View_Helper_Plural extends Zend_View_Helper_Translate
{
    /**
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see Zend_Locale
     * @param  string             $singular Singular translation string
     * @param  string             $plural   Plural translation string
     * @param  integer            $number   Number for detecting the correct plural
     * @param  string|Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                      locale identifier, @see Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        if (Zend_Registry::isRegistered(Iron_Plugin_Translator::DEFAULT_REGISTRY_KEY)) {
            $translator = Zend_Registry::get(Iron_Plugin_Translator::DEFAULT_REGISTRY_KEY);
        } else {
            $translator = $this->getTranslator();
        }
        return $translator->plural($singular, $plural, $number, $locale);
    }
}