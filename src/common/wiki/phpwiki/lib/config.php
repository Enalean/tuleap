<?php
rcs_id('$Id: config.php,v 1.137 2005/08/06 14:31:10 rurban Exp $');
/*
 * NOTE: The settings here should probably not need to be changed.
 * The user-configurable settings have been moved to IniConfig.php
 * The run-time code has been moved to lib/IniConfig.php:fix_configs()
 */
 
if (!defined("LC_ALL")) {
    // Backward compatibility (for PHP < 4.0.5)
    if (!check_php_version(4,0,5)) {
        define("LC_ALL",   "LC_ALL");
        define("LC_CTYPE", "LC_CTYPE");
    } else {
        define("LC_ALL",   0);
        define("LC_CTYPE", 2);
    }
}
// debug flags: 
define ('_DEBUG_VERBOSE',   1); // verbose msgs and add validator links on footer
define ('_DEBUG_PAGELINKS', 2); // list the extraced pagelinks at the top of each pages
define ('_DEBUG_PARSER',    4); // verbose parsing steps
define ('_DEBUG_TRACE',     8); // test php memory usage, prints php debug backtraces
define ('_DEBUG_INFO',     16);
define ('_DEBUG_APD',      32);
define ('_DEBUG_LOGIN',    64); // verbose login debug-msg (settings and reason for failure)
define ('_DEBUG_SQL',     128);

function isCGI() {
    return (substr(php_sapi_name(),0,3) == 'cgi' and 
            isset($GLOBALS['HTTP_ENV_VARS']['GATEWAY_INTERFACE']) and
            @preg_match('/CGI/',$GLOBALS['HTTP_ENV_VARS']['GATEWAY_INTERFACE']));
}

/*
// copy some $_ENV vars to $_SERVER for CGI compatibility. php does it automatically since when?
if (isCGI()) {
    foreach (explode(':','SERVER_SOFTWARE:SERVER_NAME:GATEWAY_INTERFACE:SERVER_PROTOCOL:SERVER_PORT:REQUEST_METHOD:HTTP_ACCEPT:PATH_INFO:PATH_TRANSLATED:SCRIPT_NAME:QUERY_STRING:REMOTE_HOST:REMOTE_ADDR:REMOTE_USER:AUTH_TYPE:CONTENT_TYPE:CONTENT_LENGTH') as $key) {
        $GLOBALS['HTTP_SERVER_VARS'][$key] = &$GLOBALS['HTTP_ENV_VARS'][$key];
    }
}
*/

// essential internal stuff
set_magic_quotes_runtime(0);

/** 
 * Browser Detection Functions
 *
 * Current Issues:
 *  NS/IE < 4.0 doesn't accept < ? xml version="1.0" ? >
 *  NS/IE < 4.0 cannot display PNG
 *  NS/IE < 4.0 cannot display all XHTML tags
 *  NS < 5.0 needs textarea wrap=virtual
 *  IE55 has problems with transparent PNG's
 * @author: ReiniUrban
 */
function browserAgent() {
    static $HTTP_USER_AGENT = false;
    if ($HTTP_USER_AGENT !== false) return $HTTP_USER_AGENT;
    if (!$HTTP_USER_AGENT)
        $HTTP_USER_AGENT = @$GLOBALS['HTTP_SERVER_VARS']['HTTP_USER_AGENT'];
    if (!$HTTP_USER_AGENT) // CGI
        $HTTP_USER_AGENT = @$GLOBALS['HTTP_ENV_VARS']['HTTP_USER_AGENT'];
    if (!$HTTP_USER_AGENT) // local CGI testing
        $HTTP_USER_AGENT = 'none';
    return $HTTP_USER_AGENT;
}
function browserDetect($match) {
    return strstr(browserAgent(), $match);
}
// returns a similar number for Netscape/Mozilla (gecko=5.0)/IE/Opera features.
function browserVersion() {
    if (strstr(browserAgent(),    "Mozilla/4.0 (compatible; MSIE"))
        return (float) substr(browserAgent(),30);
    elseif (strstr(browserAgent(),"Mozilla/5.0 (compatible; Konqueror/"))
        return (float) substr(browserAgent(),36);
    else
        return (float) substr(browserAgent(),8);
}
function isBrowserIE() {
    return (browserDetect('Mozilla/') and 
            browserDetect('MSIE'));
}
// problem with transparent PNG's
function isBrowserIE55() {
    return (isBrowserIE() and 
            browserVersion() > 5.1 and browserVersion() < 6.0);
}
// old Netscape prior to Mozilla
function isBrowserNetscape($version = false) {
    $agent = (browserDetect('Mozilla/') and 
            ! browserDetect('Gecko/') and
            ! browserDetect('MSIE'));
    if ($version) return $agent and browserVersion() >= $version; 
    else return $agent;
}
// NS3 or less
function isBrowserNS3() {
    return (isBrowserNetscape() and browserVersion() < 4.0);
}
// NS4 or less
function isBrowserNS4() {
    return (isBrowserNetscape() and browserVersion() < 5.0);
}
// must omit display alternate stylesheets: konqueror 3.1.4
// http://sourceforge.net/tracker/index.php?func=detail&aid=945154&group_id=6121&atid=106121
function isBrowserKonqueror($version = false) {
    if ($version) return browserDetect('Konqueror/') and browserVersion() >= $version; 
    return browserDetect('Konqueror/');
}
// MacOSX Safari has certain limitations. Need detection and patches.
// * no <object>, only <embed>
function isBrowserSafari($version = false) {
    if ($version) return browserDetect('Safari/') and browserVersion() >= $version; 
    return browserDetect('Safari/');
}


/**
 * If $LANG is undefined:
 * Smart client language detection, based on our supported languages
 * HTTP_ACCEPT_LANGUAGE="de-at,en;q=0.5"
 *   => "de"
 * We should really check additionally if the i18n HomePage version is defined.
 * So must defer this to the request loop.
 */
function guessing_lang ($languages=false) {
    if (!$languages) {
    	// make this faster
    	$languages = array("en","de","es","fr","it","ja","zh","nl","sv");
        // ignore possible "_<territory>" and codeset "ja.utf8"
        /*
        require_once("lib/Theme.php");
        $languages = listAvailableLanguages();
        if (defined('DEFAULT_LANGUAGE') and in_array(DEFAULT_LANGUAGE, $languages))
        {
            // remove duplicates
            if ($i = array_search(DEFAULT_LANGUAGE, $languages) !== false) {
                array_splice($languages, $i, 1);
            }
            array_unshift($languages, DEFAULT_LANGUAGE);
            foreach ($languages as $lang) {
                $arr = FileFinder::locale_versions($lang);
                $languages = array_merge($languages, $arr);
            }
        }
        */
    }

    if (isset($GLOBALS['request'])) // in fixup-dynamic-config there's no request yet
        $accept = $GLOBALS['request']->get('HTTP_ACCEPT_LANGUAGE');
    elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

    if ($accept) {
        $lang_list = array();
        $list = explode(",", $accept);
        for ($i=0; $i<count($list); $i++) {
            $pos = strchr($list[$i], ";") ;
            if ($pos === false) {
                // No Q it is only a locale...
                $lang_list[$list[$i]] = 100;
            } else {
                // Has a Q rating        
                $q = explode(";",$list[$i]) ;
                $loc = $q[0] ;
                $q = explode("=",$q[1]) ;
                $lang_list[$loc] = $q[1]*100 ;
            }
        }

        // sort by q desc
        arsort($lang_list);

        // compare with languages, ignoring sublang and charset
        foreach ($lang_list as $lang => $q) {
            if (in_array($lang, $languages))
                return $lang;
            // de_DE.iso8859-1@euro => de_DE.iso8859-1, de_DE, de
            // de-DE => de-DE, de
            foreach (array('@', '.', '_') as $sep) {
                if ( ($tail = strchr($lang, $sep)) ) {
                    $lang_short = substr($lang, 0, -strlen($tail));
                    if (in_array($lang_short, $languages))
                        return $lang_short;
                }
            }
            if ($pos = strchr($lang, "-") and in_array(substr($lang, 0, $pos), $languages))
                return substr($lang, 0, $pos);
        }
    }
    return $languages[0];
}

/**
 * Smart setlocale().
 *
 * This is a version of the builtin setlocale() which is
 * smart enough to try some alternatives...
 *
 * @param mixed $category
 * @param string $locale
 * @return string The new locale, or <code>false</code> if unable
 *  to set the requested locale.
 * @see setlocale
 * [56ms]
 */
function guessing_setlocale ($category, $locale) {
    $alt = array('en' => array('C', 'en_US', 'en_GB', 'en_AU', 'en_CA', 'english'),
                 'de' => array('de_DE', 'de_DE', 'de_DE@euro', 
                               'de_AT@euro', 'de_AT', 'German_Austria.1252', 'deutsch', 
                               'german', 'ge'),
                 'es' => array('es_ES', 'es_MX', 'es_AR', 'spanish'),
                 'nl' => array('nl_NL', 'dutch'),
                 'fr' => array('fr_FR', 'fran�ais', 'french'),
                 'it' => array('it_IT'),
                 'sv' => array('sv_SE'),
                 'ja.utf-8'  => array('ja_JP','ja_JP.utf-8','japanese'),
                 'ja.euc-jp' => array('ja_JP','ja_JP.eucJP','japanese.euc'),
                 'zh' => array('zh_TW', 'zh_CN'),
                 );
    if (!$locale or $locale=='C') { 
        // do the reverse: return the detected locale collapsed to our LANG
        $locale = setlocale($category, '');
        if ($locale) {
            if (strstr($locale, '_')) list ($lang) = split('_', $locale);
            else $lang = $locale;
            if (strlen($lang) > 2) { 
                foreach ($alt as $try => $locs) {
                    if (in_array($locale, $locs) or in_array($lang, $locs)) {
                    	//if (empty($GLOBALS['LANG'])) $GLOBALS['LANG'] = $try;
                        return $try;
                    }
                }
            }
        }
    }
    if (strlen($locale) == 2)
        $lang = $locale;
    else 
        list ($lang) = split('_', $locale);
    if (!isset($alt[$lang]))
        return false;
        
    foreach ($alt[$lang] as $try) {
        if ($res = setlocale($category, $try))
            return $res;
        // Try with charset appended...
        $try = $try . '.' . $GLOBALS['charset'];
        if ($res = setlocale($category, $try))
            return $res;
        foreach (array(".", '@', '_') as $sep) {
            if ($i = strpos($try, $sep)) {
                $try = substr($try, 0, $i);
                if (($res = setlocale($category, $try)))
                    return $res;
            }
        }
    }
    return false;
    // A standard locale name is typically of  the  form
    // language[_territory][.codeset][@modifier],  where  language is
    // an ISO 639 language code, territory is an ISO 3166 country code,
    // and codeset  is  a  character  set or encoding identifier like
    // ISO-8859-1 or UTF-8.
}

// [99ms]
function update_locale($loc) {
    // $LANG or DEFAULT_LANGUAGE is too less information, at least on unix for
    // setlocale(), for bindtextdomain() to succeed.
    $setlocale = guessing_setlocale(LC_ALL, $loc); // [56ms]
    if (!$setlocale) { // system has no locale for this language, so gettext might fail
        $setlocale = FileFinder::_get_lang();
        list ($setlocale,) = split('_', $setlocale, 2);
        $setlocale = guessing_setlocale(LC_ALL, $setlocale); // try again
        if (!$setlocale) $setlocale = $loc;
    }
    // Try to put new locale into environment (so any
    // programs we run will get the right locale.)
    if (!function_exists('bindtextdomain'))  {
        // Reinitialize translation array.
        global $locale;
        $locale = array();
        // do reinit to purge PHP's static cache [43ms]
        if ( ($lcfile = FindLocalizedFile("LC_MESSAGES/phpwiki.php", 'missing_ok', 'reinit')) ) {
            include($lcfile);
        }
    } else {
        // If PHP is in safe mode, this is not allowed,
        // so hide errors...
        @putenv("LC_ALL=$setlocale");
        @putenv("LANG=$loc");
        @putenv("LANGUAGE=$loc");
    }

    // To get the POSIX character classes in the PCRE's (e.g.
    // [[:upper:]]) to match extended characters (e.g. Gr��Gott), we have
    // to set the locale, using setlocale().
    //
    // The problem is which locale to set?  We would like to recognize all
    // upper-case characters in the iso-8859-1 character set as upper-case
    // characters --- not just the ones which are in the current $LANG.
    //
    // As it turns out, at least on my system (Linux/glibc-2.2) as long as
    // you setlocale() to anything but "C" it works fine.  (I'm not sure
    // whether this is how it's supposed to be, or whether this is a bug
    // in the libc...)
    //
    // We don't currently use the locale setting for anything else, so for
    // now, just set the locale to US English.
    //
    // FIXME: Not all environments may support en_US?  We should probably
    // have a list of locales to try.
    if (setlocale(LC_CTYPE, 0) == 'C') {
        $x = setlocale(LC_CTYPE, 'en_US.' . $GLOBALS['charset']);
    } else {
        $x = setlocale(LC_CTYPE, $setlocale);
    }

    return $loc;
}

/** string pcre_fix_posix_classes (string $regexp)
*
* Older version (pre 3.x?) of the PCRE library do not support
* POSIX named character classes (e.g. [[:alnum:]]).
*
* This is a helper function which can be used to convert a regexp
* which contains POSIX named character classes to one that doesn't.
*
* All instances of strings like '[:<class>:]' are replaced by the equivalent
* enumerated character class.
*
* Implementation Notes:
*
* Currently we use hard-coded values which are valid only for
* ISO-8859-1.  Also, currently on the classes [:alpha:], [:alnum:],
* [:upper:] and [:lower:] are implemented.  (The missing classes:
* [:blank:], [:cntrl:], [:digit:], [:graph:], [:print:], [:punct:],
* [:space:], and [:xdigit:] could easily be added if needed.)
*
* This is a hack.  I tried to generate these classes automatically
* using ereg(), but discovered that in my PHP, at least, ereg() is
* slightly broken w.r.t. POSIX character classes.  (It includes
* "\xaa" and "\xba" in [:alpha:].)
*
* So for now, this will do.  --Jeff <dairiki@dairiki.org> 14 Mar, 2001
*/
function pcre_fix_posix_classes ($regexp) {
    global $charset;
    if (!isset($charset))
        $charset = CHARSET; // get rid of constant. pref is dynamic and language specific
    if (in_array($GLOBALS['LANG'], array('zh')))
        $charset = 'utf-8';
    if (strstr($GLOBALS['LANG'],'.utf-8'))
        $charset = 'utf-8';
    elseif (strstr($GLOBALS['LANG'],'.euc-jp'))
        $charset = 'euc-jp';
    elseif (in_array($GLOBALS['LANG'], array('ja')))
        //$charset = 'utf-8';
        $charset = 'euc-jp';

    if (strtolower($charset) == 'utf-8') { // thanks to John McPherson
        // until posix class names/pcre work with utf-8
	if (preg_match('/[[:upper:]]/', '\xc4\x80'))
            return $regexp;    
        // utf-8 non-ascii chars: most common (eg western) latin chars are 0xc380-0xc3bf
        // we currently ignore other less common non-ascii characters
        // (eg central/east european) latin chars are 0xc432-0xcdbf and 0xc580-0xc5be
        // and indian/cyrillic/asian languages
        
        // this replaces [[:lower:]] with utf-8 match (Latin only)
        $regexp = preg_replace('/\[\[\:lower\:\]\]/','(?:[a-z]|\xc3[\x9f-\xbf]|\xc4[\x81\x83\x85\x87])',
                               $regexp);
        // this replaces [[:upper:]] with utf-8 match (Latin only)
        $regexp = preg_replace('/\[\[\:upper\:\]\]/','(?:[A-Z]|\xc3[\x80-\x9e]|\xc4[\x80\x82\x84\x86])',
                               $regexp);
    } elseif (preg_match('/[[:upper:]]/', '�')) {
        // First check to see if our PCRE lib supports POSIX character
        // classes.  If it does, there's nothing to do.
        return $regexp;
    }
    static $classes = array(
                            'alnum' => "0-9A-Za-z\xc0-\xd6\xd8-\xf6\xf8-\xff",
                            'alpha' => "A-Za-z\xc0-\xd6\xd8-\xf6\xf8-\xff",
                            'upper' => "A-Z\xc0-\xd6\xd8-\xde",
                            'lower' => "a-z\xdf-\xf6\xf8-\xff"
                            );
    $keys = join('|', array_keys($classes));
    return preg_replace("/\[:($keys):]/e", '$classes["\1"]', $regexp);
}

function deduce_script_name() {
    $s = &$GLOBALS['HTTP_SERVER_VARS'];
    $script = @$s['SCRIPT_NAME'];
    if (empty($script) or $script[0] != '/') {
        // Some places (e.g. Lycos) only supply a relative name in
        // SCRIPT_NAME, but give what we really want in SCRIPT_URL.
        if (!empty($s['SCRIPT_URL']))
            $script = $s['SCRIPT_URL'];
    }
    return $script;
}

function IsProbablyRedirectToIndex () {
    // This might be a redirect to the DirectoryIndex,
    // e.g. REQUEST_URI = /dir/?some_action got redirected
    // to SCRIPT_NAME = /dir/index.php

    // In this case, the proper virtual path is still
    // $SCRIPT_NAME, since pages appear at
    // e.g. /dir/index.php/HomePage.

    $requri = preg_replace('/\?.*$/','',$GLOBALS['HTTP_SERVER_VARS']['REQUEST_URI']);
    $requri = preg_quote($requri, '%');
    return preg_match("%^${requri}[^/]*$%", $GLOBALS['HTTP_SERVER_VARS']['SCRIPT_NAME']);
}

// >= php-4.1.0
if (!function_exists('array_key_exists')) { // lib/IniConfig.php, sqlite, adodb, ...
    function array_key_exists($item, $array) {
        return isset($array[$item]);
    }
}

// => php-4.0.5
if (!function_exists('is_scalar')) { // lib/stdlib.php:wikihash()
    function is_scalar($x) {
        return is_numeric($x) or is_string($x) or is_float($x) or is_bool($x); 
    }
}

// => php-4.2.0. pear wants to break old php's! DB uses it now.
if (!function_exists('is_a')) {
    function is_a($item,$class) {
        return isa($item,$class); 
    }
}

// needed < php5
// by bradhuizenga at softhome dot net from the php docs
if (!function_exists('str_ireplace')) {
  function str_ireplace($find, $replace, $string) {
      if (!is_array($find)) $find = array($find);
      if (!is_array($replace)) {
          if (!is_array($find)) 
              $replace = array($replace);
          else {
              // this will duplicate the string into an array the size of $find
              $c = count($find);
              $rString = $replace;
              unset($replace);
              for ($i = 0; $i < $c; $i++) {
                  $replace[$i] = $rString;
              }
          }
      }
      foreach ($find as $fKey => $fItem) {
          $between = explode(strtolower($fItem),strtolower($string));
          $pos = 0;
          foreach ($between as $bKey => $bItem) {
              $between[$bKey] = substr($string,$pos,strlen($bItem));
              $pos += strlen($bItem) + strlen($fItem);
          }
          $string = implode($replace[$fKey], $between);
      }
      return($string);
  }
}

/**
 * safe php4 definition for clone.
 * php5 copies objects by reference, but we need to clone "deep copy" in some places.
 * (BlockParser)
 * We need to eval it as workaround for the php5 parser.
 * See http://www.acko.net/node/54
 */
if (!check_php_version(5)) {
    eval('
    function clone($object) {
      return $object;
    }
    ');
}

/** 
 * wordwrap() might crash between 4.1.2 and php-4.3.0RC2, fixed in 4.3.0
 * See http://bugs.php.net/bug.php?id=20927 and 
 * http://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2002-1396
 * Improved version of wordwrap2() in the comments at http://www.php.net/wordwrap
 */
function safe_wordwrap($str, $width=80, $break="\n", $cut=false) {
    if (check_php_version(4,3))
        return wordwrap($str, $width, $break, $cut);
    elseif (!check_php_version(4,1,2))
        return wordwrap($str, $width, $break, $cut);
    else {
        $len = strlen($str);
        $tag = 0; $result = ''; $wordlen = 0;
        for ($i = 0; $i < $len; $i++) {
            $chr = $str[$i];
            // don't break inside xml tags
            if ($chr == '<') {
                $tag++;
            } elseif ($chr == '>') {
                $tag--;
            } elseif (!$tag) {
                if (!function_exists('ctype_space')) {
                    if (preg_match('/^\s$/', $chr))
                        $wordlen = 0;
                    else
                        $wordlen++;
                }
                elseif (ctype_space($chr)) {
                    $wordlen = 0;
                } else {
                    $wordlen++;
                }
            }
            if ((!$tag) && ($wordlen) && (!($wordlen % $width))) {
                $chr .= $break;
            }
            $result .= $chr;
        }
        return $result;
        /*
        if (isset($str) && isset($width)) {
            $ex = explode(" ", $str); // wrong: must use preg_split \s+
            $rp = array();
            for ($i=0; $i<count($ex); $i++) {
                // $word_array = preg_split('//', $ex[$i], -1, PREG_SPLIT_NO_EMPTY);
                // delete #&& !is_numeric($ex[$i])# if you want force it anyway
                if (strlen($ex[$i]) > $width && !is_numeric($ex[$i])) {
                    $where = 0;
                    $rp[$i] = "";
                    for($b=0; $b < (ceil(strlen($ex[$i]) / $width)); $b++) {
                        $rp[$i] .= substr($ex[$i], $where, $width).$break;
                        $where += $width;
                    }
                } else {
                    $rp[$i] = $ex[$i];
                }
            }
            return implode(" ",$rp);
        }
        return $text;
        */
    }
}

function getUploadFilePath() {
    return defined('PHPWIKI_DIR') 
        ? PHPWIKI_DIR . "/uploads/" 
        : realpath(dirname(__FILE__) . "/../uploads/");
}
function getUploadDataPath() {
  return SERVER_URL . ((substr(DATA_PATH,0,1)=='/') ? '' : "/") . DATA_PATH . '/uploads/'.GROUP_ID.'/';
}

// $Log: config.php,v $
// Revision 1.137  2005/08/06 14:31:10  rurban
// ensure absolute uploads path
//
// Revision 1.136  2005/05/06 16:49:24  rurban
// Safari comment
//
// Revision 1.135  2005/04/01 15:22:20  rurban
// Implement icase and regex options.
// Change checkbox case message from "Case-Sensitive" to "Case-Insensitive"
//
// Revision 1.134  2005/03/27 18:23:39  rurban
// compute locale only for setlocale and LC_ALL
//
// Revision 1.133  2005/02/08 13:26:59  rurban
//  improve the locale splitter
//
// Revision 1.132  2005/02/07 15:39:02  rurban
// another locale fix
//
// Revision 1.131  2005/02/05 15:32:09  rurban
// force guessing_setlocale (again)
//
// Revision 1.130  2005/01/29 20:36:44  rurban
// very important php5 fix! clone objects
//
// Revision 1.129  2005/01/08 22:53:50  rurban
// hardcode list of langs (file access is slow)
// fix client detection
// set proper locale on empty locale
//
// Revision 1.128  2005/01/04 20:22:46  rurban
// guess $LANG based on client
//
// Revision 1.127  2004/12/26 17:15:32  rurban
// new reverse locale detection on DEFAULT_LANGUAGE="", ja default euc-jp again
//
// Revision 1.126  2004/12/20 16:05:00  rurban
// gettext msg unification
//
// Revision 1.125  2004/11/21 11:59:18  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.124  2004/11/09 17:11:16  rurban
// * revert to the wikidb ref passing. there's no memory abuse there.
// * use new wikidb->_cache->_id_cache[] instead of wikidb->_iwpcache, to effectively
//   store page ids with getPageLinks (GleanDescription) of all existing pages, which
//   are also needed at the rendering for linkExistingWikiWord().
//   pass options to pageiterator.
//   use this cache also for _get_pageid()
//   This saves about 8 SELECT count per page (num all pagelinks).
// * fix passing of all page fields to the pageiterator.
// * fix overlarge session data which got broken with the latest ACCESS_LOG_SQL changes
//
// Revision 1.123  2004/11/05 21:03:27  rurban
// new DEBUG flag: _DEBUG_LOGIN (64)
//   verbose login debug-msg (settings and reason for failure)
//
// Revision 1.122  2004/10/14 17:49:58  rurban
// fix warning in safe_wordwrap
//
// Revision 1.121  2004/10/14 17:48:19  rurban
// typo in safe_wordwrap
//
// Revision 1.120  2004/09/22 13:46:26  rurban
// centralize upload paths.
// major WikiPluginCached feature enhancement:
//   support _STATIC pages in uploads/ instead of dynamic getimg.php? subrequests.
//   mainly for debugging, cache problems and action=pdf
//
// Revision 1.119  2004/09/16 07:50:37  rurban
// wordwrap() might crash between 4.1.2 and php-4.3.0RC2, fixed in 4.3.0
// See http://bugs.php.net/bug.php?id=20927 and
//     http://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2002-1396
// Improved version of wordwrap2() from the comments at http://www.php.net/wordwrap
//
// Revision 1.118  2004/07/13 14:03:31  rurban
// just some comments
//
// Revision 1.117  2004/06/21 17:29:17  rurban
// pear DB introduced a is_a requirement. so pear lost support for php < 4.2.0
//
// Revision 1.116  2004/06/21 08:39:37  rurban
// pear/Cache update from Cache-1.5.4 (added db and trifile container)
// pear/DB update from DB-1.6.1 (mysql bugfixes, php5 compat, DB_PORTABILITY features)
//
// Revision 1.115  2004/06/20 14:42:54  rurban
// various php5 fixes (still broken at blockparser)
//
// Revision 1.114  2004/06/19 11:48:05  rurban
// moved version check forwards: already needed in XmlElement::_quote
//
// Revision 1.113  2004/06/03 12:59:41  rurban
// simplify translation
// NS4 wrap=virtual only
//
// Revision 1.112  2004/06/02 18:01:46  rurban
// init global FileFinder to add proper include paths at startup
//   adds PHPWIKI_DIR if started from another dir, lib/pear also
// fix slashify for Windows
// fix USER_AUTH_POLICY=old, use only USER_AUTH_ORDER methods (besides HttpAuth)
//
// Revision 1.111  2004/05/17 17:43:29  rurban
// CGI: no PATH_INFO fix
//
// Revision 1.110  2004/05/16 23:10:44  rurban
// update_locale wrongly resetted LANG, which broke japanese.
// japanese now correctly uses EUC_JP, not utf-8.
// more charset and lang headers to help the browser.
//
// Revision 1.109  2004/05/08 14:06:12  rurban
// new support for inlined image attributes: [image.jpg size=50x30 align=right]
// minor stability and portability fixes
//
// Revision 1.108  2004/05/08 11:25:16  rurban
// php-4.0.4 fixes
//
// Revision 1.107  2004/05/06 17:30:38  rurban
// CategoryGroup: oops, dos2unix eol
// improved phpwiki_version:
//   pre -= .0001 (1.3.10pre: 1030.099)
//   -p1 += .001 (1.3.9-p1: 1030.091)
// improved InstallTable for mysql and generic SQL versions and all newer tables so far.
// abstracted more ADODB/PearDB methods for action=upgrade stuff:
//   backend->backendType(), backend->database(),
//   backend->listOfFields(),
//   backend->listOfTables(),
//
// Revision 1.106  2004/05/02 19:12:14  rurban
// fix sf.net bug #945154 Konqueror alt css
//
// Revision 1.105  2004/05/02 15:10:06  rurban
// new finally reliable way to detect if /index.php is called directly
//   and if to include lib/main.php
// new global AllActionPages
// SetupWiki now loads all mandatory pages: HOME_PAGE, action pages, and warns if not.
// WikiTranslation what=buttons for Carsten to create the missing MacOSX buttons
// PageGroupTestOne => subpages
// renamed PhpWikiRss to PhpWikiRecentChanges
// more docs, default configs, ...
//
// Revision 1.104  2004/05/01 11:26:37  rurban
// php-4.0.x support: array_key_exists (PHP 4 >= 4.1.0)
//
// Revision 1.103  2004/04/30 00:04:14  rurban
// zh (chinese language) support
//
// Revision 1.102  2004/04/29 23:25:12  rurban
// re-ordered locale init (as in 1.3.9)
// fixed loadfile with subpages, and merge/restore anyway
//   (sf.net bug #844188)
//
// Revision 1.101  2004/04/26 13:22:32  rurban
// calculate bool old or dynamic constants later
//
// Revision 1.100  2004/04/26 12:15:01  rurban
// check default config values
//
// Revision 1.99  2004/04/21 14:04:24  zorloc
// 'Require lib/FileFinder.php' necessary to allow for call to FindLocalizedFile().
//
// Revision 1.98  2004/04/20 18:10:28  rurban
// config refactoring:
//   FileFinder is needed for WikiFarm scripts calling index.php
//   config run-time calls moved to lib/IniConfig.php:fix_configs()
//   added PHPWIKI_DIR smart-detection code (Theme finder)
//   moved FileFind to lib/FileFinder.php
//   cleaned lib/config.php
//
// Revision 1.97  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>