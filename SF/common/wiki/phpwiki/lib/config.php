<?php
rcs_id('$Id$');
/*
 * NOTE: The settings here should probably not need to be changed.
 * The user-configurable settings have been moved to IniConfig.php
 * The run-time code have been moved to lib/IniConfig.php:fix_configs()
 */
 
/** 
 * Returns true if current php version is at mimimum a.b.c 
 * Called: check_php_version(4,1)
 */
function check_php_version ($a = '0', $b = '0', $c = '0') {
    static $PHP_VERSION;
    if (!isset($PHP_VERSION))
        $PHP_VERSION = substr( str_pad( preg_replace('/\D/','', PHP_VERSION), 3, '0'), 0, 3);
    return ($PHP_VERSION >= ($a.$b.$c));
}

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

function isCGI() {
    return @preg_match('/CGI/',$GLOBALS['HTTP_ENV_VARS']['GATEWAY_INTERFACE']);
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
    if (!$HTTP_USER_AGENT)
        $HTTP_USER_AGENT = @$GLOBALS['HTTP_SERVER_VARS']['HTTP_USER_AGENT'];
    if (!$HTTP_USER_AGENT) // CGI
        $HTTP_USER_AGENT = $GLOBALS['HTTP_ENV_VARS']['HTTP_USER_AGENT'];
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
function isBrowserNetscape() {
    return (browserDetect('Mozilla/') and 
            ! browserDetect('Gecko/') and
            ! browserDetect('MSIE'));
}
// NS3 or less
function isBrowserNS3() {
    return (isBrowserNetscape() and browserVersion() < 4.0);
}
// must omit display alternate stylesheets: konqueror 3.1.4
// http://sourceforge.net/tracker/index.php?func=detail&aid=945154&group_id=6121&atid=106121
function isBrowserKonqueror($version = false) {
    if ($version) return browserDetect('Konqueror/') and browserVersion() >= $version; 
    return browserDetect('Konqueror/');
}

/**
 * Smart? setlocale().
 *
 * This is a version of the builtin setlocale() which is
 * smart enough to try some alternatives...
 *
 * @param mixed $category
 * @param string $locale
 * @return string The new locale, or <code>false</code> if unable
 *  to set the requested locale.
 * @see setlocale
 */
function guessing_setlocale ($category, $locale) {
    if ($res = setlocale($category, $locale))
        return $res;
    $alt = array('en' => array('C', 'en_US', 'en_GB', 'en_AU', 'en_CA', 'english'),
                 'de' => array('de_DE', 'de_DE', 'de_DE@euro', 
                               'de_AT@euro', 'de_AT', 'German_Austria.1252', 'deutsch', 
                               'german', 'ge'),
                 'es' => array('es_ES', 'es_MX', 'es_AR', 'spanish'),
                 'nl' => array('nl_NL', 'dutch'),
                 'fr' => array('fr_FR', 'français', 'french'),
                 'it' => array('it_IT'),
                 'sv' => array('sv_SE'),
                 'ja' => array('ja_JP','ja_JP.eucJP','japanese.euc'),
                 'zh' => array('zh_TW', 'zh_CN'),
                 );
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
        foreach (array('@', ".", '_') as $sep) {
            list ($try) = split($sep, $try);
            if ($res = setlocale($category, $try))
                return $res;
        }
    }
    return false;

    // A standard locale name is typically of  the  form
    // language[_territory][.codeset][@modifier],  where  language is
    // an ISO 639 language code, territory is an ISO 3166 country code,
    // and codeset  is  a  character  set or encoding identifier like
    // ISO-8859-1 or UTF-8.
}

function update_locale($loc) {
    require_once(dirname(__FILE__)."/FileFinder.php");
    $newlocale = guessing_setlocale(LC_ALL, $loc);
    if (!$newlocale) {
        //trigger_error(sprintf(_("Can't setlocale(LC_ALL,'%s')"), $loc), E_USER_NOTICE);
        // => LC_COLLATE=C;LC_CTYPE=German_Austria.1252;LC_MONETARY=C;LC_NUMERIC=C;LC_TIME=C
        //$loc = setlocale(LC_CTYPE, '');  // pull locale from environment.
        $newlocale = FileFinder::_get_lang();
        list ($newlocale,) = split('_', $newlocale, 2);
        //$GLOBALS['LANG'] = $loc;
        //$newlocale = $loc;
        //return false;
    }
    //if (substr($newlocale,0,2) == $loc) // don't update with C or failing setlocale
    $GLOBALS['LANG'] = $loc;
    // Try to put new locale into environment (so any
    // programs we run will get the right locale.)
    //
    // If PHP is in safe mode, this is not allowed,
    // so hide errors...
    @putenv("LC_ALL=$newlocale");
    @putenv("LANG=$newlocale");
    @putenv("LANGUAGE=$newlocale");
    
    if (!function_exists ('bindtextdomain'))  {
        // Reinitialize translation array.
        global $locale;
        $locale = array();
        // do reinit to purge PHP's static cache
        if ( ($lcfile = FindLocalizedFile("LC_MESSAGES/phpwiki.php", 'missing_ok','reinit')) ) {
            include($lcfile);
        }
    }

    // To get the POSIX character classes in the PCRE's (e.g.
    // [[:upper:]]) to match extended characters (e.g. GrüßGott), we have
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
        $x = setlocale(LC_CTYPE, $newlocale);
    }

    return $newlocale;
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
    if (in_array($GLOBALS['LANG'],array('ja','zh')))
        $charset = 'utf-8';
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
    } elseif (preg_match('/[[:upper:]]/', 'Ä')) {
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
if (!function_exists('is_scalar')) { // lib/stdlib.php:hash()
    function is_scalar($x) {
        return is_numeric($x) or is_string($x) or is_float($x) or is_bool($x); 
    }
}

// $Log$
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
