<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2000 (c) The SourceForge Crew
 * http://sourceforge.net
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Symfony\Component\VarExporter\VarExporter;
use Tuleap\File\FileWriter;

class BaseLanguage
{
    public const DEFAULT_LANG_SHORT = 'en';
    public const DEFAULT_LANG       = self::DEFAULT_LANG_SHORT . '_US';
    public const CONFIG_KEY         = 'sys_lang';

    //array to hold the string values
    public $text_array;
    public $lang;
    public $name;
    public $id;
    public $code;
    public $file_array = [];

    /**
     * Supported languages
     * @var string[]
     */
    public $allLanguages;

    /**
     * Default languages
     * @var string
     */
    public $defaultLanguage;

    /**
     * Constructor
     * @param $supported_languages string 'en_US,fr_FR'
     * @param $default_language string 'en_US'
     */
    public function __construct($supported_languages, $default_language)
    {
        $this->allLanguages  = [];
        $supported_languages = explode(',', $supported_languages);
        foreach ($supported_languages as $v) {
            if (trim($v) !== '') {
                $this->allLanguages[] = trim($v);
            }
        }
        if (count($this->allLanguages)) {
            if (in_array($default_language, $this->allLanguages)) {
                $this->defaultLanguage = $default_language;
            } else {
                throw new Exception('The default language must be part of supported languages');
            }
        } else {
            throw new Exception('You must provide supported languages (see local.inc)');
        }
    }

    /**
     * "compile" string definitions for one language.
     */
    public function compileLanguage($lang)
    {
        $text_array = [];
        $this->loadAllLanguageFiles($lang, $text_array);

        $this->dumpLanguageFile($lang, $text_array);

        return $text_array;
    }

    /**
     * Load all tab files to build the internal string array.
     *
     * Here the order is important: First load the default definition and than
     * load the custom (site wide) defs in order to override the default one,
     * and so on.
     */
    public function loadAllLanguageFiles($lang, &$text_array)
    {
        // The order is important!

        // 1) load all the en_US for official code (core + plugins) in order
        // to define all the default values (all other language load while
        // override existing values. If no overriding: the en_US value appears.
        if ($lang != self::DEFAULT_LANG) {
            $this->loadCoreSiteContent(self::DEFAULT_LANG, $text_array);
            $this->loadPluginsSiteContent(self::DEFAULT_LANG, $text_array);
        }

        // 2) load the language for official code
        $this->loadCoreSiteContent($lang, $text_array);
        $this->loadCustomSiteContent($lang, $text_array);
        $this->loadPluginsSiteContent($lang, $text_array);
        $this->loadPluginsCustomSiteContent($lang, $text_array);
    }

    /**
     * Load tab files in /usr/share/codendi/site-content for given language
     */
    public function loadCoreSiteContent($lang, &$text_array)
    {
        $this->loadAllTabFiles(ForgeConfig::get('sys_incdir') . '/' . $lang, $text_array);
    }

    /**
     * Load tab files in /etc/codendi/site-content for given language
     */
    public function loadCustomSiteContent($lang, &$text_array)
    {
        $this->loadAllTabFiles(ForgeConfig::get('sys_custom_incdir') . '/' . $lang, $text_array);
    }

    /**
     * Load all tab files in /usr/share/codendi/plugins/.../site-content for
     * given language
     */
    public function loadPluginsSiteContent($lang, &$text_array)
    {
        $directories = array_merge(
            array_map('trim', explode(',', ForgeConfig::get('sys_extra_plugin_path'))),
            [ForgeConfig::get('sys_pluginsroot')]
        );
        foreach ($directories as $dir) {
            $this->_loadPluginsSiteContent($dir, $lang, $text_array);
        }
    }

    /**
     * Load all tab files in /etc/codendi/plugins/.../site-content for
     * given language
     */
    public function loadPluginsCustomSiteContent($lang, &$text_array)
    {
        $this->_loadPluginsSiteContent(ForgeConfig::get('sys_custompluginsroot'), $lang, $text_array);
    }

    /**
     * This method walk through all the plugins and load all .tab files for
     * each plugin found.
     */
    public function _loadPluginsSiteContent($basedir, $lang, &$text_array)
    {
        if (is_dir($basedir)) {
            $fd = opendir($basedir);
            while (false !== ($file = readdir($fd))) {
                if (
                    is_dir($basedir . '/' . $file)
                    && $file != '.'
                    && $file != '..'
                    && $file != '.svn'
                ) {
                    $location = $basedir . '/' . $file . '/site-content/' . $lang;
                    if (is_dir($location)) {
                        $this->loadAllTabFiles($location, $text_array);
                    }
                }
            }
            closedir($fd);
        }
    }

    /**
     * Look for all ".tab" files in the given path recursively.
     */
    public function loadAllTabFiles($basedir, &$text_array)
    {
        if (is_dir($basedir)) {
            $fd = opendir($basedir);
            while (false !== ($file = readdir($fd))) {
                if (preg_match('/\.tab$/', $file)) {
                    $this->parseLanguageFile($basedir . '/' . $file, $text_array);
                } elseif (
                    is_dir($basedir . '/' . $file)
                       && $file != '.'
                       && $file != '..'
                       && $file != '.svn'
                ) {
                    $this->loadAllTabFiles($basedir . '/' . $file, $text_array);
                }
            }
            closedir($fd);
        }
    }

    /**
     * Create a PHP file that contains all the strings loaded in this object.
     */
    public function dumpLanguageFile($lang, $text_array)
    {
        // Create language cache directory if needed
        if (! is_dir($this->getCacheDirectory())) {
            // This directory must be world reachable, but writable only by the web-server
            mkdir($this->getCacheDirectory(), 0755);
        }

        $path    = $this->getCacheDirectory() . DIRECTORY_SEPARATOR . $lang . '.php';
        $content = '<?php' . PHP_EOL . 'return ' . VarExporter::export($text_array) . ';';
        try {
            FileWriter::writeFile($path, $content);
        } catch (RuntimeException $e) {
            //Do nothing
        }
    }

    /**
     * Parse given .tab file and store the result into $text_array
     */
    public function parseLanguageFile($fname, &$text_array)
    {
        $ary = @file($fname, 1);
        for ($i = 0; $i < sizeof($ary); $i++) {
            if (
                substr($ary[$i], 0, 1) == '#' || //ignore comments...
                strlen(trim($ary[$i])) == 0
            ) {    //...or empty lines
                continue;
            }

            $line = explode("\t", $ary[$i], 3);
            if (count($line) === 3) {
                $text_array[$line[0]][$line[1]] = chop(str_replace('\n', "\n", ($line[2])));
            } else {
                $error_str = '* Error in ' . $fname . ' line ' . $i . ' string "' . trim($ary[$i]) . '" (length: ' . strlen(trim($ary[$i])) . ') : ';
                if (! isset($line[0])) {
                    $error_str .= "no index 0: empty line ? ";
                } elseif (! isset($line[1])) {
                    $error_str .= "no index 1: did you use tabs to separate elements ? ";
                } elseif (! isset($line[2])) {
                    $error_str .= "no index 2: keys present but string is missing ";
                }
                throw new RuntimeException($error_str);
            }
        }
    }

    public function loadLanguage($lang)
    {
        if ($this->lang !== $lang) {
            if (strpos($lang, DIRECTORY_SEPARATOR) !== false) {
                throw new RuntimeException('$lang is not expected to contain a directory separator, got ' . $lang);
            }
            /**
             * @psalm-taint-escape include
             * @psalm-taint-escape file
             */
            $new_lang   = $lang;
            $this->lang = $new_lang;
            $this->loadFromSerialized($new_lang) || $this->loadFromTabs($new_lang);
        }
    }

    /**
     * Load strings from previously serialized form
     *
     * As we cannot lock file for deletion, check as much as possible that we are unserializing stuff from a valid
     * file.
     *
     * @param string $lang
     * @return bool
     */
    private function loadFromSerialized($lang)
    {
        $filepath = $this->getCacheDirectory() . DIRECTORY_SEPARATOR . $lang . '.php';
        if (is_file($filepath)) {
            $this->text_array = require $filepath;
            return true;
        }
        return false;
    }

    private function loadFromTabs($lang)
    {
        $this->text_array = $this->compileLanguage($lang);
    }

    /**
     * @psalm-taint-specialize
     */
    public function getText($pagename, $category, $args = "")
    {
        // If the language files were modified by an update, the compiled version might not have been generated,
        // and the message not present.
        if (! $this->hasText($pagename, $category)) {
            // Force compile (only once)
            $this->text_array = $this->compileLanguage($this->lang);
        }
        /*
            args is an array which will replace the $1, $2, etc
            in the text_array string before it is returned
        */
        if (($args || $args == 0) && $args !== '') {
            //$tstring = sprintf($this->text_array[$pagename][$category],$args);
            $nb_args = 1;
            if (is_array($args)) {
                $nb_args = count($args);
            }
            for ($i = 1; $i <= $nb_args + 1; $i++) {
                $patterns[] = '/\$' . $i . '/';
            }
            $tstring = preg_replace($patterns, $args, $this->text_array[$pagename][$category]);
        } else {
                    // Remove $1, $2 etc. even if the given arguments are empty
                    $pattern = '/\$\d+/';
                    $tstring = preg_replace($pattern, '', $this->text_array[$pagename][$category]);
                    //$tstring = $this->text_array[$pagename][$category];
        }
        if (! $tstring) {
            $tstring = "*** Unkown msg $pagename - $category ***";
        }
        return "$tstring";
    }

    /**
     * @return bool
     */
    public function hasText($pagename, $category)
    {
        $this->ensureLanguageFilesAreLoaded();
        return isset($this->text_array[$pagename][$category]);
    }

    private function ensureLanguageFilesAreLoaded()
    {
        if (! isset($this->lang)) {
            $this->loadLanguage(UserManager::instance()->getCurrentUser()->getLocale());
        }
    }

    // This is a legacy piece of code that used to be utils_get_content
    // and is used either to include long piece of text that are inconvenient
    // to format on one line as the .tab file does or because there is some
    // PHP code that can be cutomized
    public function getContent($file, $lang_code = null, $plugin_name = null, $ext = '.txt')
    {
        // Language for current user unless it is specified in the param list
        if (! isset($lang_code) && preg_match('/^[A-Za-z]{2,4}_[A-Za-z]{2}$/', $this->lang) === 1) {
            /**
             * @psalm-taint-escape include
             */
            $lang_code = $this->lang;
        }

        if (is_null($plugin_name)) {
            // Test first the custom directory
            $custom_fn = ForgeConfig::get('sys_custom_incdir') . "/" . $lang_code . "/" . $file . $ext;
        } else {
            $custom_fn = ForgeConfig::get('sys_custompluginsroot') . '/' . $plugin_name . '/site-content/' . $lang_code . '/' . $file . $ext;
        }
        if (file_exists($custom_fn)) {
            // The custom file exists.
            return $custom_fn;
        } else {
            // Use the default file
            // Check first if exist
            if (is_null($plugin_name)) {
                $fn = ForgeConfig::get('sys_incdir') . "/" . $lang_code . "/" . $file . $ext;
            } else {
                $fn = ForgeConfig::get('sys_pluginsroot') . '/' . $plugin_name . '/site-content/' . $lang_code . '/' . $file . $ext;
            }
            if (file_exists($fn)) {
                // The custom file exists.
                return $fn;
            } else {
                if ($lang_code === self::DEFAULT_LANG) {
                    // return empty content to avoid include error
                    return __DIR__ . '/../../../site-content/en_US/others/empty.txt';
                } else {
                    // else try to find the file in the en_US directory
                    return $this->getContent($file, "en_US", $plugin_name, $ext);
                }
            }
        }
    }

    //result set handle for supported langauges
    public $language_res;

    /**
     * @return array pairs of language_code => Language
     */
    public function getLanguages()
    {
        $ret = [];
        foreach ($this->allLanguages as $lang) {
            $text_array = $this->compileLanguage($lang);
            $ret[$lang] = $text_array['system']['locale_label'];
        }
        return $ret;
    }

    public function getEncoding()
    {
        return $this->text_array['conf']['content_encoding'];
    }

    public function getFont()
    {
        return $this->text_array['conf']['default_font'];
    }

    /** Returns list of loaded language files (for debugging) */
    public function getLoadedLangageFiles()
    {
        return array_keys($this->file_array);
    }

    /**
     * Parse the Accept-Language header according to RFC 2616
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
     * @see RFC 1766
     *
     * Based on Jesse Skinner work
     * @see http://www.thefutureoftheweb.com/blog/use-accept-language-header#comment1
     *
     * @param $accept_language string "en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3"
     * @return array ('en-us' => 1, 'en' => 0.8, 'fr' => 0.5, 'fr-fr' => 0.3) ordered by score
     */
    public function parseAcceptLanguage($accept_language)
    {
        $langs      = [];
        $lang_parse = [];

        // break up string into pieces (languages and q factors)
        preg_match_all(
            '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
            $accept_language,
            $lang_parse
        );

        if (count($lang_parse[1])) {
            // create a list like "en" => 0.8
            $langs = array_combine($lang_parse[1], $lang_parse[4]);

            // set default to 1 for any without q factor
            foreach ($langs as $lang => $val) {
                if ($val === '') {
                    $langs[$lang] = 1;
                }
            }

            // sort list based on value
            arsort($langs, SORT_NUMERIC);
        }

        return $langs;
    }

    /**
     * Get the relevant language code "en_US" provided by Codendi
     * depending on the Accept-Language header
     *
     * According to RFC 2616, the separator between language abbreviation and
     * country code is a dash (-) for Accept-Language header.
     * In Codendi, we use underscore (_).
     *
     * @param $accept_language string "en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3"
     * @return string en_US
     */
    public function getLanguageFromAcceptLanguage($accept_language)
    {
        $relevant_language = $this->defaultLanguage;

        //extract language abbr and country codes from Codendi languages
        $provided_languages = [];
        foreach ($this->allLanguages as $lang) {
            list($l,$c)                                         = explode('_', $lang);
            $provided_languages[strtolower($l)][strtolower($c)] = $lang;
        }

        //Now do the same thing for accept_language,
        $parse_accept_lang = $this->parseAcceptLanguage($accept_language);
        foreach ($parse_accept_lang as $lang => $score) {
            $lang = explode('-', $lang);
            $l    = strtolower($lang[0]);
            if (isset($provided_languages[$l])) {
                //We've just found a matching languages
                //check now for the country code
                if (isset($lang[1]) && isset($provided_languages[$l][strtolower($lang[1])])) {
                    $relevant_language = $provided_languages[$l][strtolower($lang[1])];
                } else {
                    //If there is no country code, then take the first one
                    //provided by Codendi
                    $relevant_language = array_shift($provided_languages[strtolower($lang[0])]);
                }

                //We have our relevant language. We can go out
                break;
            }
        }

        return $relevant_language;
    }

    /**
     * @param $language string 'en_US'
     * @return bool true if the $language is supported
     */
    public function isLanguageSupported($language)
    {
        return in_array($language, $this->allLanguages);
    }

    public function invalidateCache()
    {
        foreach (glob($this->getCacheDirectory() . DIRECTORY_SEPARATOR . '*.php') as $file) {
            unlink($file);
        }
        foreach (glob($this->getCacheDirectory() . DIRECTORY_SEPARATOR . '*.bin') as $file) {
            unlink($file);
        }
    }

    public function getCacheDirectory()
    {
        return ForgeConfig::getCacheDir() . DIRECTORY_SEPARATOR . 'lang';
    }

    public function getOverridableText($pagename, $category, $args = "")
    {
        return $this->getText($pagename, $category, $args);
    }
}
