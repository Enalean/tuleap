<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// Copyright 2004-2005 (c) The CodeX Team, Xerox. All rights reserved
//
// 


/*

    Tim Perdue, September 7, 2000
    Laurent Julliard, Jan 14, 2004
    Manuel Vacelet, July 22, 2008 (nice, every 4 years !)

    Base class for adding multilingual support to CodeX

    Contains variables which can be overridden optionally by other
    language files.

    Base language is english - an english class will extend this one,
    but won't override anything

    As new languages are added, they can override what they wish, and
    as we extend our class, other languages can follow suit
    as they are translated without holding up our progress

    A global language file is loaded first and then each php script
    loads its won scripts (site-local customized versions are also
    loaded if they do exist)

*/

class BaseLanguage {

    //array to hold the string values
    var $text_array ;
    var $lang, $name, $id, $code ;
    var $file_array = array();

    /**
     * Supported languages
     */
    public $allLanguages;
    
    /**
     * Default languages
     */
    public $defaultLanguage;
    
    /**
     * Constructor
     * @param $supported_languages string 'en_US,fr_FR'
     * @param $default_language string 'en_US'
     */
    function __construct($supported_languages, $default_language) {
        $this->allLanguages = array();
        $supported_languages = explode(',', $supported_languages);
        foreach($supported_languages as $v) {
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
     * "compile" all available language definitions.
     */
    function compileAllLanguageFiles() {
        foreach($this->allLanguages as $code) {
            $this->compileLanguage($code);
        }
    }

    /**
     * Load all generated php files to verify if the syntax is correct.
     */
    function testLanguageFiles() {
        if(is_dir($GLOBALS['codex_cache_dir'].'/lang/')) {
            $fd = opendir($GLOBALS['codex_cache_dir'].'/lang/');
            // Browse all generated php files
            while(false !== ($file = readdir($fd))) {
                if(is_file($GLOBALS['codex_cache_dir'].'/lang/'.$file)
                   && preg_match('/\.php$/', $file)) {
                    echo "Test $file\n";
                    include($GLOBALS['codex_cache_dir'].'/lang/'.$file);
                    unset($this->text_array);
                }
            }
            closedir($fd);
        }
    }

    /**
     * "compile" string definitions for one language.
     */
    function compileLanguage($lang) {
        $text_array = array();
        $this->loadAllLanguageFiles($lang, $text_array);

        // Dump the result into the cached files
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
    function loadAllLanguageFiles($lang, &$text_array) {
        // The order is important!

        // 1) load all the en_US for official code (core + plugins) in order
        // to define all the default values (all other language load while
        // override existing values. If no overriding: the en_US value appears.
        if($lang != 'en_US') {
            $this->loadCoreSiteContent('en_US', $text_array);
            // The old code was only loading the core site-content as fallback
            //$this->loadCustomSiteContent('en_US');
            //$this->loadCorePluginsSiteContent('en_US');
            //$this->loadCustomPluginsSiteContent('en_US');
        }

        // 2) load the language for official code
        $this->loadCoreSiteContent($lang, $text_array);
        $this->loadCustomSiteContent($lang, $text_array);
        $this->loadPluginsSiteContent($lang, $text_array);
        $this->loadPluginsCustomSiteContent($lang, $text_array);
    }

    /**
     * Load tab files in /usr/share/codex/site-content for given language
     */
    function loadCoreSiteContent($lang, &$text_array) {
        $this->loadAllTabFiles($GLOBALS['sys_incdir'].'/'.$lang, $text_array);
    }

    /**
     * Load tab files in /etc/codex/site-content for given language
     */
    function loadCustomSiteContent($lang, &$text_array) {
        $this->loadAllTabFiles($GLOBALS['sys_custom_incdir'].'/'.$lang, $text_array);
    }

    /**
     * Load all tab files in /usr/share/codex/plugins/.../site-content for
     * given language
     */
    function loadPluginsSiteContent($lang, &$text_array) {
        $this->_loadPluginsSiteContent($GLOBALS['sys_pluginsroot'], $lang, $text_array);
    }

    /**
     * Load all tab files in /etc/codex/plugins/.../site-content for
     * given language
     */
    function loadPluginsCustomSiteContent($lang, &$text_array) {
        $this->_loadPluginsSiteContent($GLOBALS['sys_custompluginsroot'], $lang, $text_array);
    }
    
    /**
     * This method walk through all the plugins and load all .tab files for
     * each plugin found.
     */
    function _loadPluginsSiteContent($basedir, $lang, &$text_array) {
        if(is_dir($basedir)) {
            $fd = opendir($basedir);
            while(false !== ($file = readdir($fd))) {
                if(is_dir($basedir.'/'.$file)
                   && $file != '.'
                   && $file != '..'
                   && $file != '.svn'
                   && $file != 'CVS') {
                    $location = $basedir.'/'.$file.'/site-content/'.$lang;
                    if(is_dir($location)) {
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
    function loadAllTabFiles($basedir, &$text_array) {
        if(is_dir($basedir)) {
            $fd = opendir($basedir);
            while(false !== ($file = readdir($fd))) {
                if(preg_match('/\.tab$/', $file)) {
                    $this->parseLanguageFile($basedir.'/'.$file, $text_array);
                }
                elseif(is_dir($basedir.'/'.$file)
                       && $file != '.'
                       && $file != '..'
                       && $file != '.svn'
                       && $file != 'CVS') {
                    $this->loadAllTabFiles($basedir.'/'.$file, $text_array);
                }
            }
            closedir($fd);
        }
    }

    /**
     * Create a PHP file that contains all the strings loaded in this object.
     */
    function dumpLanguageFile($lang, $text_array) {
        $fd = @fopen($GLOBALS['codex_cache_dir'].'/lang/'.$lang.'.php', 'w');
        if($fd !== false) {
            fwrite($fd, '<?php'."\n");
            foreach($text_array as $key1 => $level2) {
                foreach($level2 as $key2 => $value) {
                    $str = str_replace("'", "\'", $value);
                    fwrite($fd, '$this->text_array[\''.$key1.'\'][\''.$key2.'\'] = \''.$str.'\';'."\n");
                }
            }
            fwrite($fd, '?>');
            fclose($fd);
        }
    }

    function loadLanguageFile($fname) {
        if (array_key_exists($fname, $this->file_array)) { 
            return; 
        }
        $this->file_array[$fname] = 1;
        $this->parseLanguageFile($fname, $this->text_array);
    }

    /**
     * Parse given .tab file and store the result into $text_array
     */
    function parseLanguageFile($fname, &$text_array) {
        $ary = @file($fname,1);
        for( $i=0; $i<sizeof($ary); $i++) {
            if (substr($ary[$i], 0, 1) == '#' ||  //ignore comments...
                strlen(trim($ary[$i])) == 0) {    //...or empty lines
                continue;
            }
            // Language files can include others for defaults.
            // e.g. an English-Canada.tab file might "include English" first,
            // then override all those whacky American spellings.
            if (preg_match("/^include ([a-zA-Z]+)/", $ary[$i], $matches)) {
                $dir = dirname($fname);
                $this->parseLanguageFile($dir."/".$matches[1].".tab", $text_array);
            } else {
                $line = explode("\t", $ary[$i], 3);
                $text_array[$line[0]][$line[1]] = chop(str_replace('\n', "\n", ($line[2])));
                //echo "(".strlen(trim($ary[$i])).")"."Reading msg :".$line[0]."<b> | </b>".$line[1]."<b> | </b>".$text_array[$line[0]][$line[1]]."<br>";
            }
        }
    }

    // Load the global language file (this is a global message catalog
    // that is loaded for all scripts from pre.php
    function loadLanguage($lang) {
        if($this->lang != $lang) {
            $this->lang = $lang;
            setlocale (LC_TIME, $lang);
            $langFile = $GLOBALS['codex_cache_dir'].'/lang/'.$this->lang.'.php';
            if(file_exists($langFile)) {
                include($langFile);
            } else {
                // If language is supported, the compiled file should exists, try
                // to create it
                $this->text_array = $this->compileLanguage($lang);
            }
        }
    }

    function getText($pagename, $category, $args="") {
        if (!isset($this->lang)) {
            $this->loadLanguage(UserManager::instance()->getCurrentUser()->getLocale());
        }
        /*
            args is an array which will replace the $1, $2, etc
            in the text_array string before it is returned
        */
        if ($args || $args == 0) {
            //$tstring = sprintf($this->text_array[$pagename][$category],$args);
            for ($i=1; $i<=sizeof($args)+1; $i++) {
                $patterns[] = '/\$'.$i.'/';
            }
            $tstring = preg_replace($patterns, $args, $this->text_array[$pagename][$category]);
        } else {
                    // Remove $1, $2 etc. even if the given arguments are empty
                    $pattern = '/\$\d+/';
                    $tstring = preg_replace($pattern, '', $this->text_array[$pagename][$category]);
                    //$tstring = $this->text_array[$pagename][$category];
        }
        if (!$tstring) {
            $tstring = "*** Unkown msg $pagename - $category ***";
        }
        return "$tstring";
    }
    
    function hasText($pagename, $category) {
        return isset($this->text_array[$pagename][$category]);
    }

    // This is a legacy piece of code that used to be utils_get_content
    // and is used either to include long piece of text that are inconvenient
    // to format on one line as the .tab file does or because there is some
    // PHP code that can be cutomized
    function getContent($file, $lang_code = null, $plugin_name = null){

        // Language for current user unless it is specified in the param list
        if (!isset($lang_code)) { 
            $lang_code = $this->lang;
        }

        if (is_null($plugin_name)) {
            // Test first the custom directory
            $custom_fn = $GLOBALS['sys_custom_incdir']."/".$lang_code."/".$file.".txt";
        } else {
            $custom_fn = $GLOBALS['sys_custompluginsroot'].'/'.$plugin_name.'/site-content/'.$lang_code.'/'.$file.'.txt' ;
        }
        if ( file_exists($custom_fn) ) {
            // The custom file exists. 
            return $custom_fn;
        } else {
            // Use the default file
            // Check first if exist
            if (is_null($plugin_name)) {
                $fn = $GLOBALS['sys_incdir']."/".$lang_code."/".$file.".txt";
            } else {
                $fn = $GLOBALS['sys_pluginsroot'].'/'.$plugin_name.'/site-content/'.$lang_code.'/'.$file.".txt";
            }
            if ( file_exists($fn) ) {
                // The custom file exists. 
                return $fn;
            } else {
                if ($lang_code == "en_US") {
                    // return empty content to avoid include error
                    return $GLOBALS['sys_incdir']."/".$lang_code."/others/empty.txt";
                } else {
                    // else try to find the file in the en_US directory
                    return $this->getContent($file, "en_US");
                }
            }
        }
    }

    //result set handle for supported langauges
    var $language_res;

    /**
     * @return array pairs of language_code => Language
     */
    function getLanguages() {
        $languages =  array(
            'en_US' => 'English',
            'fr_FR' => 'Français',
        );
        $ret = array();
        foreach($languages as $key => $value) {
            if (in_array($key, $this->allLanguages)) {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }

    function getLanguageCode() {
        return isset($this->lang) ? $this->lang : $this->defaultLanguage;
    }

    function getEncoding() {
        return $this->text_array['conf']['content_encoding'];
    }

    function getFont() {
        return $this->text_array['conf']['default_font'];
    }

    /** Returns list of loaded language files (for debugging) */
    function getLoadedLangageFiles() {
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
    function parseAcceptLanguage($accept_language) {
        $langs      = array();
        $lang_parse = array();
        
        // break up string into pieces (languages and q factors)
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', 
                       $accept_language,
                       $lang_parse);
        
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
    function getLanguageFromAcceptLanguage($accept_language) {
        $relevant_language = $this->defaultLanguage;
        
        //extract language abbr and country codes from Codendi languages
        $provided_languages = array();
        foreach($this->allLanguages as $lang) {
            list($l,$c) = explode('_', $lang);
            $provided_languages[strtolower($l)][strtolower($c)] = $lang;
        }
        
        //Now do the same thing for accept_language, 
        $parse_accept_lang = $this->parseAcceptLanguage($accept_language);
        foreach($parse_accept_lang as $lang => $score) {
            $lang = explode('-', $lang);
            $l = strtolower($lang[0]);
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
    function isLanguageSupported($language) {
        return in_array($language, $this->allLanguages);
    }
}
?>
