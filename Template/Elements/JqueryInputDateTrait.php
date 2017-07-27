<?php
namespace exface\AbstractAjaxTemplate\Template\Elements;

/**
 *
 * @author SFL
 *        
 */
trait JqueryInputDateTrait {

    private $dateFormatScreen;

    private $dateFormatInternal;

    /**
     * Returns the format which is used to show dates on the screen.
     *
     * The format is specified in the translation files "DATE.FORMAT.SCREEN".
     *
     * @return unknown
     */
    protected function buildJsDateFormatScreen()
    {
        if (is_null($this->dateFormatScreen)) {
            $this->dateFormatScreen = $this->translate("DATE.FORMAT.SCREEN");
        }
        return $this->dateFormatScreen;
    }

    /**
     * Returns the format which is used for dates internally, eg to send them to the
     * server.
     *
     * The format is specified in the translation files "DATE.FORMAT.INTERNAL".
     *
     * @return unknown
     */
    protected function buildJsDateFormatInternal()
    {
        if (is_null($this->dateFormatInternal)) {
            $this->dateFormatInternal = $this->translate("DATE.FORMAT.INTERNAL");
        }
        return $this->dateFormatInternal;
    }

    /**
     * Generates the DateJs filename based on the locale provided by the translator.
     *
     * @return string
     */
    protected function buildDateJsLocaleFilename()
    {
        $dateJsBasepath = MODX_BASE_PATH . 'exface' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'npm-asset' . DIRECTORY_SEPARATOR . 'datejs' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR;
        
        $locale = $this->getTemplate()->getApp()->getTranslator()->getLocale();
        $filename = 'date-' . str_replace("_", "-", $locale) . '.min.js';
        if (file_exists($dateJsBasepath . $filename)) {
            return $filename;
        }
        
        $fallbackLocales = $this->getTemplate()->getApp()->getTranslator()->getFallbackLocales();
        foreach ($fallbackLocales as $fallbackLocale) {
            $filename = 'date-' . str_replace("_", "-", $fallbackLocale) . '.min.js';
            if (file_exists($dateJsBasepath . $filename)) {
                return $filename;
            }
        }
        
        return 'date.min.js';
    }
}
