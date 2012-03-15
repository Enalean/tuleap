<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/**
 * I18N_Translator
 *
 * The I18N_Translator class handles language translations via an Array that is
 * stored in an INI file. There is 1 ini file per language and upon construction
 * of the class, the appropriate language file is loaded. The class offers
 * functionality to manage the files as well, such as creating new language
 * files and adding/deleting of existing translations. Upon destruction, the
 * file is saved.
 *
 * @author      Andrew S. Nagy <andrew.nagy@villanova.edu>
 * @package     I18N_Translator
 * @category    I18N
 */
class I18N_Translator
{
    /**
     * Language translation files path
     *
     * @var     string
     * @access  public
     */
    var $path;

    /**
     * The specified language.
     *
     * @var     string
     * @access  public
     */
    var $langCode;

    /**
     * An array of the translated text
     *
     * @var     array
     * @access  public
     */
    var $words = array();

    /**
     * Debugging flag
     *
     * @var     boolean
     * @access  public
     */
    var $debug = false;

    /**
     * Constructor
     *
     * @param   string $langCode    The ISO 639-1 Language Code
     * @access  public
     */
    function __construct($path, $langCode, $debug = false)
    {
        $this->path = $path;
        $this->langCode = preg_replace('/[^\w\-]/', '', $langCode);

        if ($debug) {
            $this->debug = true;
        }

        // Load file in specified path
        if ($dh = opendir($path)) {
            $file = $path . '/' . $this->langCode . '.ini';
            if ($this->langCode != '' && is_file($file)) {
                $this->words = $this->parseLanguageFile($file);
            } else {
                return new PEAR_Error("Unknown language file");
            }
        } else {
            return new PEAR_Error("Cannot open $path for reading");
        }
    }

    /**
     * Parse a language file.
     *
     * @param   string $file        Filename to load
     * @access  private
     * @return  array
     */
    function parseLanguageFile($file)
    {
        /* Old method -- use parse_ini_file; problematic due to reserved words and
         * increased strictness in PHP 5.3.
        $words = parse_ini_file($file);
        return $words;
         */
        
        // Manually parse the language file:
        $words = array();
        $contents = file($file);
        if (is_array($contents)) {
            foreach($contents as $current) {
                // Split the string on the equals sign, keeping a max of two chunks:
                $parts = explode('=', $current, 2);
                $key = trim($parts[0]);
                if (!empty($key) && substr($key, 0, 1) != ';') {
                    // Trim outermost double quotes off the value if present:
                    if (isset($parts[1])) {
                        $value = preg_replace('/^\"?(.*?)\"?$/', '$1', trim($parts[1]));

                        // Store the key/value pair (allow empty values -- sometimes
                        // we want to replace a language token with a blank string):
                        $words[$key] = $value;
                    }
                }
            }
        }
        
        return $words;
    }

    /**
     * Translate the phrase
     *
     * @param   string $phrase      The phrase to translate
     * @access  public
     * @note    Can be called statically if 2nd parameter is defined and load
     *          method is called before
     */
    function translate($phrase)
    {
        if (isset($this->words[$phrase])) {
            return $this->words[$phrase];
        } else {
            if ($this->debug) {
                return "translate_index_not_found($phrase)";
            } else {
                return $phrase;
            }
        }
    }
}
?>