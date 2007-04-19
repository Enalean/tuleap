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

	function BaseLanguage() {
		$this->loadLanguage('en_US');
	}

	/**
	 * loadLanguageMsg($fname)
	 * Load an individual language file. This is used by indivudual
	 * php scripts to use their specific message catalog. fname is relative
	 * $sys_incdir with no .tab extension at the end
	 *
	 * @param		string	path of language file to load
	 */
	function loadLanguageMsg($fname, $plugin_name = null) {
        global $sys_user_theme;
        
        if (is_null($plugin_name)) {
            // load default message file
            $ftname = $GLOBALS['sys_incdir'].'/'.$this->lang.'/'.$fname.'.tab' ;
        } else {
            // load default message file for plugin
            $ftname = $GLOBALS['sys_pluginsroot'].'/'.$plugin_name.'/site-content/'.$this->lang.'/'.$fname.'.tab';
        }
        if (!file_exists ($ftname)) {
            // If the file does not exist in the selected language, use the default language (en_US)
            $ftname = $GLOBALS['sys_incdir'].'/en_US/'.$fname.'.tab' ;
        }
        // load message file
        $this->loadLanguageFile($ftname) ;
        
        // load site-local customizations
        if (is_null($plugin_name)) {
            $ftname = $GLOBALS['sys_custom_incdir'].'/'.$this->lang.'/'.$fname.'.tab' ;
        } else {
            if (isset($GLOBALS['sys_custompluginsroot']))
                $ftname = $GLOBALS['sys_custompluginsroot'].'/'.$plugin_name.'/site-content/'.$this->lang.'/'.$fname.'.tab';
        }
        if (file_exists ($ftname)) {
            $this->loadLanguageFile($ftname) ;
        }
        
        // load customization by theme
        $ftname = '../themes/'.$sys_user_theme.'/'.$this->lang.'/'.$fname.'.tab' ;
        if (file_exists ($ftname)) {
            $this->loadLanguageFile($ftname) ;
        }
        
        //load site-local customizations by theme
        $ftname = $GLOBALS['sys_custom_themeroot'].'/messages/'.$sys_user_theme.'/'.$this->lang.'/'.$fname.'.tab' ;
        if (file_exists ($ftname)) {
            $this->loadLanguageFile($ftname) ;
        }
	}

	function loadLanguageFile($fname) {
		if (array_key_exists($fname, $this->file_array)) { return; }
		$this->file_array[$fname] = 1;
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
				$this->loadLanguageFile($dir."/".$matches[1].".tab");
			} else {
				$line = explode("\t", $ary[$i], 3);
				$this->text_array[$line[0]][$line[1]] = chop(str_replace('\n', "\n", ($line[2])));
				//echo "(".strlen(trim($ary[$i])).")"."Reading msg :".$line[0]."<b> | </b>".$line[1]."<b> | </b>".$this->text_array[$line[0]][$line[1]]."<br>";
			}
		}
	}

	function loadLanguageID($language_id) {
		$res=db_query("SELECT * FROM supported_languages WHERE language_id='$language_id'");
		$this->loadLanguage(db_result($res,0,'language_code'));
	}

	// Load the global language file (this is a global message catalog
	// that is loaded for all scripts from pre.php
	function loadLanguage($lang) {
		global $sys_user_theme;
		if ($this->lang == $lang) { return; }
		$fname = $GLOBALS['sys_incdir'].'/'.$lang.'/'.$lang.'.tab' ;
		$this->loadLanguageFile($fname) ;
		// Site-local customizations
		$fname = $GLOBALS['sys_custom_incdir'].'/'.$lang.'/'.$lang.'.tab' ;
		if (file_exists ($fname)) {
			$this->loadLanguageFile($fname) ;
		}
		//Customization by theme
		$ftname = '../themes/'.$sys_user_theme.'/'.$lang.'/'.$lang.'.tab' ;
		if (file_exists ($ftname)) {
			$this->loadLanguageFile($ftname) ;
		}
		$this->lang = $lang ;
	}

	function getText($pagename, $category, $args="") {
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

	// This is a legacy piece of code that used to be utils_get_content
	// and is used either to include long piece of text that are inconvenient
	// to format on one line as the .tab file does or because there is some
	// PHP code that can be cutomized
	function getContent($file, $lang_code = null, $plugin_name = null){

	    // Language for current user unless it is specified in the param list
	    if (!isset($lang_code)) { $lang_code = $this->getLanguageCode(); }

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
		    if ($lang_code == "en_US")
			// return empty content to avoid include error
			return $GLOBALS['sys_incdir']."/".$lang_code."/others/empty.txt";
		    else
			// else try to find the file in the en_US directory
			return $this->getContent($file, "en_US");
		}
	    }
	}

	//result set handle for supported langauges
	var $language_res;

	/*
		returns database result
		of supported languages
	*/
	function getLanguages() {
                if (!isset($this->text_array['conf']['language_res']) || !$this->text_array['conf']['language_res']) {
			$this->text_array['conf']['language_res']=db_query("SELECT * FROM supported_languages WHERE active=1 ORDER BY name ASC");
		}
		return $this->text_array['conf']['language_res'];
	}

	function getLanguageId() {
		if (!$this->id) {
			$this->id = db_result(db_query("SELECT language_id FROM supported_languages WHERE language_code='".$this->lang."'"), 0, 0) ;
		}
		return $this->id ;
	}

	function getLanguageName() {
		if (!$this->name) {
			$id = $this->getLanguageId () ;
			$this->name = db_result(db_query("SELECT name FROM supported_languages WHERE language_id='$id'"), 0, 0) ;
		}
		return $this->name ;
	}

	function getLanguageCode() {
		if (!$this->code) {
			$id = $this->getLanguageId () ;
			$this->code = db_result(db_query("SELECT language_code FROM supported_languages WHERE language_id='$id'"), 0, 0) ;
		}
		return $this->code ;
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

}

function language_code_to_result($alang) {
	global $cookie_language_id;

	/*


		Determine which language file to use

		It depends on whether the user has set a cookie or not using
		the account page or the left-hand nav or how their browser is
		set or whether they are logged in or not

		if logged in, use language from users table
		else check for cookie and use that value if valid
		if no cookie check browser preference and use that language if valid
		else just use system default language

	*/

	if ($cookie_language_id) {
		$lang=$cookie_language_id;
		$res=db_query("select * from supported_languages where language_id='$lang'");
		if (!$res || db_numrows($res) < 1) {
			return db_query("select * from supported_languages where language_id='1'"); // default to english
		} else {
			return $res;
		}
	} else {
		$ary = explode(',', str_replace(' ', '', $alang)); // delete space and split
		for( $i=0; $i<sizeof($ary); $i++){
			$lang_code = ereg_replace(';.*', '', $ary[$i]); // remove ;q=0.x
			$res = db_query("select * from supported_languages where language_code = '$lang_code'");
			if (db_numrows($res) > 0) {
			    return $res;
			}

			// If that didn't work:
			// - First substitute - with _ as the database
			// uses en_US and not en-us as Mozilla and IE do
			// - Second check if we have sublanguage specifier
			// If so, try to strip it and look for main language only
			if (strstr($lang_code, '-')) {
			    $lang_code = str_replace('-','_',$lang_code);
			    $res = db_query("select * from supported_languages where language_code = '$lang_code'");
			    if (db_numrows($res) > 0) {
				return $res;
			    }
			    
			    $lang_code = substr($lang_code, 0, 2);
			    $res = db_query("select * from supported_languages where language_code = '$lang_code'");
			    if (db_numrows($res) > 0) {
				return $res;
			    }
			}
			
			// If that didn't work:
			// Test only the two first letter of the browser language
			// as the database uses en_US and not en alone as Mozilla and IE can do
			$lang_code = substr($lang_code, 0, 2);
			$res = db_query("select * from supported_languages where SUBSTRING(language_code, 1, 2) = '$lang_code'");
			if (db_numrows($res) > 0) {
			    return $res;
			}
		}
		return db_query("select * from supported_languages where language_code='".$GLOBALS['sys_lang']."'"); // default to system default
	}
}

/* Return language code (e.g. 'en_US') corresponding to the language ID (e.g. '1'). */
function language_id_to_language_code($language_id=1) {
    $res=db_query("select language_code from supported_languages where language_id='".$language_id."'");
    return db_result($res,0,'language_code');
}


?>
