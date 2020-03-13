<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

require_once(dirname(__FILE__) . '/stdlib.php');

/**
 * A class for finding files.
 *
 * This should really provided by pear. We don't want really to mess around
 * with all the lousy systems. (WindowsNT, Win95, Mac, VMS, ...)
 * But pear has only System and File, which do nothing.
 * Anyway, in good PHP style we ignore the rest of the world and try to behave
 * as on unix only. That means we use / as pathsep in all our constants.
 */
class FileFinder
{
    //var $_pathsep, $_path;

    /**
     *
     *
     * @param $path array A list of directories in which to search for files.
     */
    public function __construct($path = false)
    {
        $this->_pathsep = $this->_get_syspath_separator();
        if (!isset($this->_path) and $path === false) {
            $path = $this->_get_include_path();
        }
        $this->_path = $path;
    }

    /**
     * Find file.
     *
     * @param $file string File to search for.
     * @return string The filename (including path), if found, otherwise false.
     */
    public function findFile($file, $missing_okay = false)
    {
        if ($this->_is_abs($file)) {
            if (file_exists($file)) {
                return $file;
            }
        } elseif (($dir = $this->_search_path($file))) {
            return $dir . $this->_use_path_separator($dir) . $file;
        }
        return $missing_okay ? false : $this->_not_found($file);
    }

    /**
     * Unify used pathsep character.
     * Accepts array of paths also.
     * This might not work on Windows95 or FAT volumes. (not tested)
     */
    public function slashifyPath($path)
    {
        return $this->forcePathSlashes($path, $this->_pathsep);
    }

    /**
     * Force using '/' as path seperator.
     */
    public function forcePathSlashes($path, $sep = '/')
    {
        if (is_array($path)) {
            $result = array();
            foreach ($path as $dir) {
                $result[] = $this->forcePathSlashes($dir, $sep);
            }
            return $result;
        } else {
            if (isWindows() or $this->_isOtherPathsep()) {
                if (isMac()) {
                    $from = ":";
                } elseif (isWindows()) {
                    $from = "\\";
                } else {
                    $from = "\\";
                }
                // PHP is stupid enough to use \\ instead of \
                if (isWindows()) {
                    if (substr($path, 0, 2) != '\\\\') {
                        $path = str_replace('\\\\', '\\', $path);
                    } else { // UNC paths
                        $path = '\\\\' . str_replace('\\\\', '\\', substr($path, 2));
                    }
                }
                return strtr($path, $from, $sep);
            } else {
                return $path;
            }
        }
    }

    /**
     * Try to include file.
     *
     * If file is found in the path, then the files directory is added
     * to PHP's include_path (if it's not already there.) Then the
     * file is include_once()'d.
     *
     * @param $file string File to include.
     * @return bool True if file was successfully included.
     */
    public function includeOnce($file)
    {
        if (($ret = @include_once($file))) {
            return $ret;
        }

        if (!$this->_is_abs($file)) {
            if (($dir = $this->_search_path($file)) && is_file($dir . $this->_pathsep . $file)) {
                $this->_append_to_include_path($dir);
                return include_once($file);
            }
        }
        return $this->_not_found($file);
    }

    public function _isOtherPathsep()
    {
        return $this->_pathsep != '/';
    }

    /**
     * The system-dependent path-separator character.
     * UNIX,WindowsNT,MacOSX: /
     * Windows95: \
     * Mac:       :
     *
     * @access private
     * @return string path_separator.
     */
    public function _get_syspath_separator()
    {
        if (!empty($this->_pathsep)) {
            return $this->_pathsep;
        } elseif (isWindowsNT()) {
            return "/"; // we can safely use '/'
        } elseif (isWindows()) {
            return "\\";  // FAT might use '\'
        } elseif (isMac()) {
            return ':';    // MacOsX is /
        } else { // VMS or LispM is really weird, we ignore it.
            return '/';
        }
    }

    /**
     * The path-separator character of the given path.
     * Windows accepts "/" also, but gets confused with mixed path_separators,
     * e.g "C:\Apache\phpwiki/locale/button"
     * > dir "C:\Apache\phpwiki/locale/button" =>
     *       Parameterformat nicht korrekt - "locale"
     * So if there's any '\' in the path, either fix them to '/' (not in Win95 or FAT?)
     * or use '\' for ours.
     *
     * @access private
     * @return string path_separator.
     */
    public function _use_path_separator($path)
    {
        if (isWindows95()) {
            if (empty($path)) {
                return "\\";
            } else {
                return (strchr($path, "\\")) ? "\\" : '/';
            }
        } elseif (isMac()) {
            if (empty($path)) {
                return ":";
            } else {
                return (strchr($path, ":")) ? ":" : '/';
            }
        } else {
            return $this->_get_syspath_separator();
        }
    }

    /**
     * Determine if path is absolute.
     *
     * @access private
     * @param $path string Path.
     * @return bool True if path is absolute.
     */
    public function _is_abs($path)
    {
        if (preg_match('#^/#D', $path)) {
            return true;
        } elseif (isWindows() and (preg_match('#^[a-z]:[/\\]#Di', $path))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Strip ending '/' or '\' from path.
     *
     * @access private
     * @param $path string Path.
     * @return bool New path (destructive)
     */
    public function _strip_last_pathchar(&$path)
    {
        if (isMac()) {
            if (substr($path, -1) == ':' or substr($path, -1) == "/") {
                $path = substr($path, 0, -1);
            }
        } else {
            if (substr($path, -1) == '/' or substr($path, -1) == "\\") {
                $path = substr($path, 0, -1);
            }
        }
        return $path;
    }

    /**
     * Report a "file not found" error.
     *
     * @access private
     * @param $file string Name of missing file.
     * @return bool false.
     */
    public function _not_found($file)
    {
        trigger_error(sprintf(_("%s: file not found"), $file), E_USER_ERROR);
        return false;
    }


    /**
     * Search our path for a file.
     *
     * @access private
     * @param $file string File to find.
     * @return string Directory which contains $file, or false.
     * [5x,44ms]
     */
    public function _search_path($file)
    {
        foreach ($this->_path as $dir) {
            // ensure we use the same pathsep
            if ($this->_isOtherPathsep()) {
                $dir = $this->slashifyPath($dir);
                $file = $this->slashifyPath($file);
                if (file_exists($dir . $this->_pathsep . $file)) {
                    return $dir;
                }
            } elseif (@file_exists($dir . $this->_pathsep . $file)) {
                   return $dir;
            }
        }
        return false;
    }

    /**
     * The system-dependent path-separator character. On UNIX systems,
     * this character is ':'; on Win32 systems it is ';'.
     * Fixme:
     * On Mac it cannot be : because this is the seperator there!
     *
     * @access private
     * @return string path_separator.
     */
    public function _get_ini_separator()
    {
        return isWindows() ? ';' : ':';
        // return preg_match('/^Windows/', php_uname())
    }

    /**
     * Get the value of PHP's include_path.
     *
     * @access private
     * @return array Include path.
     */
    public function _get_include_path()
    {
        if (defined("INCLUDE_PATH")) {
            $path = INCLUDE_PATH;
        } else {
            // Tuleap: never trust /etc/php.ini, always rely on environment.
            // $path = @get_cfg_var('include_path'); // FIXME: report warning
            // if (empty($path)) $path = @ini_get('include_path');
            $path = @ini_get('include_path');
        }
        if (empty($path)) {
            $path = '.';
        }
        return explode($this->_get_ini_separator(), $this->slashifyPath($path));
    }

    /**
     * Add a directory to the end of PHP's include_path.
     *
     * The directory is appended only if it is not already listed in
     * the include_path.
     *
     * @access private
     * @param $dir string Directory to add.
     */
    public function _append_to_include_path($dir)
    {
        $dir = $this->slashifyPath($dir);
        if (!in_array($dir, $this->_path)) {
            $this->_path[] = $dir;
        }
        /*
         * Some (buggy) PHP's (notable SourceForge's PHP 4.0.6)
         * sometimes don't seem to heed their include_path.
         * I.e. sometimes a file is not found even though it seems to
         * be in the current include_path. A simple
         * ini_set('include_path', ini_get('include_path')) seems to
         * be enough to fix the problem
         *
         * This following line should be in the above if-block, but we
         * put it here, as it seems to work-around the bug.
         */
        @ini_set('include_path', implode($this->_get_ini_separator(), $this->_path));
    }

    /**
     * Add a directory to the front of PHP's include_path.
     *
     * The directory is prepended, and removed from the tail if already existing.
     *
     * @access private
     * @param $dir string Directory to add.
     */
    public function _prepend_to_include_path($dir)
    {
        $dir = $this->slashifyPath($dir);
        // remove duplicates
        if ($i = array_search($dir, $this->_path) !== false) {
            array_splice($this->_path, $i, 1);
        }
        array_unshift($this->_path, $dir);
        @ini_set('include_path', implode($this->_path, $this->_get_ini_separator()));
    }

    // Return all the possible shortened locale specifiers for the given locale.
    // Most specific first.
    // de_DE.iso8859-1@euro => de_DE.iso8859-1, de_DE, de
    // This code might needed somewhere else also.
    public function locale_versions($lang)
    {
        // Try less specific versions of the locale
        $langs[] = $lang;
        foreach (array('@', '.', '_') as $sep) {
            if (($tail = strchr($lang, $sep))) {
                $langs[] = substr($lang, 0, -strlen($tail));
            }
        }
        return $langs;
    }

    /**
     * Try to figure out the appropriate value for $LANG.
     *
     *@access private
     *@return string The value of $LANG.
     */
    public function _get_lang()
    {
        if (!empty($GLOBALS['LANG'])) {
            return $GLOBALS['LANG'];
        }

        foreach (array('LC_ALL', 'LC_MESSAGES', 'LC_RESPONSES') as $var) {
            $lang = setlocale(constant($var), 0);
            if (!empty($lang)) {
                return $lang;
            }
        }

        foreach (array('LC_ALL', 'LC_MESSAGES', 'LC_RESPONSES', 'LANG') as $var) {
            $lang = getenv($var);
            if (!empty($lang)) {
                return $lang;
            }
        }

        return "C";
    }
}

/**
 * A class for finding PEAR code.
 *
 * This is a subclass of FileFinder which searches a standard list of
 * directories where PEAR code is likely to be installed.
 *
 * Example usage:
 *
 * <pre>
 *   $pearFinder = new PearFileFinder;
 *   $pearFinder->includeOnce('DB.php');
 * </pre>
 *
 * The above code will look for 'DB.php', if found, the directory in
 * which it was found will be added to PHP's include_path, and the
 * file will be included. (If the file is not found, and E_USER_ERROR
 * will be thrown.)
 */
class PearFileFinder extends FileFinder
{
    /**
     *
     *
     * @param $path array Where to look for PEAR library code.
     * A good set of defaults is provided, so you can probably leave
     * this parameter blank.
     */
    public function __construct($path = array())
    {
        parent::__construct(array_merge(
            $path,
            array('/usr/share/php4',
                                '/usr/share/php',
                                '/usr/lib/php4',
                                '/usr/lib/php',
                                '/usr/local/share/php4',
                                '/usr/local/share/php',
                                '/usr/local/lib/php4',
                                '/usr/local/lib/php',
                                '/System/Library/PHP',
                                '/Apache/pear'        // Windows
            )
        ));
    }
}

/**
 * Find PhpWiki localized files.
 *
 * This is a subclass of FileFinder which searches PHP's include_path
 * for files. It looks first for "locale/$LANG/$file", then for
 * "$file".
 *
 * If $LANG is something like "de_DE.iso8859-1@euro", this class will
 * also search under various less specific variations like
 * "de_DE.iso8859-1", "de_DE" and "de".
 */
class LocalizedFileFinder extends FileFinder
{
    public function __construct()
    {
        $this->_pathsep = $this->_get_syspath_separator();
        $include_path = $this->_get_include_path();
        $path = array();

        $lang = $this->_get_lang();
        assert(!empty($lang));

        if ($locales = $this->locale_versions($lang)) {
            foreach ($locales as $lang) {
                if ($lang == 'C') {
                    $lang = 'en';
                }
                foreach ($include_path as $dir) {
                    $path[] = $this->slashifyPath($dir . "/locale/$lang");
                }
            }
        }
        parent::__construct(array_merge($path, $include_path));
    }
}

/**
 * Find PhpWiki localized theme buttons.
 *
 * This is a subclass of FileFinder which searches PHP's include_path
 * for files. It looks first for "buttons/$LANG/$file", then for
 * "$file".
 *
 * If $LANG is something like "de_DE.iso8859-1@euro", this class will
 * also search under various less specific variations like
 * "de_DE.iso8859-1", "de_DE" and "de".
 */
class LocalizedButtonFinder extends FileFinder
{
    public function __construct()
    {
        global $WikiTheme;
        $this->_pathsep = $this->_get_syspath_separator();
        $include_path = $this->_get_include_path();
        $path = array();

        $lang = $this->_get_lang();
        assert(!empty($lang));
        assert(!empty($WikiTheme));

        if (is_object($WikiTheme)) {
            $langs = $this->locale_versions($lang);
            foreach ($langs as $lang) {
                if ($lang == 'C') {
                    $lang = 'en';
                }
                foreach ($include_path as $dir) {
                    $path[] = $this->slashifyPath($WikiTheme->file("buttons/$lang"));
                }
            }
        }

        parent::__construct(array_merge($path, $include_path));
    }
}

// Search PHP's include_path to find file or directory.
function FindFile($file, $missing_okay = false, $slashify = false)
{
    static $finder;
    if (!isset($finder)) {
        $finder = new FileFinder;
        // remove "/lib" from dirname(__FILE__)
        $wikidir = preg_replace('/.lib$/', '', dirname(__FILE__));
        // let the system favor its local pear?
        $finder->_append_to_include_path(dirname(__FILE__) . "/pear");
        $finder->_prepend_to_include_path($wikidir);
        // Don't override existing INCLUDE_PATH config.
        if (!defined("INCLUDE_PATH")) {
            define("INCLUDE_PATH", implode($finder->_get_ini_separator(), $finder->_path));
        }
    }
    $s = $finder->findFile($file, $missing_okay);
    if ($slashify) {
        $s = $finder->slashifyPath($s);
    }
    return $s;
}

// Search PHP's include_path to find file or directory.
// Searches for "locale/$LANG/$file", then for "$file".
function FindLocalizedFile($file, $missing_okay = false, $re_init = false)
{
    static $finder;
    if ($re_init or !isset($finder)) {
        $finder = new LocalizedFileFinder;
    }
    return $finder->findFile($file, $missing_okay);
}

function FindLocalizedButtonFile($file, $missing_okay = false, $re_init = false)
{
    static $buttonfinder;
    if ($re_init or !isset($buttonfinder)) {
        $buttonfinder = new LocalizedButtonFinder;
    }
    return $buttonfinder->findFile($file, $missing_okay);
}

/**
 * Prefixes with PHPWIKI_DIR and slashify.
 * For example to unify with
 *   require_once dirname(__FILE__).'/lib/file.php'
 *   require_once 'lib/file.php' loading style.
 * Doesn't expand "~" or symlinks yet. truename would be perfect.
 *
 * NormalizeLocalFileName("lib/config.php") => /home/user/phpwiki/lib/config.php
 */
function NormalizeLocalFileName($file)
{
    static $finder;
    if (!isset($finder)) {
        $finder = new FileFinder;
    }
    // remove "/lib" from dirname(__FILE__)
    if ($finder->_is_abs($file)) {
        return $finder->slashifyPath($file);
    } else {
        if (defined("PHPWIKI_DIR")) {
            $wikidir = PHPWIKI_DIR;
        } else {
            $wikidir = preg_replace('/.lib$/', '', dirname(__FILE__));
        }
        $wikidir = $finder->_strip_last_pathchar($wikidir);
        $pathsep = $finder->_use_path_separator($wikidir);
        return $finder->slashifyPath($wikidir . $pathsep . $file);
        // return PHPWIKI_DIR . "/" . $file;
    }
}

/**
 * Prefixes with DATA_PATH and slashify
 */
function NormalizeWebFileName($file)
{
    static $finder;
    if (!isset($finder)) {
        $finder = new FileFinder;
    }
    if (defined("DATA_PATH")) {
        $wikipath = DATA_PATH;
        $wikipath = $finder->_strip_last_pathchar($wikipath);
        if (!$file) {
            return $finder->forcePathSlashes($wikipath);
        } else {
            return $finder->forcePathSlashes($wikipath . '/' . $file);
        }
    } else {
        return $finder->forcePathSlashes($file);
    }
}

function isWindows()
{
    static $win;
    if (isset($win)) {
        return $win;
    }
    //return preg_match('/^Windows/', php_uname());
    $win = (substr(PHP_OS, 0, 3) == 'WIN');
    return $win;
}

function isWindows95()
{
    static $win95;
    if (isset($win95)) {
        return $win95;
    }
    $win95 = isWindows() and !isWindowsNT();
    return $win95;
}

function isWindowsNT()
{
    static $winnt;
    if (isset($winnt)) {
        return $winnt;
    }
    // FIXME: Do this using PHP_OS instead of php_uname().
    // $winnt = (PHP_OS == "WINNT"); // example from http://www.php.net/manual/en/ref.readline.php
    if (function_usable('php_uname')) {
        $winnt = preg_match('/^Windows NT/', php_uname());
    } else {
        $winnt = false;         // FIXME: punt.
    }
    return $winnt;
}

/**
 * This is for the OLD Macintosh OS, NOT MacOSX or Darwin!
 * This has really ugly pathname semantics.
 * ":path" is relative, "Desktop:path" (I think) is absolute.
 * FIXME: Please fix this someone. So far not supported.
 */
function isMac()
{
    return (substr(PHP_OS, 0, 9) == 'Macintosh'); // not tested!
}

// probably not needed, same behaviour as on unix.
function isCygwin()
{
    return (substr(PHP_OS, 0, 6) == 'CYGWIN');
}

// $Log: FileFinder.php,v $
// Revision 1.31  2005/02/28 21:24:32  rurban
// ignore forbidden ini_set warnings. Bug #1117254 by Xavier Roche
//
// Revision 1.30  2004/11/10 19:32:21  rurban
// * optimize increaseHitCount, esp. for mysql.
// * prepend dirs to the include_path (phpwiki_dir for faster searches)
// * Pear_DB version logic (awful but needed)
// * fix broken ADODB quote
// * _extract_page_data simplification
//
// Revision 1.29  2004/11/09 17:11:03  rurban
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
// Revision 1.28  2004/11/06 17:02:33  rurban
// Workaround some php-win \\ duplication bug
//
// Revision 1.27  2004/10/14 19:23:58  rurban
// remove debugging prints
//
// Revision 1.26  2004/10/14 19:19:33  rurban
// loadsave: check if the dumped file will be accessible from outside.
// and some other minor fixes. (cvsclient native not yet ready)
//
// Revision 1.25  2004/10/12 13:13:19  rurban
// php5 compatibility (5.0.1 ok)
//
// Revision 1.24  2004/08/05 17:33:51  rurban
// strange problem with WikiTheme
//
// Revision 1.23  2004/06/19 12:33:25  rurban
// prevent some warnings in corner cases
//
// Revision 1.22  2004/06/14 11:31:20  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.21  2004/06/02 18:01:45  rurban
// init global FileFinder to add proper include paths at startup
//   adds PHPWIKI_DIR if started from another dir, lib/pear also
// fix slashify for Windows
// fix USER_AUTH_POLICY=old, use only USER_AUTH_ORDER methods (besides HttpAuth)
//
// Revision 1.20  2004/05/27 17:49:05  rurban
// renamed DB_Session to DbSession (in CVS also)
// added WikiDB->getParam and WikiDB->getAuthParam method to get rid of globals
// remove leading slash in error message
// added force_unlock parameter to File_Passwd (no return on stale locks)
// fixed adodb session AffectedRows
// added FileFinder helpers to unify local filenames and DATA_PATH names
// editpage.php: new edit toolbar javascript on ENABLE_EDIT_TOOLBAR
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
