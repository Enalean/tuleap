<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
 
/**
 * Factory for BaseLanguage objects
 */
class BaseLanguageFactory {
    
    /**
     * @var array of BaseLanguage
     */
    protected $languages;
    
    /**
    * @var string The supported languages eg: 'en_US,fr_FR'
     */
    protected $supported_languages;
    
    /**
    * @var string The default language eg: 'en_US'
    */
    protected $default_language;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->languages           = array();
        $this->supported_languages = Config::get('sys_supported_languages');
        $this->default_language    = Config::get('sys_lang');
    }
    
    /**
     * Cache the BaseLanguage instance
     *
     * @param BaseLanguage $language The instance
     *
     * @return void
     */
    public function cacheBaseLanguage(BaseLanguage $language) {
        $this->languages[$language->defaultLanguage] = $language;
    }
    
    /**
     * Get an instance of a BaseLanguage according to a given locale
     *
     * @param string $locale The locale eg: 'en_US'
     *
     * @return BaseLanguage
     */
    public function getBaseLanguage($locale) {
        if (strpos($this->supported_languages, $locale) === false) {
            $locale = $this->default_language;
        }
        if (!isset($this->languages[$locale])) {
            $this->cacheBaseLanguage($this->createBaseLanguage($locale));
        }
        return $this->languages[$locale];
    }
    
    /**
     * Instantiate and load a new BaseLanguage
     *
     * @param string $supported_languages The supported languages eg: 'en_US,fr_FR'
     * @param string $locale              The current locale
     *
     * @return BaseLanguage
     */
    protected function createBaseLanguage($locale) {
        $currentlocale = setlocale(LC_ALL, '0');
        $language = new BaseLanguage($this->supported_languages, $locale);
        $language->loadLanguage($locale);
        setlocale(LC_ALL, $currentlocale);
        return $language;
    }
}

?>
