<?php
rcs_id('$Id$');

/**
 * A configurator intended to read it's config from a PHP-style INI file,
 * instead of a PHP file.
 *
 * Pass a filename to the IniConfig() function and it will read all it's
 * definitions from there, all by itself, and proceed to do a mass-define
 * of all valid PHPWiki config items.  In this way, we can hopefully be
 * totally backwards-compatible with the old index.php method, while still
 * providing a much tastier on-going experience.
 *
 * @author: Joby Walker, Reini Urban, Matthew Palmer
 */
/*
 * Copyright 2004 $ThePhpWikiProgrammingTeam
 *
 * This file is part of PhpWiki.
 *
 * PhpWiki is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * PhpWiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with PhpWiki; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/** TODO
 * - Convert the value lists to provide defaults, so that every "if
 *      (defined())" and "if (!defined())" can fuck off to the dismal hole
 *      it belongs in.
 *
 * - Resurrect the larger "config object" code (in config/) so it'll aid the
 *      GUI config writers, and allow us to do proper validation and default
 *      value handling.
 *
 * - Get rid of WikiNameRegexp and KeywordLinkRegexp as globals by finding
 *      everywhere that uses them as variables and modify the code to use
 *      them as constants.  Will involve hacking around
 *      pcre_fix_posix_classes (probably with redefines()).
 */

include_once (dirname(__FILE__)."/config.php");
include_once (dirname(__FILE__)."/FileFinder.php");

function IniConfig($file) {
    
    //FindFile("pear/Config.php");
    //require_once("Config.php");
 
    // List of all valid config options to be define()d which take "values" (not
    // booleans). Needs to be categorised, and generally made a lot tidier. 
   $_IC_VALID_VALUE = array
        ('DEBUG', 'WIKI_NAME', 'ADMIN_USER', 'ADMIN_PASSWD',
         'HTML_DUMP_SUFFIX', 'MAX_UPLOAD_SIZE', 'MINOR_EDIT_TIMEOUT',
         'ACCESS_LOG', 'CACHE_CONTROL', 'CACHE_CONTROL_MAX_AGE',
         'PASSWORD_LENGTH_MINIMUM', 'USER_AUTH_POLICY', 'LDAP_AUTH_HOST',
         'LDAP_BASE_DN', 'LDAP_AUTH_USER', 'LDAP_AUTH_PASSWORD',
         'LDAP_SEARCH_FIELD', 'IMAP_AUTH_HOST', 'POP3_AUTH_HOST',
         'AUTH_USER_FILE', 'AUTH_SESS_USER', 'AUTH_SESS_LEVEL', 'GROUP_METHOD',
         'AUTH_GROUP_FILE', 'EDITING_POLICY', 'THEME', 'CHARSET',
         'DEFAULT_LANGUAGE', 'WIKI_PGSRC', 'DEFAULT_WIKI_PGSRC',
         'ALLOWED_PROTOCOLS', 'INLINE_IMAGES', 'SUBPAGE_SEPARATOR',
         'INTERWIKI_MAP_FILE', 'COPYRIGHTPAGE_TITLE', 'COPYRIGHTPAGE_URL',
         'AUTHORPAGE_TITLE', 'AUTHORPAGE_URL', 'SERVER_NAME', 'SERVER_PORT',
         'SCRIPT_NAME', 'DATA_PATH', 'PHPWIKI_DIR', 'VIRTUAL_PATH',
         'WIKI_NAME_REGEXP');

    // List of all valid config options to be define()d which take booleans.
    $_IC_VALID_BOOL = array
        ('ENABLE_USER_NEW', 'ENABLE_EDIT_TOOLBAR', 'JS_SEARCHREPLACE',
         'ENABLE_REVERSE_DNS', 'ENCRYPTED_PASSWD', 'ZIPDUMP_AUTH', 
         'ENABLE_RAW_HTML', 'STRICT_MAILABLE_PAGEDUMPS', 'COMPRESS_OUTPUT',
         'WIKIDB_NOCACHE_MARKUP', 'ALLOW_ANON_USER', 'ALLOW_ANON_EDIT',
         'ALLOW_BOGO_LOGIN', 'ALLOW_USER_PASSWORDS',
         'AUTH_USER_FILE_STORABLE', 'ALLOW_HTTP_AUTH_LOGIN',
         'ALLOW_USER_LOGIN', 'ALLOW_LDAP_LOGIN', 'ALLOW_IMAP_LOGIN',
         'WARN_NONPUBLIC_INTERWIKIMAP', 'USE_PATH_INFO',
         'DISABLE_HTTP_REDIRECT');

    if(!file_exists($file)){
        trigger_error("Datasource file '$file' does not exist", E_USER_ERROR);
        exit();
    }
         
    $rs = @parse_ini_file($file);
    $rsdef = @parse_ini_file(dirname(__FILE__)."/../config/config-default.ini");

    foreach ($_IC_VALID_VALUE as $item) {
        if (defined($item)) continue;
        if (array_key_exists($item, $rs)) {
            define($item, $rs[$item]);
        } elseif (array_key_exists($item, $rsdef)) {
            define($item, $rsdef[$item]);
        // calculate them later:
        } elseif (in_array($item,array('DATABASE_PREFIX', 'SERVER_NAME', 'SERVER_PORT',
         	'SCRIPT_NAME', 'DATA_PATH', 'PHPWIKI_DIR', 'VIRTUAL_PATH'))) {
            ;
        } else {
            trigger_error(sprintf("missing config setting for %s",$item));
        }
    }

    // Boolean options are slightly special - if they're set to any of
    // '', 'false', '0', or 'no' (all case-insensitive) then the value will
    // be a boolean false, otherwise if there is anything set it'll
    // be true.
    foreach ($_IC_VALID_BOOL as $item) {
        if (defined($item)) continue;
        if (array_key_exists($item, $rs)) {
            $val = $rs[$item];
        } elseif (array_key_exists($item, $rsdef)) {
            $val = $rsdef[$item];
        } else {
            $val = false; //trigger_error(sprintf("missing boolean config setting for %s",$item));
        }
        
        // calculate them later: old or dynamic constants
        if (!array_key_exists($item, $rs) and
            in_array($item,array('USE_PATH_INFO','USE_DB_SESSION',
                                 'ALLOW_HTTP_AUTH_LOGIN','ALLOW_LDAP_LOGIN',
                                 'ALLOW_IMAP_LOGIN','ALLOW_USER_LOGIN',
                                 'REQUIRE_SIGNIN_BEFORE_EDIT',
                                 'WIKIDB_NOCACHE_MARKUP')))
        {
            ;
        }
        elseif (!$val) {
            define($item, false);
        }
        elseif (strtolower($val) == 'false' ||
                strtolower($val) == 'no' ||
                $val == '0') {
            define($item, false);
        }
        else {
            define($item, true);
        }
    }

    // Special handling for some config options
    if ($val = @$rs['INCLUDE_PATH']) {
        ini_set('include_path', $val);
    }

    if ($val = @$rs['SESSION_SAVE_PATH']) {
        ini_set('session.save_path', $val);
    }

    // Database
    global $DBParams;
    $DBParams['dbtype'] = @$rs['DATABASE_TYPE'];
    if (isset($rs['DATABASE_DSN']))
        $DBParams['dsn'] = $rs['DATABASE_DSN'];
    if (defined('DATABASE_DSN')) 
        $DBParams['dsn'] = DATABASE_DSN;
    if (isset($rs['DATABASE_PREFIX']))
        $DBParams['prefix'] = $rs['DATABASE_PREFIX'];
    if (defined('DATABASE_PREFIX')) 
        $DBParams['prefix'] = DATABASE_PREFIX;

    $DBParams['db_session_table'] = @$rs['DATABASE_SESSION_TABLE'];
    $DBParams['dba_handler'] = @$rs['DATABASE_DBA_HANDLER'];
    $DBParams['directory'] = @$rs['DATABASE_DIRECTORY'];
    $DBParams['timeout'] = @$rs['DATABASE_TIMEOUT'];
    if (!defined('USE_DB_SESSION') and $DBParams['db_session_table'] and 
        in_array($DBParams['dbtype'],array('SQL','ADODB','dba'))) {
        define('USE_DB_SESSION', true);
    }

    // Expiry stuff
    global $ExpireParams;

    $ExpireParams['major'] = array(
                                   'max_age' => @$rs['MAJOR_MAX_AGE'],
                                   'min_age' => @$rs['MAJOR_MIN_AGE'],
                                   'min_keep' => @$rs['MAJOR_MIN_KEEP'],
                                   'keep' => @$rs['MAJOR_KEEP'],
                                   'max_keep' => @$rs['MAJOR_MAX_KEEP']
                                   );
    $ExpireParams['minor'] = array(
                                   'max_age' => @$rs['MINOR_MAX_AGE'],
                                   'min_age' => @$rs['MINOR_MIN_AGE'],
                                   'min_keep' => @$rs['MINOR_MIN_KEEP'],
                                   'keep' => @$rs['MINOR_KEEP'],
                                   'max_keep' => @$rs['MINOR_MAX_KEEP']
                                   );
    $ExpireParams['author'] = array(
                                    'max_age' => @$rs['AUTHOR_MAX_AGE'],
                                    'min_age' => @$rs['AUTHOR_MIN_AGE'],
                                    'min_keep' => @$rs['AUTHOR_MIN_KEEP'],
                                    'keep' => @$rs['AUTHOR_KEEP'],
                                    'max_keep' => @$rs['AUTHOR_MAX_KEEP']
                                    );

    // User authentication
    global $USER_AUTH_ORDER;
    $USER_AUTH_ORDER = preg_split('/\s*:\s*/', @$rs['USER_AUTH_ORDER']);

    // LDAP bind options
    global $LDAP_SET_OPTION;
    $optlist = preg_split('/\s*:\s*/', @$rs['LDAP_SET_OPTION']);
    foreach ($optlist as $opt) {
        $bits = preg_split('/\s*=\s*/', $opt, 2);
        if (count($bits) == 2) {
            $LDAP_SET_OPTION[$bits[0]] = $bits[1];
        }
        else {
            // Possibly throw some sort of error?
        }
    }

    // Now it's the external DB authentication stuff's turn
    if (in_array('Db', $USER_AUTH_ORDER) && empty($rs['DBAUTH_AUTH_DSN'])) {
        $rs['DBAUTH_AUTH_DSN'] = $DBParams['dsn'];
    }
    
    global $DBAuthParams;
    $DBAP_MAP = array('DBAUTH_AUTH_DSN' => 'auth_dsn',
                      'DBAUTH_AUTH_CHECK' => 'auth_check',
                      'DBAUTH_AUTH_USER_EXISTS' => 'auth_user_exists',
                      'DBAUTH_AUTH_CRYPT_METHOD' => 'auth_crypt_method',
                      'DBAUTH_AUTH_UPDATE' => 'auth_update',
                      'DBAUTH_AUTH_CREATE' => 'auth_create',
                      'DBAUTH_PREF_SELECT' => 'pref_select',
                      'DBAUTH_PREF_UPDATE' => 'pref_update',
                      'DBAUTH_IS_MEMBER' => 'is_member',
                      'DBAUTH_GROUP_MEMBERS' => 'group_members',
                      'DBAUTH_USER_GROUPS' => 'user_groups'
                      );

    foreach ($DBAP_MAP as $rskey => $apkey) {
        if (isset($rs[$rskey])) {
            $DBAuthParams[$apkey] = $rs[$rskey];
        }
    }

    // Default Wiki pages to force loading from pgsrc
    global $GenericPages;
    $GenericPages = preg_split('/\s*:\s*/', @$rs['DEFAULT_WIKI_PAGES']);

    // Wiki name regexp:  Should be a define(), but might needed to be changed at runtime
    // (different LC_CHAR need different posix classes)
    global $WikiNameRegexp;
    $WikiNameRegexp = constant('WIKI_NAME_REGEXP');
    if (!trim($WikiNameRegexp))
       $WikiNameRegexp = '(?<![[:alnum:]])(?:[[:upper:]][[:lower:]]+){2,}(?![[:alnum:]])';

    // Another "too-tricky" redefine
    global $KeywordLinkRegexp;
    if (!isset($rs['KEYWORDS'])) $rs['KEYWORDS'] = "Category:Topic";
    $keywords = preg_split('/\s*:\s*/', $rs['KEYWORDS']);
    if (empty($keywords)) $keywords = array("Category","Topic");
    $KeywordLinkRegexp = '(?<=' . implode('|^', $keywords) . ')[[:upper:]].*$';
        
    global $DisabledActions;
    $DisabledActions = preg_split('/\s*:\s*/', @$rs['DISABLED_ACTIONS']);
    
    /*global $AllowedProtocols, $InlineImages;
    $AllowedProtocols = constant("ALLOWED_PROTOCOLS");
    $InlineImages = constant("INLINE_IMAGES");*/

    fix_configs();
}

// moved from lib/config.php
function fix_configs() {
    global $FieldSeparator, $charset, $WikiNameRegexp, $KeywordLinkRegexp, $AllActionPages;
    global $DisabledActions, $HTTP_SERVER_VARS, $DBParams, $LANG;

    // "\x80"-"\x9f" (and "\x00" - "\x1f") are non-printing control
    // chars in iso-8859-*
    // $FieldSeparator = "\263"; // this is a superscript 3 in ISO-8859-1.
    // $FieldSeparator = "\xFF"; // this byte should never appear in utf-8
    // FIXME: get rid of constant. pref is dynamic and language specific
    $charset = CHARSET;
    if (isset($LANG) and in_array($LANG,array('ja','zh')))
        $charset = 'utf-8';
    if (strtolower($charset) == 'utf-8')
        $FieldSeparator = "\xFF";
    else
        $FieldSeparator = "\x81";

    if (!defined('DEFAULT_LANGUAGE'))
        define('DEFAULT_LANGUAGE', 'en');
    update_locale(isset($LANG) ? $LANG : DEFAULT_LANGUAGE);

    // Set up (possibly fake) gettext()
    //
    if (!function_exists ('bindtextdomain')) {
        $locale = array();

        function gettext ($text) { 
            global $locale;
            if (!empty ($locale[$text]))
                return $locale[$text];
            return $text;
        }

        function _ ($text) {
            return gettext($text);
        }
    }
    else {
        // Working around really weird gettext problems: (4.3.2, 4.3.6 win)
        // bindtextdomain() returns the current domain path.
        // 1. If the script is not index.php but something like "de", on a different path
        //    then bindtextdomain() fails, but after chdir to the correct path it will work okay.
        // 2. But the weird error "Undefined variable: bindtextdomain" is generated then.
        $bindtextdomain_path = FindFile("locale", false, true);
        if (isWindows())
            $bindtextdomain_path = str_replace("/","\\",$bindtextdomain_path);
        $bindtextdomain_real = @bindtextdomain("phpwiki", $bindtextdomain);
        if ($bindtextdomain_real != $bindtextdomain_path) {
            // this will happen with virtual_paths. chdir and try again.
            chdir($bindtextdomain_path);
            $bindtextdomain_real = @bindtextdomain("phpwiki", $bindtextdomain);
        }
        textdomain("phpwiki");
        if ($bindtextdomain_real != $bindtextdomain_path) { // change back
            chdir($bindtextdomain_real . (isWindows() ? "\\.." : "/.."));
        }
    }

    $WikiNameRegexp = pcre_fix_posix_classes($WikiNameRegexp);
    $KeywordLinkRegexp = pcre_fix_posix_classes($KeywordLinkRegexp);

    $AllActionPages = explode(':','AllPages:BackLinks:DebugInfo:FindPage:FullRecentChanges:'
                              .'FullTextSearch:FuzzyPages:InterWikiSearch:LikePages:MostPopular:'
                              .'OrphanedPages:PageDump:PageHistory:PageInfo:RandomPage:'
                              .'RecentChanges:RecentEdits:RelatedChanges:TitleSearch:'
                              .'UserPreferences:WantedPages:UpLoad');

    //////////////////////////////////////////////////////////////////
    // Autodetect URL settings:
    //
    if (!defined('SERVER_NAME')) define('SERVER_NAME', $HTTP_SERVER_VARS['SERVER_NAME']);
    if (!defined('SERVER_PORT')) define('SERVER_PORT', $HTTP_SERVER_VARS['SERVER_PORT']);
    if (!defined('SERVER_PROTOCOL')) {
        if (empty($HTTP_SERVER_VARS['HTTPS']) || $HTTP_SERVER_VARS['HTTPS'] == 'off')
            define('SERVER_PROTOCOL', 'http');
        else
            define('SERVER_PROTOCOL', 'https');
    }

    if (!defined('SCRIPT_NAME'))
        define('SCRIPT_NAME', deduce_script_name());

    if (!defined('USE_PATH_INFO')) {
            /*
             * If SCRIPT_NAME does not look like php source file,
             * or user cgi we assume that php is getting run by an
             * action handler in /cgi-bin.  In this case,
             * I think there is no way to get Apache to pass
             * useful PATH_INFO to the php script (PATH_INFO
             * is used to the the php interpreter where the
             * php script is...)
             */
            switch (php_sapi_name()) {
            case 'apache':
            case 'apache2handler':
                define('USE_PATH_INFO', true);
                break;
            case 'cgi':
            case 'apache2filter':
                define('USE_PATH_INFO', false);
                break;
            default:
                define('USE_PATH_INFO', ereg('\.(php3?|cgi)$', SCRIPT_NAME));
                break;
            }
        }
     
    // If user has not defined PHPWIKI_DIR, and we need it
    if (!defined('PHPWIKI_DIR') and !file_exists("themes/default")) {
    	$themes_dir = FindFile("themes");
        define('PHPWIKI_DIR', dirname($themes_dir));
    }
        
    if (!defined('VIRTUAL_PATH')) {
        // We'd like to auto-detect when the cases where apaches
        // 'Action' directive (or similar means) is used to
        // redirect page requests to a cgi-handler.
        //
        // In cases like this, requests for e.g. /wiki/HomePage
        // get redirected to a cgi-script called, say,
        // /path/to/wiki/index.php.  The script gets all
        // of /wiki/HomePage as it's PATH_INFO.
        //
        // The problem is:
        //   How to detect when this has happened reliably?
        //   How to pick out the "virtual path" (in this case '/wiki')?
        //
        // (Another time an redirect might occur is to a DirectoryIndex
        // -- the requested URI is '/wikidir/', the request gets
        // passed to '/wikidir/index.php'.  In this case, the
        // proper VIRTUAL_PATH is '/wikidir/index.php', since the
        // pages will appear at e.g. '/wikidir/index.php/HomePage'.
        //

        $REDIRECT_URL = &$HTTP_SERVER_VARS['REDIRECT_URL'];
        if (USE_PATH_INFO and isset($REDIRECT_URL)
            and ! IsProbablyRedirectToIndex()) {
            // FIXME: This is a hack, and won't work if the requested
            // pagename has a slash in it.
            $temp = strtr(dirname($REDIRECT_URL . 'x'),"\\",'/');
            if ( ($temp == '/') || ($temp == '\\') )
                $temp = '';
            define('VIRTUAL_PATH', $temp);
        } else {
            define('VIRTUAL_PATH', SCRIPT_NAME);
        }
    }

    // If user has not defined DATA_PATH, we want to use relative URLs.
    if (!defined('DATA_PATH')) {
        // fix similar to the one suggested by jkalmbach for 
        // installations in the webrootdir, like "http://phpwiki.org/HomePage"
        $temp = dirname(SCRIPT_NAME);
        if ( ($temp == '/') || ($temp == '\\') )
            $temp = '';
        define('DATA_PATH', $temp);
        /*
        if (USE_PATH_INFO)
            define('DATA_PATH', '..');
        */
    }

    if (SERVER_PORT
        && SERVER_PORT != (SERVER_PROTOCOL == 'https' ? 443 : 80)) {
        define('SERVER_URL',
               SERVER_PROTOCOL . '://' . SERVER_NAME . ':' . SERVER_PORT);
    }
    else {
        define('SERVER_URL',
               SERVER_PROTOCOL . '://' . SERVER_NAME);
    }

    if (VIRTUAL_PATH != SCRIPT_NAME) {
        // Apache action handlers are used.
        define('PATH_INFO_PREFIX', VIRTUAL_PATH . '/');
    }
    else
        define('PATH_INFO_PREFIX', '/');

    define('PHPWIKI_BASE_URL',
           SERVER_URL . (USE_PATH_INFO ? VIRTUAL_PATH . '/' : SCRIPT_NAME));

    // Detect PrettyWiki setup (not loading index.php directly)
    // $SCRIPT_FILENAME should be the same as __FILE__ in index.php
    if (!isset($SCRIPT_FILENAME))
        $SCRIPT_FILENAME = @$HTTP_SERVER_VARS['SCRIPT_FILENAME'];
    if (!isset($SCRIPT_FILENAME))
        $SCRIPT_FILENAME = @$HTTP_ENV_VARS['SCRIPT_FILENAME'];
    if (!isset($SCRIPT_FILENAME))
        $SCRIPT_FILENAME = dirname(__FILE__.'/../') . '/index.php';
    if (isWindows())
        $SCRIPT_FILENAME = strtr($SCRIPT_FILENAME,'/','\\');
    define('SCRIPT_FILENAME',$SCRIPT_FILENAME);

    //////////////////////////////////////////////////////////////////
    // Select database
    //
    if (empty($DBParams['dbtype']))
        $DBParams['dbtype'] = 'dba';

    if (!defined('THEME'))
        define('THEME', 'default');

    if (!defined('WIKI_NAME'))
        define('WIKI_NAME', _("An unnamed PhpWiki"));

    if (!defined('HOME_PAGE'))
        define('HOME_PAGE', _("HomePage"));

    // FIXME: delete
    // Access log
    if (!defined('ACCESS_LOG'))
        define('ACCESS_LOG', '');

    // FIXME: delete
    // Get remote host name, if apache hasn't done it for us
    if (empty($HTTP_SERVER_VARS['REMOTE_HOST']) && ENABLE_REVERSE_DNS)
        $HTTP_SERVER_VARS['REMOTE_HOST'] = gethostbyaddr($HTTP_SERVER_VARS['REMOTE_ADDR']);

    // check whether the crypt() function is needed and present
    if (defined('ENCRYPTED_PASSWD') && !function_exists('crypt')) {
        $error = sprintf(_("Encrypted passwords cannot be used: %s."),
                         "'function crypt()' not available in this version of php");
        trigger_error($error);
    }

    if (!defined('ADMIN_PASSWD') or ADMIN_PASSWD == '')
        trigger_error(_("The admin password cannot be empty. Please update your /index.php"));

    if (defined('USE_DB_SESSION') and USE_DB_SESSION) {
        if (! $DBParams['db_session_table'] ) {
            trigger_error(_("Empty db_session_table. Turn USE_DB_SESSION off or define the table name."), 
                          E_USER_ERROR);
            // this is flawed. constants cannot be changed.
            define('USE_DB_SESSION',false);
            $DBParams['db_session_table'] = @$DBParams['prefix'] . 'session';
        }
    } else {
        // default: true (since v1.3.8)
        if (!defined('USE_DB_SESSION'))
            define('USE_DB_SESSION',true);
    }
    // legacy:
    if (!defined('ENABLE_USER_NEW')) define('ENABLE_USER_NEW',true);
    if (!defined('ALLOW_USER_LOGIN'))
        define('ALLOW_USER_LOGIN', defined('ALLOW_USER_PASSWORDS') && ALLOW_USER_PASSWORDS);
    if (!defined('ALLOW_ANON_USER')) define('ALLOW_ANON_USER', true); 
    if (!defined('ALLOW_ANON_EDIT')) define('ALLOW_ANON_EDIT', false); 
    if (!defined('REQUIRE_SIGNIN_BEFORE_EDIT')) define('REQUIRE_SIGNIN_BEFORE_EDIT', ! ALLOW_ANON_EDIT);
    if (!defined('ALLOW_BOGO_LOGIN')) define('ALLOW_BOGO_LOGIN', true);

    if (ALLOW_USER_LOGIN and !empty($DBAuthParams) and empty($DBAuthParams['auth_dsn'])) {
        if (isset($DBParams['dsn']))
            $DBAuthParams['auth_dsn'] = $DBParams['dsn'];
    }
}

// $Log$
// Revision 1.1  2005/04/12 13:33:28  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.21  2004/05/08 22:55:12  rurban
// Fixed longstanding sf.net:demo problem. endless loop, caused by an empty definition of
// WIKI_NAME_REGEXP. Exactly this constant wasn't checked for its default setting.
//
// Revision 1.20  2004/05/08 20:21:00  rurban
// remove php tags in Log
//
// Revision 1.19  2004/05/08 19:55:29  rurban
// support <span>inlined plugin-result</span>:
//   if the plugin is parsed inside a line, use <span> instead of
//   <div tightenable top bottom>
//   e.g. for "This is the current Phpwiki <plugin SystemInfo version> version.
//
// Revision 1.18  2004/05/08 16:58:19  rurban
// don't ignore some false config values (e.g. USE_PATH_INFO false was ignored)
//
// Revision 1.17  2004/05/06 19:26:15  rurban
// improve stability, trying to find the InlineParser endless loop on sf.net
//
// remove end-of-zip comments to fix sf.net bug #777278 and probably #859628
//
// Revision 1.16  2004/05/02 15:10:05  rurban
// new finally reliable way to detect if /index.php is called directly
//   and if to include lib/main.php
// new global AllActionPages
// SetupWiki now loads all mandatory pages: HOME_PAGE, action pages, and warns if not.
// WikiTranslation what=buttons for Carsten to create the missing MacOSX buttons
// PageGroupTestOne => subpages
// renamed PhpWikiRss to PhpWikiRecentChanges
// more docs, default configs, ...
//
// Revision 1.15  2004/05/01 15:59:29  rurban
// more php-4.0.6 compatibility: superglobals
//
// Revision 1.14  2004/04/29 23:25:12  rurban
// re-ordered locale init (as in 1.3.9)
// fixed loadfile with subpages, and merge/restore anyway
//   (sf.net bug #844188)
//
// Revision 1.13  2004/04/29 21:54:05  rurban
// typo
//
// Revision 1.12  2004/04/27 16:16:27  rurban
// more subtle config problems with defaults
//
// Revision 1.11  2004/04/26 20:44:34  rurban
// locking table specific for better databases
//
// Revision 1.10  2004/04/26 13:22:32  rurban
// calculate bool old or dynamic constants later
//
// Revision 1.9  2004/04/26 12:15:01  rurban
// check default config values
//
// Revision 1.8  2004/04/23 16:55:59  zorloc
// If using Db auth and DBAUTH_AUTH_DSN is empty set DBAUTH_AUTH_DSN to $DBParams['dsn']
//
// Revision 1.7  2004/04/20 22:26:27  zorloc
// Removed Pear_Config for parse_ini_file().
//
// Revision 1.6  2004/04/20 18:10:27  rurban
// config refactoring:
//   FileFinder is needed for WikiFarm scripts calling index.php
//   config run-time calls moved to lib/IniConfig.php:fix_configs()
//   added PHPWIKI_DIR smart-detection code (Theme finder)
//   moved FileFind to lib/FileFinder.php
//   cleaned lib/config.php
//
// Revision 1.5  2004/04/20 17:21:57  rurban
// WikiFarm code: honor predefined constants
//
// Revision 1.4  2004/04/20 17:08:19  rurban
// Some IniConfig fixes: prepend our private lib/pear dir
//   switch from " to ' in the auth statements
//   use error handling.
// WikiUserNew changes for the new "'$variable'" syntax
//   in the statements
// TODO: optimization to put config vars into the session.
//

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>