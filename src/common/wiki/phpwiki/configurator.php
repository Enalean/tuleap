<?php // $Id: configurator.php,v 1.40 2005/09/18 12:06:41 rurban Exp $
/*
 * Copyright 2002,2003,2005 $ThePhpWikiProgrammingTeam
 * Copyright 2002 Martin Geisler <gimpster@gimpster.com> 
 *
 * This file is part of PhpWiki.
 * Parts of this file were based on PHPWeather's configurator.php file.
 *   http://sourceforge.net/projects/phpweather/
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

/**
 * Starts automatically the first time by IniConfig("config/config.ini") 
 * if it doesn't exist.
 *
 * DONE:
 * o Initial expand ?show=_part1 (the part id)
 * o read config-default.ini and use this as default_values
 * o commented / optional: non-default values should not be commented!
 *                         default values if optional can be omitted.
 * o validate input (fix javascript, add POST checks)
 * o start this automatically the first time
 * o fix include_path
 *
 * 1.3.11 TODO: (or 1.3.12?)
 * o parse_ini_file("config-dist.ini") for the commented vars
 * o check automatically for commented and optional vars
 * o fix _optional, to ignore existing config.ini and only use config-default.ini values
 * o mixin class for commented 
 * o fix SQL quotes, AUTH_ORDER quotes and file forward slashes
 * o posted values validation, extend js validation for sane DB values
 * o read config-dist.ini into sections, comments, and optional/required settings
 *
 * A file config/config.ini will be automatically generated, if writable.
 *
 * NOTE: If you have a starterscript outside PHPWIKI_DIR but no 
 * config/config.ini yet (very unlikely!), you must define DATA_PATH in the 
 * starterscript, otherwise the webpath to configurator is unknown, and 
 * subsequent requests will fail. (POST to save the INI)
 */

global $tdwidth;

if (empty($configurator))
    $configurator = "configurator.php";
if (!strstr($_SERVER["SCRIPT_NAME"], $configurator) and defined('DATA_PATH'))
    $configurator = DATA_PATH . "/" . $configurator;
$scriptname = str_replace('configurator.php', 'index.php', $_SERVER["SCRIPT_NAME"]);

$tdwidth = 700;
$config_file = (substr(PHP_OS,0,3) == 'WIN') ? 'config\\config.ini' : 'config/config.ini';
$fs_config_file = dirname(__FILE__) . (substr(PHP_OS,0,3) == 'WIN' ? '\\' : '/') . $config_file;
if (isset($_POST['create']))  header('Location: '.$configurator.'?show=_part1&create=1#create');

// helpers from lib/WikiUser/HttpAuth.php
if (!function_exists('_http_user')) {
    function _http_user() {

	if (!empty($_SERVER['PHP_AUTH_USER']))
	    return array($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
	if (!empty($_SERVER['REMOTE_USER']))
	    return array($_SERVER['REMOTE_USER'], $_SERVER['PHP_AUTH_PW']);
        if (!empty($_ENV['REMOTE_USER']))
	    return array($_ENV['REMOTE_USER'],
            $_ENV['PHP_AUTH_PW']);
	if (!empty($GLOBALS['REMOTE_USER']))
	    return array($GLOBALS['REMOTE_USER'], $GLOBALS['PHP_AUTH_PW']);
	    
	// MsWindows IIS:
	if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            list($userid, $passwd) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            return array($userid, $passwd);
	}
	return array('','');
    }
    function _http_logout() {
        // maybe we should random the realm to really force a logout. but the next login will fail.
        // better_srand(); $realm = microtime().rand();
        header('WWW-Authenticate: Basic realm="'.WIKI_NAME.'"');
        if (strstr(php_sapi_name(), 'apache'))
            header('HTTP/1.0 401 Unauthorized'); 
        else    
            header("Status: 401 Access Denied"); //IIS and CGI need that
        unset($GLOBALS['REMOTE_USER']);
        unset($_SERVER['PHP_AUTH_USER']);
        unset($_SERVER['PHP_AUTH_PW']);

	trigger_error("Permission denied. Require ADMIN_USER.", E_USER_ERROR);
	exit();
    }
}

// If config.ini exists, we require ADMIN_USER access by faking HttpAuth. 
// So nobody can see or reset the password(s).
if (file_exists($fs_config_file)) {
    // Require admin user
    if (!defined('ADMIN_USER') or !defined('ADMIN_PASSWD')) {
    	if (!function_exists("IniConfig")) {
    	    include_once("lib/prepend.php");
	    include_once("lib/IniConfig.php");
    	}
	IniConfig($fs_config_file);
    }
    if (!defined('ADMIN_USER') or ADMIN_USER == '') {
	trigger_error("Configuration problem:\nADMIN_USER not defined in \"$fs_config_file\".\n"
		      . "Cannot continue: You have to fix that manually.", E_USER_ERROR);
	exit();
    }

    list($admin_user, $admin_pw) = _http_user();
    //$required_user = ADMIN_USER;
    if (empty($admin_user) or $admin_user != ADMIN_USER)
    {
	_http_logout();
    }
    // check password
    if (ENCRYPTED_PASSWD and function_exists('crypt')) {
        if (crypt($admin_pw, ADMIN_PASSWD) != ADMIN_PASSWD) 
	    _http_logout();
    } elseif ($admin_pw != ADMIN_PASSWD) {
        _http_logout();
    }
} else {
    if (!function_exists("IniConfig")) {
        include_once("lib/prepend.php");
	include_once("lib/IniConfig.php");
    }
    $def_file = (substr(PHP_OS,0,3) == 'WIN') ? 'config\\config-default.ini' : 'config/config-default.ini';
    $fs_def_file = dirname(__FILE__) . (substr(PHP_OS,0,3) == 'WIN' ? '\\' : '/') . $def_file;
    IniConfig($fs_def_file);
}

echo '<','?xml version="1.0" encoding="iso-8859-1"?',">\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!-- $Id: configurator.php,v 1.40 2005/09/18 12:06:41 rurban Exp $ -->
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Configuration tool for PhpWiki <?php echo $config_file ?></title>
<style type="text/css" media="screen">
<!--
/* TABLE { border: thin solid black } */
body { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 80%; }
pre { font-size: 120%; }
td { border: thin solid black }
tr { border: none }
div.hint { border: thin solid red, background-color: #eeeeee; }
tr.hidden { border: none; display: none; }
td.part { background-color: #eeeeee; color: inherit; }
td.instructions { background-color: #ffffee; width: <?php echo $tdwidth ?>px; color: inherit; }
td.unchangeable-variable-top   { border-bottom: none; background-color: #ffffee; color:inherit; }
td.unchangeable-variable-left  { border-top: none; background-color: #ffffee; color:inherit; }
-->
</style>
<script language="JavaScript" type="text/javascript">
<!--
function update(accepted, error, value, output) {
  var msg = document.getElementById(output);
  if (accepted) {
    /* MSIE 5.0 fails here */
    if (msg && msg.innerHTML) { msg.innerHTML = "<font color=\"green\">Input accepted.</font>"; }
  } else {
    while ((index = error.indexOf("%s")) > -1) {
      error = error.substring(0, index) + value + error.substring(index+2);
    }
    if (msg) { msg.innerHTML = "<font color=\"red\">" + error + "</font>"; }
  }
  if (submit = document.getElementById('submit')) submit.disabled = accepted ? false : true;
}

function validate(error, value, output, field) {
  update(field.value == value, error, field.value, output);
}

function validate_ereg(error, ereg, output, field) {
  regex = new RegExp(ereg);
  update(regex.test(field.value), error, field.value, output);
}

function validate_range(error, low, high, empty_ok, output, field) {
  update((empty_ok == 1 && field.value == "") ||
         (field.value >= low && field.value <= high),
         error, field.value, output);
}

function toggle_group(id) {
  var text = document.getElementById(id + "_text");
  var do_hide = false;
  if (text.innerHTML == "Hide options.") {
    do_hide = true;
    text.innerHTML = "Show options.";
  } else {
    text.innerHTML = "Hide options.";
  }

  var rows = document.getElementsByTagName('tr');
  var i = 0;
  for (i = 0; i < rows.length; i++) {
    var tr = rows[i];
    if (tr.className == 'header' && tr.id == id) {
      i++;
      break;
    }
  }
  for (; i < rows.length; i++) {
    var tr = rows[i];
    if (tr.className == 'header')
      break;
    tr.className = do_hide ? 'hidden': 'nonhidden';
  }
}

function do_init() {
  // Hide all groups.  We do this via JavaScript to avoid
  // hiding the groups if JavaScript is not supported...
  var rows = document.getElementsByTagName('tr');
  var show = '<?php echo $_GET["show"] ?>';
  for (var i = 0; i < rows.length; i++) {
    var tr = rows[i];
    if (tr.className == 'header')
	if (!show || tr.id != show)
	    toggle_group(tr.id);
  }

  // Select text in textarea upon focus
  var area = document.getElementById('config-output');
  if (area) {
    listener = { handleEvent: function (e) { area.select(); } };
    area.addEventListener('focus', listener, false);
  }
}
  
-->
</script>
</head>
<body onload="do_init();">

      <h1>Configuration for PhpWiki <?php echo $config_file ?></h1>

<div class="hint">
    Using this configurator.php is experimental!<br>
    On any configuration problems, please edit the resulting config.ini manually.
</div>

<?php
//define('DEBUG', 1);
/**
 * The Configurator is a php script to aid in the configuration of PhpWiki.
 * Parts of this file were based on PHPWeather's configurator.php file.
 *   http://sourceforge.net/projects/phpweather/
 *
 * TO CHANGE THE CONFIGURATION OF YOUR PHPWIKI, DO *NOT* MODIFY THIS FILE!
 * more instructions go here
 *
 * Todo: 
 *   * fix include_path
 *   * eval config.ini to get the actual settings.
 */

//////////////////////////////
// begin configuration options

/**
 * Notes for the description parameter of $property:
 *
 * - Descriptive text will be changed into comments (preceeded by ; )
 *   for the final output to config.ini.
 *
 * - Only a limited set of html is allowed: pre, dl dt dd; it will be
 *   stripped from the final output.
 *
 * - Line breaks and spacing will be preserved for the final output.
 *
 * - Double line breaks are automatically converted to paragraphs
 *   for the html version of the descriptive text.
 *
 * - Double-quotes and dollar signs in the descriptive text must be
 *   escaped: \" and \$. Instead of escaping double-quotes you can use 
 *   single (') quotes for the enclosing quotes. 
 *
 * - Special characters like < and > must use html entities,
 *   they will be converted back to characters for the final output.
 */

$SEPARATOR = ";=========================================================================";

$preamble = "
; This is the main configuration file for PhpWiki in INI-style format.
; Note that certain characters are used as comment char and therefore 
; these entries must be in double-quotes. Such as \":\", \";\", \",\" and \"|\"
; Take special care for DBAUTH_ sql statements. (Part 3a)
;
; This file is divided into several parts: Each one has different configuration 
; settings you can change; in all cases the default should work on your system,
; however, we recommend you tailor things to your particular setting.
; Here undefined definitions get defined by config-default.ini settings.
";

$properties["Part Zero"] =
new part('_part0', $SEPARATOR."\n", "
Part Zero: (optional)
Latest Development and Tricky Options");

if (defined('INCLUDE_PATH'))
    $include_path = INCLUDE_PATH;
else {
  if (substr(PHP_OS,0,3) == 'WIN') {
      $include_path = dirname(__FILE__) . ';' . ini_get('include_path');
      if (strchr(ini_get('include_path'),'/'))
	  $include_path = strtr($include_path,'\\','/');
  } else {
    $include_path = dirname(__FILE__) . ':' . ini_get('include_path');
  }
}

$properties["PHP include_path"] =
new _define('INCLUDE_PATH', $include_path);

// TODO: convert this a checkbox row as in tests/unit/test.pgp
$properties["DEBUG"] =
new numeric_define_optional('DEBUG', DEBUG);

// TODO: bring the default to the front
$properties["ENABLE_USER_NEW"] =
new boolean_define_commented_optional
('ENABLE_USER_NEW', 
 array('true'  => "Enabled",
       'false' => "Disabled."));

$properties["ENABLE_PAGEPERM"] =
new boolean_define_commented_optional
('ENABLE_PAGEPERM', 
 array('true'  => "Enabled",
       'false' => "Disabled."));

$properties["ENABLE_EDIT_TOOLBAR"] =
new boolean_define_commented_optional
('ENABLE_EDIT_TOOLBAR', 
 array('true'  => "Enabled",
       'false' => "Disabled."));

$properties["JS_SEARCHREPLACE"] =
new boolean_define_commented_optional
('JS_SEARCHREPLACE', 
 array('true'  => "Enabled",
       'false' => "Disabled"));

$properties["ENABLE_DOUBLECLICKEDIT"] =
new boolean_define_commented_optional
('ENABLE_DOUBLECLICKEDIT', 
 array('true'  => "Enabled",
       'false' => "Disabled"));

$properties["ENABLE_XHTML_XML"] =
new boolean_define_commented_optional
('ENABLE_XHTML_XML', 
 array('false' => "Disabled",
       'true'  => "Enabled"));

$properties["USECACHE"] =
new boolean_define_commented_optional
('USECACHE', 
 array('true'  => "Enabled",
       'false' => "Disabled"));

$properties["GOOGLE_LINKS_NOFOLLOW"] =
new boolean_define_commented_optional
('GOOGLE_LINKS_NOFOLLOW', 
 array('true'  => "Enabled",
       'false' => "Disabled"));

$properties["ENABLE_LIVESEARCH"] =
new boolean_define_commented_optional
('ENABLE_LIVESEARCH', 
 array('true'  => "Enabled",
       'false' => "Disabled"));

$properties["ENABLE_ACDROPDOWN"] =
new boolean_define_commented_optional
('ENABLE_ACDROPDOWN', 
 array('true'  => "Enabled",
       'false' => "Disabled"));

$properties["ENABLE_DISCUSSION_LINK"] =
new boolean_define_commented_optional
('ENABLE_DISCUSSION_LINK', 
 array('true'  => "Enabled",
       'false' => "Disabled"));

$properties["USE_SAFE_DBSESSION"] =
new boolean_define_commented_optional
('USE_SAFE_DBSESSION', 
 array('false' => "Disabled",
       'true'  => "Enabled"));

$properties["Part One"] =
new part('_part1', $SEPARATOR."\n", "
Part One: Authentication and security settings. See Part Three for more.");

$properties["Wiki Name"] =
new _define_optional('WIKI_NAME', WIKI_NAME);

$properties["Admin Username"] =
new _define_notempty('ADMIN_USER', ADMIN_USER, "
You must set this! Username and password of the administrator.",
"onchange=\"validate_ereg('Sorry, ADMIN_USER cannot be empty.', '^.+$', 'ADMIN_USER', this);\"");

$properties["Admin Password"] =
new _define_password('ADMIN_PASSWD', ADMIN_PASSWD, "
You must set this! 
For heaven's sake pick a good password.

If your version of PHP supports encrypted passwords, your password will be
automatically encrypted within the generated config file. 
Use the \"Create Random Password\" button to create a good (random) password.

ADMIN_PASSWD is ignored on HttpAuth",
"onchange=\"validate_ereg('Sorry, ADMIN_PASSWD must be at least 4 chars long.', '^....+$', 'ADMIN_PASSWD', this);\"");

$properties["Encrypted Passwords"] =
new boolean_define
('ENCRYPTED_PASSWD',
 array('true'  => "true.  use crypt for all passwords",
       'false' => "false. use plaintest passwords (not recommended)"));

$properties["Reverse DNS"] =
new boolean_define_optional
('ENABLE_REVERSE_DNS',
 array('true'  => "true. perform additional reverse dns lookups",
       'false' => "false. just record the address as given by the httpd server"));

$properties["ZIPdump Authentication"] =
new boolean_define_optional('ZIPDUMP_AUTH', 
                    array('false' => "false. Everyone may download zip dumps",
                          'true'  => "true. Only admin may download zip dumps"));

$properties["Enable RawHtml Plugin"] =
new boolean_define_commented_optional
('ENABLE_RAW_HTML', 
 array('true'  => "Enabled",
       'false' => "Disabled"));

$properties["Allow RawHtml Plugin only on locked pages"] =
new boolean_define_commented_optional
('ENABLE_RAW_HTML_LOCKEDONLY', 
 array('true'  => "Enabled",
       'false' => "Disabled"));

$properties["Allow RawHtml Plugin if safe HTML code"] =
new boolean_define_commented_optional
('ENABLE_RAW_HTML_SAFE', 
 array('true'  => "Enabled",
       'false' => "Disabled"), "
If this is set, all unsafe html code is stripped automatically (experimental!)
See <a href=\"http://chxo.com/scripts/safe_html-test.php\" target=\"_new\">chxo.com/scripts/safe_html-test.php</a>
");

$properties["Maximum Upload Size"] =
new numeric_define_optional('MAX_UPLOAD_SIZE', MAX_UPLOAD_SIZE);

$properties["Minor Edit Timeout"] =
new numeric_define_optional('MINOR_EDIT_TIMEOUT', MINOR_EDIT_TIMEOUT);

$properties["Disabled Actions"] =
new array_define('DISABLED_ACTIONS', DISABLED_ACTIONS /*array()*/);

$properties["Moderate all Pagechanges"] =
new boolean_define_commented_optional
('ENABLE_MODERATEDPAGE_ALL', 
 array('false' => "Disabled",
       'true'  => "Enabled"));

$properties["Compress Output"] =
new boolean_define_commented_optional
( 'COMPRESS_OUTPUT', 
  array(''      => 'undefined - GZIP compress when appropriate.',
        'false' => 'Never compress output.',
        'true'  => 'Always try to compress output.'));

$properties["HTTP Cache Control"] =
new _define_selection_optional
('CACHE_CONTROL',
 array('LOOSE' => 'LOOSE',
       'STRICT' => 'STRICT',
       'NO_CACHE' => 'NO_CACHE',
       'ALLOW_STALE' => 'ALLOW_STALE'),
"
HTTP CACHE_CONTROL

This controls how PhpWiki sets the HTTP cache control
headers (Expires: and Cache-Control:) 

Choose one of:

<dl>
<dt>NO_CACHE</dt>
<dd>This is roughly the old (pre 1.3.4) behaviour.  PhpWiki will
    instruct proxies and browsers never to cache PhpWiki output.</dd>

<dt>STRICT</dt>
<dd>Cached pages will be invalidated whenever the database global
    timestamp changes.  This should behave just like NONE (modulo
    bugs in PhpWiki and your proxies and browsers), except that
    things will be slightly more efficient.</dd>

<dt>LOOSE</dt>
<dd>Cached pages will be invalidated whenever they are edited,
    or, if the pages include plugins, when the plugin output could
    concievably have changed.

    <p>Behavior should be much like STRICT, except that sometimes
       wikilinks will show up as undefined (with the question mark)
       when in fact they refer to (recently) created pages.
       (Hitting your browsers reload or perhaps shift-reload button
       should fix the problem.)</p></dd>

<dt>ALLOW_STALE</dt>
<dd>Proxies and browsers will be allowed to used stale pages.
    (The timeout for stale pages is controlled by CACHE_CONTROL_MAX_AGE.)

    <p>This setting will result in quirky behavior.  When you edit a
       page your changes may not show up until you shift-reload the
       page, etc...</p>

    <p>This setting is generally not advisable, however it may be useful
       in certain cases (e.g. if your wiki gets lots of page views,
       and few edits by knowledgable people who won't freak over the quirks.)</p>
</dd>

The default is currently LOOSE.");

$properties["HTTP Cache Control Max Age"] =
new numeric_define_optional('CACHE_CONTROL_MAX_AGE', CACHE_CONTROL_MAX_AGE);

$properties["Markup Caching"] =
new boolean_define_commented_optional
('WIKIDB_NOCACHE_MARKUP',
 array('false' => 'Enable markup cache',
       'true'  => 'Disable markup cache'));

$properties["COOKIE_EXPIRATION_DAYS"] =
new numeric_define_optional('COOKIE_EXPIRATION_DAYS', COOKIE_EXPIRATION_DAYS);

$properties["COOKIE_DOMAIN"] =
new _define_commented_optional('COOKIE_DOMAIN', COOKIE_DOMAIN);

$properties["Path for PHP Session Support"] =
new _define_optional('SESSION_SAVE_PATH', defined('SESSION_SAVE_PATH') ? SESSION_SAVE_PATH : ini_get('session.save_path'));

$properties["Force PHP Database Sessions"] =
new boolean_define_commented_optional
('USE_DB_SESSION', 
 array('false' => 'Disable database sessions, use files',
       'true'  => 'Enable database sessions'));

///////// database selection

$properties["Part Two"] =
new part('_part2', $SEPARATOR."\n", "

Part Two:
Database Configuration
");

$properties["Database Type"] =
new _define_selection("DATABASE_TYPE",
              array('dba'   => "dba",
                    'SQL'   => "SQL PEAR",
                    'ADODB' => "SQL ADODB",
                    'PDO'   => "PDO (php5 only)",
                    'file'   => "flatfile",
                    'cvs'   => "CVS File handler")/*, "
Select the database backend type:
Choose dba (default) to use one of the standard UNIX dba libraries. This is the fastest.
Choose ADODB or SQL to use an SQL database with ADODB or PEAR.
Choose PDO on php5 to use an SQL database. (experimental, no paging yet)
flatfile is simple and slow.
CVS is highly experimental and slow.
Recommended is dba or SQL: PEAR or ADODB."*/);

$properties["SQL DSN Setup"] =
new unchangeable_variable('_sqldsnstuff', "", "
For SQL based backends, specify the database as a DSN
The most general form of a DSN looks like:
<pre>
  phptype(dbsyntax)://username:password@protocol+hostspec/database?option=value
</pre>
For a MySQL database, the following should work:
<pre>
   mysql://user:password@host/databasename
</pre>
To connect over a unix socket, use something like
<pre>
   mysql://user:password@unix(/path/to/socket)/databasename
</pre>
<pre>
  DATABASE_DSN = mysql://guest@:/var/lib/mysql/mysql.sock/phpwiki
  DATABASE_DSN = mysql://guest@localhost/phpwiki
  DATABASE_DSN = pgsql://localhost/user_phpwiki
</pre>");

// Choose ADODB or SQL to use an SQL database with ADODB or PEAR.
// Choose dba to use one of the standard UNIX dbm libraries.

$properties["SQL Type"] =
new _variable_selection('_dsn_sqltype',
              array('mysql'  => "MySQL",
                    'pgsql'  => "PostgreSQL",
                    'mssql'  => "Microsoft SQL Server",
                    'oci8'   => "Oracle 8",
                    'mysqli' => "mysqli (only ADODB)",
                    'mysqlt' => "mysqlt (only ADODB)",
                    'ODBC'   => "ODBC (only ADODB or PDO)",
                    'firebird' => "Firebird (only PDO)",
                    'oracle'  => "Oracle (only PDO)",
), "
SQL DB types. The DSN hosttype.");

$properties["SQL User"] =
new _variable('_dsn_sqluser', "wikiuser", "
SQL User Id:");

$properties["SQL Password"] =
new _variable('_dsn_sqlpass', "", "
SQL Password:");

$properties["SQL Database Host"] =
new _variable('_dsn_sqlhostorsock', "localhost", "
SQL Database Hostname:

To connect over a local named socket, use something like
<pre>
  unix(/var/lib/mysql/mysql.sock)
</pre>
here. 
mysql on Windows via named pipes might need 127.0.0.1");

$properties["SQL Database Name"] =
new _variable('_dsn_sqldbname', "phpwiki", "
SQL Database Name:");

$dsn_sqltype = $properties["SQL Type"]->value();
$dsn_sqluser = $properties["SQL User"]->value();
$dsn_sqlpass = $properties["SQL Password"]->value();
$dsn_sqlhostorsock = $properties["SQL Database Host"]->value();
$dsn_sqldbname = $properties["SQL Database Name"]->value();
$dsn_sqlstring = $dsn_sqltype."://{$dsn_sqluser}:{$dsn_sqlpass}@{$dsn_sqlhostorsock}/{$dsn_sqldbname}";

$properties["SQL dsn"] =
new unchangeable_define("DATABASE_DSN", 
                        $dsn_sqlstring, "
Calculated from the settings above:");

$properties["Filename / Table name Prefix"] =
new _define_commented("DATABASE_PREFIX", DATABASE_PREFIX, "
Used by all DB types:

Prefix for filenames or table names, e.g. \"phpwiki_\"

Currently <b>you MUST EDIT THE SQL file too!</b> (in the schemas/
directory because we aren't doing on the fly sql generation
during the installation.");

$properties["DATABASE_PERSISTENT"] =
new boolean_define_commented_optional
('DATABASE_PERSISTENT', 
 array('false' => "Disabled",
       'true'  => "Enabled"));

$properties["DB Session table"] =
new _define_optional("DATABASE_SESSION_TABLE", DATABASE_SESSION_TABLE, "
Tablename to store session information. Only supported by SQL backends.

A word of warning - any prefix defined above will be prepended to whatever is given here.
");

//TODO: $TEMP
$temp = !empty($_ENV['TEMP']) ? $_ENV['TEMP'] : "/tmp";
$properties["dba directory"] =
new _define("DATABASE_DIRECTORY", $temp);

// TODO: list the available methods
$properties["dba handler"] =
new _define_selection('DATABASE_DBA_HANDLER',
              array('gdbm' => "gdbm - GNU database manager (not recommended anymore)",
                    'dbm'  => "DBM - Redhat default. On sf.net there's dbm and not gdbm anymore",
                    'db2'  => "DB2 - BerkeleyDB (Sleepycat) DB2",
                    'db3'  => "DB3 - BerkeleyDB (Sleepycat) DB3. Default on Windows but not on every Linux",
                    'db4'  => "DB4 - BerkeleyDB (Sleepycat) DB4."), "
Use 'gdbm', 'dbm', 'db2', 'db3' or 'db4' depending on your DBA handler methods supported: <br >  "
                      . (function_exists("dba_handlers") ? join(", ",dba_handlers()) : "")
		      . "\n\nBetter not use other hacks such as inifile, flatfile or cdb");

$properties["dba timeout"] =
new numeric_define("DATABASE_TIMEOUT", DATABASE_TIMEOUT, "
Recommended values are 10-20 seconds. The more load the server has, the higher the timeout.");

$properties["DBADMIN_USER"] =
new _define_optional('DBADMIN_USER', DBADMIN_USER. "
If action=upgrade detects mysql problems, but has no ALTER permissions, 
give here a database username which has the necessary ALTER or CREATE permissions.
Of course you can fix your database manually. See lib/upgrade.php for known issues.");

$properties["DBADMIN_PASSWD"] =
new _define_password_optional('DBADMIN_PASSWD', DBADMIN_PASSWD);

///////////////////

$properties["Page Revisions"] =
new unchangeable_variable('_parttworevisions', "", "

Section 2a: Archive Cleanup
The next section controls how many old revisions of each page are kept in the database.

There are two basic classes of revisions: major and minor. Which
class a revision belongs in is determined by whether the author
checked the \"this is a minor revision\" checkbox when they saved the
page.
 
There is, additionally, a third class of revisions: author
revisions. The most recent non-mergable revision from each distinct
author is and author revision.

The expiry parameters for each of those three classes of revisions
can be adjusted seperately. For each class there are five
parameters (usually, only two or three of the five are actually
set) which control how long those revisions are kept in the
database.
<dl>
   <dt>max_keep:</dt> <dd>If set, this specifies an absolute maximum for the
            number of archived revisions of that class. This is
            meant to be used as a safety cap when a non-zero
            min_age is specified. It should be set relatively high,
            and it's purpose is to prevent malicious or accidental
            database overflow due to someone causing an
            unreasonable number of edits in a short period of time.</dd>

  <dt>min_age:</dt>  <dd>Revisions younger than this (based upon the supplanted
            date) will be kept unless max_keep is exceeded. The age
            should be specified in days. It should be a
            non-negative, real number,</dd>

  <dt>min_keep:</dt> <dd>At least this many revisions will be kept.</dd>

  <dt>keep:</dt>     <dd>No more than this many revisions will be kept.</dd>

  <dt>max_age:</dt>  <dd>No revision older than this age will be kept.</dd>
</dl>
Supplanted date: Revisions are timestamped at the instant that they
cease being the current revision. Revision age is computed using
this timestamp, not the edit time of the page.

Merging: When a minor revision is deleted, if the preceding
revision is by the same author, the minor revision is merged with
the preceding revision before it is deleted. Essentially: this
replaces the content (and supplanted timestamp) of the previous
revision with the content after the merged minor edit, the rest of
the page metadata for the preceding version (summary, mtime, ...)
is not changed.
");

// For now the expiration parameters are statically inserted as
// an unchangeable property. You'll have to edit the resulting
// config file if you really want to change these from the default.

$properties["Major Edits: keep minimum days"] =
    new numeric_define("MAJOR_MIN_KEEP", MAJOR_MIN_KEEP, "
Default: Keep for unlimited time. 
Set to 0 to enable archive cleanup");
$properties["Minor Edits: keep minumum days"] =
    new numeric_define("MINOR_MIN_KEEP", MINOR_MIN_KEEP, "
Default: Keep for unlimited time. 
Set to 0 to enable archive cleanup");

$properties["Major Edits: how many"] =
    new numeric_define("MAJOR_KEEP", MAJOR_KEEP, "
Keep up to 8 major edits");
$properties["Major Edits: how many days"] =
    new numeric_define("MAJOR_MAX_AGE", MAJOR_MAX_AGE, "
keep them no longer than a month");

$properties["Minor Edits: how many"] =
    new numeric_define("MINOR_KEEP", MINOR_KEEP, "
Keep up to 4 minor edits");
$properties["Minor Edits: how many days"] =
    new numeric_define("MINOR_MAX_AGE", "7", "
keep them no longer than a week");

$properties["per Author: how many"] =
    new numeric_define("AUTHOR_KEEP", "8", "
Keep the latest contributions of the last 8 authors,");
$properties["per Author: how many days"] =
    new numeric_define("AUTHOR_MAX_AGE", "365", "
up to a year.");
$properties["per Author: keep minumum days"] =
    new numeric_define("AUTHOR_MIN_AGE", "7", "
Additionally, (in the case of a particularly active page) try to
keep the latest contributions of all authors in the last week (even if there are more than eight of them,)");
$properties["per Author: max revisions"] =
    new numeric_define("AUTHOR_MAX_KEEP", "20", "
but in no case keep more than twenty unique author revisions.");

/////////////////////////////////////////////////////////////////////

$properties["Part Three"] =
new part('_part3', $SEPARATOR."\n", "

Part Three: (optional)
Basic User Authentication Setup
");

$properties["Publicly viewable"] =
new boolean_define_optional('ALLOW_ANON_USER',
                    array('true'  => "true. Permit anonymous view. (Default)",
                          'false' => "false. Force login even on view (strictly private)"), "
If ALLOW_ANON_USER is false, you have to login before viewing any page or doing any other action on a page.");

$properties["Allow anonymous edit"] =
new boolean_define_optional('ALLOW_ANON_EDIT',
                    array('true'  => "true. Permit anonymous users to edit. (Default)",
                          'false' => "false. Force login on edit (moderately locked)"), "
If ALLOW_ANON_EDIT is false, you have to login before editing or changing any page. See below.");

$properties["Allow Bogo Login"] =
new boolean_define_optional('ALLOW_BOGO_LOGIN',
                    array('true'  => "true. Users may Sign In with any WikiWord, without password. (Default)",
                          'false' => "false. Require stricter authentication."), "
If ALLOW_BOGO_LOGIN is false, you may not login with any wikiword username and empty password. 
If true, users are allowed to create themselves with any WikiWord username. See below.");

$properties["Allow User Passwords"] =
new boolean_define_optional('ALLOW_USER_PASSWORDS',
                    array('true'  => "True user authentication with password checking. (Default)",
                          'false' => "false. Ignore authentication settings below."), "
If ALLOW_USER_PASSWORDS is true, the authentication settings below define where and how to 
check against given username/passwords. For completely security disable BOGO_LOGIN and ANON_EDIT above.");

$properties["User Authentication Methods"] =
    new array_define('USER_AUTH_ORDER', array("PersonalPage", "Db"), "
Many different methods can be used to check user's passwords. 
Try any of these in the given order:
<dl>
<dt>BogoLogin</dt>
	<dd>WikiWord username, with no *actual* password checking,
        although the user will still have to enter one.</dd>
<dt>PersonalPage</dt>
	<dd>Store passwords in the users homepage metadata (simple)</dd>
<dt>Db</dt>
	<dd>Use DBAUTH_AUTH_* (see below) with PearDB or ADODB only.</dd>
<dt>LDAP</dt>
	<dd>Authenticate against LDAP_AUTH_HOST with LDAP_BASE_DN.</dd>
<dt>IMAP</dt>
	<dd>Authenticate against IMAP_AUTH_HOST (email account)</dd>
<dt>POP3</dt>
	<dd>Authenticate against POP3_AUTH_HOST (email account)</dd>
<dt>Session</dt>
	<dd>Get username and level from a PHP session variable. (e.g. for gforge)</dd>
<dt>File</dt>
	<dd>Store username:crypted-passwords in .htaccess like files. 
         Use Apache's htpasswd to manage this file.</dd>
<dt>HttpAuth</dt>
	<dd>Use the protection by the webserver (.htaccess/.htpasswd) (experimental)
	Enforcing HTTP Auth not yet. Note that the ADMIN_USER should exist also.
        Using HttpAuth disables all other methods and no userauth sessions are used.</dd>
</dl>

Several of these methods can be used together, in the manner specified by
USER_AUTH_POLICY, below.  To specify multiple authentication methods,
separate the name of each one with colons.
<pre>
  USER_AUTH_ORDER = 'PersonalPage : Db'
  USER_AUTH_ORDER = 'BogoLogin : PersonalPage'
</pre>");

$properties["PASSWORD_LENGTH_MINIMUM"] =
    new numeric_define("PASSWORD_LENGTH_MINIMUM", "6");

$properties["USER_AUTH_POLICY"] =
new _define_selection('USER_AUTH_POLICY',
              array('first-only' => "first-only - use only the first method in USER_AUTH_ORDER",
                    'old'  	=> "old - ignore USER_AUTH_ORDER (legacy)",
                    'strict'  	=> "strict - check all methods for userid + password (recommended)",
                    'stacked'  	=> "stacked - check all methods for userid, and if found for password"), "
The following policies are available for user authentication:
<dl>
<dt>first-only</dt>
	<dd>use only the first method in USER_AUTH_ORDER</dd>
<dt>old</dt>
	<dd>ignore USER_AUTH_ORDER and try to use all available 
        methods as in the previous PhpWiki releases (slow)</dd>
<dt>strict</dt>
	<dd>check if the user exists for all methods: 
        on the first existing user, try the password. 
        dont try the other methods on failure then</dd>
<dt>stacked</dt>
	<dd>check the given user - password combination for all
        methods and return true on the first success.</dd></dl>");

///////////////////

$properties["Part Three A"] =
new part('_part3a', $SEPARATOR."\n", "

Part Three A: (optional)
Group Membership");

$properties["Group membership"] =
new _define_selection("GROUP_METHOD",
              array('WIKIPAGE' => "WIKIPAGE - List at \"CategoryGroup\". (Slowest, but easiest to maintain)",
                    '"NONE"'   => "NONE - Disable group membership (Fastest)",
                    'DB'       => "DB - SQL Database, Optionally external. See USERS/GROUPS queries",
                    'FILE'     => "Flatfile. See AUTH_GROUP_FILE below.",
                    'LDAP'     => "LDAP - See \"LDAP authentication options\" above. (Experimental)"), "
Group membership.  PhpWiki supports defining permissions for a group as
well as for individual users.  This defines how group membership information
is obtained.  Supported values are:
<dl>
<dt>\"NONE\"</dt>
          <dd>Disable group membership (Fastest). Note the required quoting.</dd>
<dt>WIKIPAGE</dt>
          <dd>Define groups as list at \"CategoryGroup\". (Slowest, but easiest to maintain)</dd>
<dt>DB</dt>
          <dd>Stored in an SQL database. Optionally external. See USERS/GROUPS queries</dd>
<dt>FILE</dt>
          <dd>Flatfile. See AUTH_GROUP_FILE below.</dd>
<dt>LDAP</dt>
          <dd>LDAP groups. See \"LDAP authentication options\" above and 
          lib/WikiGroup.php. (experimental)</dd></dl>");

$properties["CATEGORY_GROUP_PAGE"] =
  new _define_optional('CATEGORY_GROUP_PAGE', _("CategoryGroup"), "
If GROUP_METHOD = WIKIPAGE:

Page where all groups are listed.");

$properties["AUTH_GROUP_FILE"] =
  new _define_optional('AUTH_GROUP_FILE', _("/etc/groups"), "
For GROUP_METHOD = FILE, the file given below is referenced to obtain
group membership information.  It should be in the same format as the
standard unix /etc/groups(5) file.");

$properties["Part Three B"] =
new part('_part3b', $SEPARATOR."\n", "

Part Three B: (optional)
External database authentication and authorization.

If USER_AUTH_ORDER includes Db, or GROUP_METHOD = DB, the options listed
below define the SQL queries used to obtain the information out of the
database, and (optionally) store the information back to the DB.");

$properties["DBAUTH_AUTH_DSN"] =
  new _define_optional('DBAUTH_AUTH_DSN', $dsn_sqlstring, "
A database DSN to connect to.  Defaults to the DSN specified for the Wiki as a whole.");

$properties["User Exists Query"] =
  new _define('DBAUTH_AUTH_USER_EXISTS', "SELECT userid FROM user WHERE userid='\$userid'", "
USER/PASSWORD queries:

For USER_AUTH_POLICY=strict and the Db method is required");

$properties["Check Query"] =
  new _define_optional('DBAUTH_AUTH_CHECK', "SELECT IF(passwd='\$password',1,0) AS ok FROM user WHERE userid='\$userid'", "

Check to see if the supplied username/password pair is OK

Plaintext passwords: (DBAUTH_AUTH_CRYPT_METHOD = plain)<br>
; DBAUTH_AUTH_CHECK = \"SELECT IF(passwd='\$password',1,0) AS ok FROM user WHERE userid='\$userid'\"

database-hashed passwords (more secure):<br>
; DBAUTH_AUTH_CHECK = \"SELECT IF(passwd=PASSWORD('\$password'),1,0) AS ok FROM user WHERE userid='\$userid'\"");

$properties["Crypt Method"] =
new _define_selection_optional
('DBAUTH_AUTH_CRYPT_METHOD',
 array('plain' => 'plain',
       'crypt' => 'crypt'), "
If you want to use Unix crypt()ed passwords, you can use DBAUTH_AUTH_CHECK
to get the password out of the database with a simple SELECT query, and
specify DBAUTH_AUTH_USER_EXISTS and DBAUTH_AUTH_CRYPT_METHOD:

; DBAUTH_AUTH_CHECK = \"SELECT passwd FROM user where userid='\$userid'\" <br>
; DBAUTH_AUTH_CRYPT_METHOD = crypt");

$properties["Update the user's authentication credential"] =
    new _define('DBAUTH_AUTH_UPDATE', "UPDATE user SET passwd='\$password' WHERE userid='\$userid'", "
If this is not defined but DBAUTH_AUTH_CHECK is, then the user will be unable to update their
password.

Plaintext passwords:<br>
  DBAUTH_AUTH_UPDATE = \"UPDATE user SET passwd='\$password' WHERE userid='\$userid'\"<br>
Database-hashed passwords:<br>
  DBAUTH_AUTH_UPDATE = \"UPDATE user SET passwd=PASSWORD('\$password') WHERE userid='\$userid'\"");

$properties["Allow the user to create their own account"] =
    new _define_optional('DBAUTH_AUTH_CREATE', "INSERT INTO user SET passwd=PASSWORD('\$password'),userid='\$userid'", "
If this is empty, Db users cannot subscribe by their own.");

$properties["USER/PREFERENCE queries"] =
    new _define_optional('DBAUTH_PREF_SELECT', "SELECT prefs FROM user WHERE userid='\$userid'", "
If you choose to store your preferences in an external database, enable
the following queries.  Note that if you choose to store user preferences
in the 'user' table, only registered users get their prefs from the database,
self-created users do not.  Better to use the special 'pref' table.

The prefs field stores the serialized form of the user's preferences array,
to ease the complication of storage.
<pre>
  DBAUTH_PREF_SELECT = \"SELECT prefs FROM user WHERE userid='\$userid'\"
  DBAUTH_PREF_SELECT = \"SELECT prefs FROM pref WHERE userid='\$userid'\"
</pre>");

$properties["Update the user's preferences"] =
    new _define_optional('DBAUTH_PREF_UPDATE', "UPDATE user SET prefs='\$pref_blob' WHERE userid='\$userid'", "
Note that REPLACE works only with mysql and destroy all other columns!

Mysql: DBAUTH_PREF_UPDATE = \"REPLACE INTO pref SET prefs='\$pref_blob',userid='\$userid'\"");

$properties["USERS/GROUPS queries"] =
    new _define_optional('DBAUTH_IS_MEMBER', "SELECT user FROM user WHERE user='\$userid' AND group='\$groupname'", "
You can define 1:n or n:m user<=>group relations, as you wish.

Sample configurations:

only one group per user (1:n):<br>
   DBAUTH_IS_MEMBER = \"SELECT user FROM user WHERE user='\$userid' AND group='\$groupname'\"<br>
   DBAUTH_GROUP_MEMBERS = \"SELECT user FROM user WHERE group='\$groupname'\"<br>
   DBAUTH_USER_GROUPS = \"SELECT group FROM user WHERE user='\$userid'\"<br>
multiple groups per user (n:m):<br>
   DBAUTH_IS_MEMBER = \"SELECT userid FROM member WHERE userid='\$userid' AND groupname='\$groupname'\"<br>
   DBAUTH_GROUP_MEMBERS = \"SELECT DISTINCT userid FROM member WHERE groupname='\$groupname'\"<br>
   DBAUTH_USER_GROUPS = \"SELECT groupname FROM member WHERE userid='\$userid'\"<br>");
$properties["DBAUTH_GROUP_MEMBERS"] =
    new _define_optional('DBAUTH_GROUP_MEMBERS', "SELECT user FROM user WHERE group='\$groupname'", "");
$properties["DBAUTH_USER_GROUPS"] =
    new _define_optional('DBAUTH_USER_GROUPS', "SELECT group FROM user WHERE user='\$userid'", "");

if (function_exists('ldap_connect')) {

$properties["LDAP AUTH Host"] =
  new _define_optional('LDAP_AUTH_HOST', "ldap://localhost:389", "
If USER_AUTH_ORDER contains Ldap:

The LDAP server to connect to.  Can either be a hostname, or a complete
URL to the server (useful if you want to use ldaps or specify a different
port number).");

$properties["LDAP BASE DN"] =
  new _define_optional('LDAP_BASE_DN', "ou=mycompany.com,o=My Company", "
The organizational or domain BASE DN: e.g. \"dc=mydomain,dc=com\".

Note: ou=Users and ou=Groups are used for GroupLdap Membership
Better use LDAP_OU_USERS and LDAP_OU_GROUP with GROUP_METHOD=LDAP.");

$properties["LDAP SET OPTION"] =
    new _define_optional('LDAP_SET_OPTION', "LDAP_OPT_PROTOCOL_VERSION=3:LDAP_OPT_REFERRALS=0", "
Some LDAP servers need some more options, such as the Windows Active
Directory Server.  Specify the options (as allowed by the PHP LDAP module)
and their values as NAME=value pairs separated by colons.");

$properties["LDAP AUTH USER"] =
    new _define_optional('LDAP_AUTH_USER', "CN=ldapuser,ou=Users,o=Development,dc=mycompany.com", "
DN to initially bind to the LDAP server as. This is needed if the server doesn't 
allow anonymous queries. (Windows Active Directory Server)");

$properties["LDAP AUTH PASSWORD"] =
    new _define_optional('LDAP_AUTH_PASSWORD', "secret", "
Password to use to initially bind to the LDAP server, as the DN 
specified in the LDAP_AUTH_USER option (above).");

$properties["LDAP SEARCH FIELD"] =
    new _define_optional('LDAP_SEARCH_FIELD', "uid", "
If you want to match usernames against an attribute other than uid,
specify it here. Default: uid

e.g.: LDAP_SEARCH_FIELD = sAMAccountName");

$properties["LDAP OU USERS"] =
    new _define_optional('LDAP_OU_USERS', "ou=Users", "
If you have an organizational unit for all users, define it here.
This narrows the search, and is needed for LDAP group membership (if GROUP_METHOD=LDAP)
Default: ou=Users");

$properties["LDAP OU GROUP"] =
    new _define_optional('LDAP_OU_GROUP', "ou=Groups", "
If you have an organizational unit for all groups, define it here.
This narrows the search, and is needed for LDAP group membership (if GROUP_METHOD=LDAP)
The entries in this ou must have a gidNumber and cn attribute.
Default: ou=Groups");

} else { // function_exists('ldap_connect')

$properties["LDAP Authentication"] =
new unchangeable_variable('LDAP Authentication', "
; If USER_AUTH_ORDER contains Ldap:
; 
; The LDAP server to connect to.  Can either be a hostname, or a complete
; URL to the server (useful if you want to use ldaps or specify a different
; port number).
;LDAP_AUTH_HOST = \"ldap://localhost:389\"
; 
; The organizational or domain BASE DN: e.g. \"dc=mydomain,dc=com\".
;
; Note: ou=Users and ou=Groups are used for GroupLdap Membership
; Better use LDAP_OU_USERS and LDAP_OU_GROUP with GROUP_METHOD=LDAP.
;LDAP_BASE_DN = \"ou=Users,o=Development,dc=mycompany.com\"

; Some LDAP servers need some more options, such as the Windows Active
; Directory Server.  Specify the options (as allowed by the PHP LDAP module)
; and their values as NAME=value pairs separated by colons.
; LDAP_SET_OPTION = \"LDAP_OPT_PROTOCOL_VERSION=3:LDAP_OPT_REFERRALS=0\"

; DN to initially bind to the LDAP server as. This is needed if the server doesn't 
; allow anonymous queries. (Windows Active Directory Server)
; LDAP_AUTH_USER = \"CN=ldapuser,ou=Users,o=Development,dc=mycompany.com\"

; Password to use to initially bind to the LDAP server, as the DN 
; specified in the LDAP_AUTH_USER option (above).
; LDAP_AUTH_PASSWORD = secret

; If you want to match usernames against an attribute other than uid,
; specify it here. Default: uid
; LDAP_SEARCH_FIELD = sAMAccountName

; If you have an organizational unit for all users, define it here.
; This narrows the search, and is needed for LDAP group membership (if GROUP_METHOD=LDAP)
; Default: ou=Users
; LDAP_OU_USERS = ou=Users

; If you have an organizational unit for all groups, define it here.
; This narrows the search, and is needed for LDAP group membership (if GROUP_METHOD=LDAP)
; The entries in this ou must have a gidNumber and cn attribute.
; Default: ou=Groups
; LDAP_OU_GROUP = ou=Groups", "
; Ignored. No LDAP support in this php. configure --with-ldap");
}

if (function_exists('imap_open')) {

$properties["IMAP Auth Host"] =
  new _define_optional('IMAP_AUTH_HOST', 'localhost:143/imap/notls', "
If USER_AUTH_ORDER contains IMAP:

The IMAP server to check usernames from. Defaults to localhost.

Some IMAP_AUTH_HOST samples:
  localhost, localhost:143/imap/notls, 
  localhost:993/imap/ssl/novalidate-cert (SuSE refuses non-SSL conections)");

} else { // function_exists('imap_open')

$properties["IMAP Authentication"] =
  new unchangeable_variable('IMAP_AUTH_HOST',"
; If USER_AUTH_ORDER contains IMAP:
; The IMAP server to check usernames from. Defaults to localhost.
; 
; Some IMAP_AUTH_HOST samples:
;   localhost, localhost:143/imap/notls, 
;   localhost:993/imap/ssl/novalidate-cert (SuSE refuses non-SSL conections)
;IMAP_AUTH_HOST = localhost:143/imap/notls", "
Ignored. No IMAP support in this php. configure --with-imap");

}

$properties["POP3 Authentication"] =
  new _define_optional('POP3_AUTH_HOST', 'localhost:110', "
If USER_AUTH_ORDER contains POP3:

The POP3 mail server to check usernames and passwords against.");
$properties["File Authentication"] =
  new _define_optional('AUTH_USER_FILE', '/etc/shadow', "
If USER_AUTH_ORDER contains File:

File to read for authentication information.
Popular choices are /etc/shadow and /etc/httpd/.htpasswd");

$properties["File Storable?"] =
new boolean_define_commented_optional
('AUTH_USER_FILE_STORABLE',
 array('false'  => "Disabled",
       'true'   => "Enabled"), "
Defines whether the user is able to change their own password via PhpWiki.
Note that this means that the webserver user must be able to write to the
file specified in AUTH_USER_FILE.");

$properties["Session Auth USER"] =
  new _define_optional('AUTH_SESS_USER', 'userid', "
If USER_AUTH_ORDER contains Session:

Name of the session variable which holds the already authenticated username.
Sample: 'userid', 'user[username]', 'user->username'");

$properties["Session Auth LEVEL"] =
  new numeric_define('AUTH_SESS_LEVEL', '2', "
Which level will the user be? 1 = Bogo or 2 = Pass");

/////////////////////////////////////////////////////////////////////

$properties["Part Four"] =
new part('_part4', $SEPARATOR."\n", "

Part Four:
Page appearance and layout");

$properties["Theme"] =
new _define_selection_optional('THEME',
              array('default'  => "default",
                    'MacOSX'   => "MacOSX",
                    'smaller'  => 'smaller',
                    'Wordpress'=> 'Wordpress',
                    'Portland' => "Portland",
                    'Sidebar'  => "Sidebar",
                    'Crao'     => 'Crao',
                    'wikilens' => 'wikilens (Ratings)',
                    'shamino_com' => 'shamino_com',
                    'SpaceWiki' => "SpaceWiki",
                    'Hawaiian' => "Hawaiian",
                    'MonoBook'  => 'MonoBook [experimental]',
                    'blog' 	=> 'blog [experimental]',
                    ), "
THEME

Most of the page appearance is controlled by files in the theme
subdirectory.

There are a number of pre-defined themes shipped with PhpWiki.
Or you may create your own (e.g. by copying and then modifying one of
stock themes.)
<pre>
  THEME = default
  THEME = MacOSX
  THEME = smaller
  THEME = Wordpress
  THEME = Portland
  THEME = Sidebar
  THEME = Crao
  THEME = wikilens (Ratings)
  THEME = Hawaiian
  THEME = SpaceWiki
  THEME = Hawaiian
</pre>
  
Problems:
<pre>
  THEME = MonoBook (WikiPedia) [experimental. MSIE problems]
  THEME = blog     (Kubrick)   [experimental. Several links missing]
</pre>");

$properties["Character Set"] =
new _define_optional('CHARSET', 'iso-8859-1', "
Select a valid charset name to be inserted into the xml/html pages, 
and to reference links to the stylesheets (css). For more info see: 
http://www.iana.org/assignments/character-sets. Note that PhpWiki 
has been extensively tested only with the latin1 (iso-8859-1) 
character set.

If you change the default from iso-8859-1 PhpWiki may not work 
properly and it will require code modifications. However, character 
sets similar to iso-8859-1 may work with little or no modification 
depending on your setup. The database must also support the same 
charset, and of course the same is true for the web browser. (Some 
work is in progress hopefully to allow more flexibility in this 
area in the future).");

$properties["Language"] =
new _define_selection_optional('DEFAULT_LANGUAGE',
               array('en' => "English",
                     ''   => "<empty> (user-specific)",
                     'fr' => "Fran~is",
                     'de' => "Deutsch",
                     'nl' => "Nederlands",
                     'es' => "Espa~l",
                     'sv' => "Svenska",
                     'it' => "Italiano",
                     'ja' => "Japanese",
                     'zh' => "Chinese"), "
Select your language/locale - default language is \"en\" for English.
Other languages available:<pre>
English \"en\"  (English    - HomePage)
German  \"de\" (Deutsch    - StartSeite)
French  \"fr\" (Fran~is   - Accueil)
Dutch   \"nl\" (Nederlands - ThuisPagina)
Spanish \"es\" (Espa~l    - P~inaPrincipal)
Swedish \"sv\" (Svenska    - Framsida)
Italian \"it\" (Italiano   - PaginaPrincipale)
Japanese \"ja\" (Japanese   - ~~~~~~)
Chinese  \"zh\" (Chinese)
</pre>
If you set DEFAULT_LANGUAGE to the empty string, your systems default language
(as determined by the applicable environment variables) will be
used.");

$properties["Wiki Page Source"] =
new _define_optional('WIKI_PGSRC', 'pgsrc', "
WIKI_PGSRC -- specifies the source for the initial page contents of
the Wiki. The setting of WIKI_PGSRC only has effect when the wiki is
accessed for the first time (or after clearing the database.)
WIKI_PGSRC can either name a directory or a zip file. In either case
WIKI_PGSRC is scanned for files -- one file per page.
<pre>
// Default (old) behavior:
define('WIKI_PGSRC', 'pgsrc'); 
// New style:
define('WIKI_PGSRC', 'wiki.zip'); 
define('WIKI_PGSRC', 
       '../Logs/Hamwiki/hamwiki-20010830.zip'); 
</pre>");

/*
$properties["Default Wiki Page Source"] =
new _define('DEFAULT_WIKI_PGSRC', 'pgsrc', "
DEFAULT_WIKI_PGSRC is only used when the language is *not* the
default (English) and when reading from a directory: in that case
some English pages are inserted into the wiki as well.
DEFAULT_WIKI_PGSRC defines where the English pages reside.

FIXME: is this really needed?
");

$properties["Generic Pages"] =
new array_variable('GenericPages', array('ReleaseNotes', 'SteveWainstead', 'TestPage'), "
These are the pages which will get loaded from DEFAULT_WIKI_PGSRC.	

FIXME: is this really needed?  Cannot we just copy these pages into
the localized pgsrc?
");
*/

///////////////////

$properties["Part Five"] =
new part('_part5', $SEPARATOR."\n", "

Part Five:
Mark-up options");

$properties["Allowed Protocols"] =
new list_define('ALLOWED_PROTOCOLS', 'http|https|mailto|ftp|news|nntp|ssh|gopher', "
Allowed protocols for links - be careful not to allow \"javascript:\"
URL of these types will be automatically linked.
within a named link [name|uri] one more protocol is defined: phpwiki");

$properties["Inline Images"] =
new list_define('INLINE_IMAGES', 'png|jpg|gif', "
URLs ending with the following extension should be inlined as images. 
Scripts shoud not be allowed!");

$properties["WikiName Regexp"] =
new _define('WIKI_NAME_REGEXP', "(?<![[:alnum:]])(?:[[:upper:]][[:lower:]]+){2,}(?![[:alnum:]])", "
Perl regexp for WikiNames (\"bumpy words\")
(?&lt;!..) &amp; (?!...) used instead of '\b' because \b matches '_' as well");

$properties["Subpage Separator"] =
new _define_optional('SUBPAGE_SEPARATOR', '"/"', "
One character which seperates pages from subpages. Defaults to '/', but '.' or ':' were also used.",
"onchange=\"validate_ereg('Sorry, \'%s\' must be a single character. Currently only :, / or .', '^[/:.]$', 'SUBPAGE_SEPARATOR', this);\""
);

$properties["InterWiki Map File"] =
new _define('INTERWIKI_MAP_FILE', 'lib/interwiki.map', "
InterWiki linking -- wiki-style links to other wikis on the web

The map will be taken from a page name InterWikiMap.
If that page is not found (or is not locked), or map
data can not be found in it, then the file specified
by INTERWIKI_MAP_FILE (if any) will be used.");

$properties["WARN_NONPUBLIC_INTERWIKIMAP"] =
new boolean_define('WARN_NONPUBLIC_INTERWIKIMAP',   
	array('true'  => "true",
              'false' => "false"), "
Display a warning if the internal lib/interwiki.map is used, and 
not the public InterWikiMap page. This map is not readable from outside.");


$properties["Keyword Link Regexp"] =
new _define_optional('KEYWORDS', '\"Category* OR Topic*\"', "
Search term used for automatic page classification by keyword extraction.

Any links on a page to pages whose names match this search 
will be used keywords in the keywords html meta tag. This is an aid to
classification by search engines. The value of the match is
used as the keyword.

The default behavior is to match Category* or Topic* links.");

$properties["Author and Copyright Site Navigation Links"] =
new _define_commented_optional('COPYRIGHTPAGE_TITLE', "GNU General Public License", "

These will be inserted as <link rel> tags in the html header of
every page, for search engines and for browsers like Mozilla which
take advantage of link rel site navigation.

If you have your own copyright and contact information pages change
these as appropriate.");

$properties["COPYRIGHTPAGE URL"] =
new _define_commented_optional('COPYRIGHTPAGE_URL', "http://www.gnu.org/copyleft/gpl.html#SEC1", "

Other useful alternatives to consider:
<pre>
 COPYRIGHTPAGE_TITLE = \"GNU Free Documentation License\"
 COPYRIGHTPAGE_URL = \"http://www.gnu.org/copyleft/fdl.html\"
 COPYRIGHTPAGE_TITLE = \"Creative Commons License 2.0\"
 COPYRIGHTPAGE_URL = \"http://creativecommons.org/licenses/by/2.0/\"</pre>
See http://creativecommons.org/learn/licenses/ for variations");

$properties["AUTHORPAGE_TITLE"] =
    new _define_commented_optional('AUTHORPAGE_TITLE', "The PhpWiki Programming Team", "
Default Author Names");
$properties["AUTHORPAGE_URL"] =
    new _define_commented_optional('AUTHORPAGE_URL', "http://phpwiki.org/ThePhpWikiProgrammingTeam", "
Default Author URL");

$properties["TOC_FULL_SYNTAX"] =
new boolean_define_optional
('TOC_FULL_SYNTAX', 
 array('true'  => "Enabled",
       'false' => "Disabled"), "
Allow full markup in headers to be parsed by the CreateToc plugin.

If false you may not use WikiWords or [] links or any other markup in 
headers in pages with the CreateToc plugin. But if false the parsing is 
faster and more stable.");

$properties["ENABLE_MARKUP_COLOR"] =
new boolean_define_optional
('ENABLE_MARKUP_COLOR', 
 array('true'  => "Enabled",
       'false' => "Disabled"), "
If disabled the %color=... %% syntax will be disabled. Since 1.3.11
Default: true");

$properties["ENABLE_MARKUP_TEMPLATE"] =
new boolean_define_optional
('ENABLE_MARKUP_TEMPLATE', 
 array('true'  => "Enabled",
       'false' => "Disabled"), "
Enable mediawiki-style {{TemplatePage|vars=value|...}} syntax. Since 1.3.11
Default: undefined. Enabled automatically on the MonoBook theme if undefined.");

///////////////////

$properties["Part Six"] =
new part('_part6', $SEPARATOR."\n", "

Part Six (optional):
URL options -- you can probably skip this section.

For a pretty wiki (no index.php in the url) set a seperate DATA_PATH.");

$properties["Server Name"] =
new _define_commented_optional('SERVER_NAME', $_SERVER['SERVER_NAME'], "
Canonical name of the server on which this PhpWiki resides.");

$properties["Server Port"] =
new numeric_define_commented('SERVER_PORT', $_SERVER['SERVER_PORT'], "
Canonical httpd port of the server on which this PhpWiki resides.",
"onchange=\"validate_ereg('Sorry, \'%s\' is no valid port number.', '^[0-9]+$', 'SERVER_PORT', this);\"");

$properties["Script Name"] =
new _define_commented_optional('SCRIPT_NAME', $scriptname, "
Relative URL (from the server root) of the PhpWiki script.");

$properties["Data Path"] =
new _define_commented_optional('DATA_PATH', dirname($scriptname), "
URL of the PhpWiki install directory.  (You only need to set this
if you've moved index.php out of the install directory.)  This can
be either a relative URL (from the directory where the top-level
PhpWiki script is) or an absolute one.");


$properties["PhpWiki Install Directory"] =
new _define_commented_optional('PHPWIKI_DIR', dirname(__FILE__), "
Path to the PhpWiki install directory.  This is the local
filesystem counterpart to DATA_PATH.  (If you have to set
DATA_PATH, your probably have to set this as well.)  This can be
either an absolute path, or a relative path interpreted from the
directory where the top-level PhpWiki script (normally index.php)
resides.");

$properties["Use PATH_INFO"] =
new _define_selection_optional_commented('USE_PATH_INFO', 
		    array(''      => 'automatic',
			  'true'  => 'use PATH_INFO',
			  'false' => 'do not use PATH_INFO'), "
PhpWiki will try to use short urls to pages, eg 
http://www.example.com/index.php/HomePage
If you want to use urls like 
http://www.example.com/index.php?pagename=HomePage
then define 'USE_PATH_INFO' as false by uncommenting the line below.
NB:  If you are using Apache >= 2.0.30, then you may need to to use
the directive \"AcceptPathInfo On\" in your Apache configuration file
(or in an appropriate <.htaccess> file) for the short urls to work:  
See http://httpd.apache.org/docs-2.0/mod/core.html#acceptpathinfo

See also http://phpwiki.sourceforge.net/phpwiki/PrettyWiki for more ideas
on prettifying your urls.

Default: PhpWiki will try to divine whether use of PATH_INFO
is supported in by your webserver/PHP configuration, and will
use PATH_INFO if it thinks that is possible.");

$properties["Virtual Path"] =
new _define_commented_optional('VIRTUAL_PATH', '/SomeWiki', "
VIRTUAL_PATH is the canonical URL path under which your your wiki
appears. Normally this is the same as dirname(SCRIPT_NAME), however
using e.g. seperate starter scripts, apaches mod_actions (or mod_rewrite), 
you can make it something different.

If you do this, you should set VIRTUAL_PATH here or in the starter scripts.

E.g. your phpwiki might be installed at at /scripts/phpwiki/index.php,
but you've made it accessible through eg. /wiki/HomePage.

One way to do this is to create a directory named 'wiki' in your
server root. The directory contains only one file: an .htaccess
file which reads something like:
<pre>
    Action x-phpwiki-page /scripts/phpwiki/index.php
    SetHandler x-phpwiki-page
    DirectoryIndex /scripts/phpwiki/index.php
</pre>
In that case you should set VIRTUAL_PATH to '/wiki'.

(VIRTUAL_PATH is only used if USE_PATH_INFO is true.)
");

///////////////////

$properties["Part Seven"] =
new part('_part7', $SEPARATOR."\n", "

Part Seven:

Miscellaneous settings
");

$properties["Strict Mailable Pagedumps"] =
new boolean_define_optional
('STRICT_MAILABLE_PAGEDUMPS', 
 array('false' => "binary",
       'true'  => "quoted-printable"),
"
If you define this to true, (MIME-type) page-dumps (either zip dumps,
or \"dumps to directory\" will be encoded using the quoted-printable
encoding.  If you're actually thinking of mailing the raw page dumps,
then this might be useful, since (among other things,) it ensures
that all lines in the message body are under 80 characters in length.

Also, setting this will cause a few additional mail headers
to be generated, so that the resulting dumps are valid
RFC 2822 e-mail messages.

Probably, you can just leave this set to false, in which case you get
raw ('binary' content-encoding) page dumps.");

$properties["HTML Dump Filename Suffix"] =
new _define_optional('HTML_DUMP_SUFFIX', ".html", "
Here you can change the filename suffix used for XHTML page dumps.
If you don't want any suffix just comment this out.");

$properties["Default local Dump Directory"] =
new _define_optional('DEFAULT_DUMP_DIR', "/tmp/wikidump", "
Specify the default directory for local backups.");

$properties["Pagename of Recent Changes"] =
new _define_optional('RECENT_CHANGES', 'RecentChanges', "
Page name of RecentChanges page.  Used for RSS Auto-discovery.");

$properties["Disable HTTP Redirects"] =
new boolean_define_commented_optional
('DISABLE_HTTP_REDIRECT',
 array('false' => 'Enable HTTP Redirects',
       'true' => 'Disable HTTP Redirects'),
"
(You probably don't need to touch this.)

PhpWiki uses HTTP redirects for some of it's functionality.
(e.g. after saving changes, PhpWiki redirects your browser to
view the page you just saved.)

Some web service providers (notably free European Lycos) don't seem to
allow these redirects.  (On Lycos the result in an \"Internal Server Error\"
report.)  In that case you can set DISABLE_HTTP_REDIRECT to true.
(In which case, PhpWiki will revert to sneakier tricks to try to
redirect the browser...)");

$properties["Disable GETIMAGESIZE"] =
new boolean_define_commented_optional
('DISABLE_GETIMAGESIZE',
 array('false' => 'Enable',
       'true'  => 'Disable'), "
Set GETIMAGESIZE to disabled, if your php fails to calculate the size on 
inlined images, or you don't want to disable too small images to be inlined.

Per default too small ploaded or external images are not displayed, 
to prevent from external 1 pixel spam.");

$properties["EDITING_POLICY"] =
  new _define_optional('EDITING_POLICY', "EditingPolicy", "
An interim page which gets displayed on every edit attempt, if it exists.");

$properties["ENABLE_MODERATEDPAGE_ALL"] =
new boolean_define_commented_optional
('ENABLE_MODERATEDPAGE_ALL',
 array('false' => 'Disable',
       'true'  => 'Enable'), "
");

$properties["FORTUNE_DIR"] =
  new _define_commented_optional('FORTUNE_DIR', "/usr/share/fortune", "
");
$properties["DBADMIN_USER"] =
  new _define_commented_optional('DBADMIN_USER', "", "
");
$properties["DBADMIN_PASSWD"] =
  new _define_commented_optional('DBADMIN_PASSWD', "", "
");
$properties["USE_EXTERNAL_HTML2PDF"] =
  new _define_commented_optional('USE_EXTERNAL_HTML2PDF', "htmldoc --quiet --format pdf14 --no-toc --no-title %s", "
");

$properties["Part Seven A"] =
new part('_part7a', $SEPARATOR."\n", "

Part Seven A:

Cached Plugin Settings. (pear Cache)
");

$properties["pear Cache cache directory"] =
new _define_commented_optional('PLUGIN_CACHED_CACHE_DIR', "/tmp/cache", "
Should be writable to the webserver.");
$properties["pear Cache Filename Prefix"] =
new _define_optional('PLUGIN_CACHED_FILENAME_PREFIX', "phpwiki", "");
$properties["pear Cache LOWWATER"] =
$properties["pear Cache MAXARGLEN"] =
new numeric_define_optional('PLUGIN_CACHED_MAXARGLEN', "1000", "
max. generated url length.");
$properties["pear Cache IMGTYPES"] =
new list_define('PLUGIN_CACHED_IMGTYPES', "png|gif|gd|gd2|jpeg|wbmp|xbm|xpm", "
Handle those image types via GD handles. Check your GD supported image types.");

$end = "\n".$SEPARATOR."\n";

// performance hack
text_from_dist("_MAGIC_CLOSE_FILE");

// end of configuration options
///////////////////////////////
// begin class definitions

/**
 * A basic config-dist.ini configuration line in the form of a variable. 
 * (not needed anymore, we have only defines)
 *
 * Produces a string in the form "$name = value;"
 * e.g.:
 * $WikiNameRegexp = "value";
 */
class _variable {

    var $config_item_name;
    var $default_value;
    var $description;
    var $prefix;
    var $jscheck;

    function __construct($config_item_name, $default_value, $description = '', $jscheck = '') {
        $this->config_item_name = $config_item_name;
	if (!$description)
	    $description = text_from_dist($config_item_name);
        $this->description = $description;
	// TODO: get boolean default value from config-default.ini
	if (defined($config_item_name) 
	    and !preg_match("/(selection|boolean)/", get_class($this))
	    and !preg_match("/(SCRIPT_NAME|VIRTUAL_PATH)/", $config_item_name))
	    $this->default_value = constant($config_item_name); // ignore given default value
	elseif ($config_item_name == $default_value)
	    $this->default_value = '';
	else
	    $this->default_value = $default_value;
	$this->jscheck = $jscheck;
        if (preg_match("/variable/i", get_class($this)))
	    $this->prefix = "\$";
	elseif (preg_match("/ini_set/i", get_class($this)))
            $this->prefix = "ini_get: ";
        else
	    $this->prefix = "";
    }

    function value() {
      if (isset($_POST[$this->config_item_name]))
          return $_POST[$this->config_item_name];
      else 
          return $this->default_value;
    }

    function _config_format($value) {
	return '';
        $v = $this->get_config_item_name();
        // handle arrays: a|b --> a['b']
        if (strpos($v, '|')) {
            list($a, $b) = explode('|', $v);
            $v = sprintf("%s['%s']", $a, $b);
        }
        if (preg_match("/[\"']/", $value))
            $value = '"' . $value . '"';
        return sprintf("%s = \"%s\"", $v, $value);
    }

    function get_config_item_name() {
        return $this->config_item_name;
    }

    function get_config_item_id() {
        return str_replace('|', '-', $this->config_item_name);
    }

    function get_config_item_header() {
       if (strchr($this->config_item_name,'|')) {
          list($var,$param) = explode('|',$this->config_item_name);
	  return "<b>" . $this->prefix . $var . "['" . $param . "']</b><br />";
       }
       elseif ($this->config_item_name[0] != '_')
	  return "<b>" . $this->prefix . $this->config_item_name . "</b><br />";
       else 
          return '';
    }

    function _get_description() {
        return $this->description;
    }

    function _get_config_line($posted_value) {
        return "\n" . $this->_config_format($posted_value);
    }

    function get_config($posted_value) {
        $d = stripHtml($this->_get_description());
        $d = str_replace("\n", "\n; ", $d) . $this->_get_config_line($posted_value) ."\n";
        return $d;
    }

    function get_instructions($title) {
        global $tdwidth;
        $i = "<h3>" . $title . "</h3>\n    " . nl2p($this->_get_description()) . "\n";
        return "<tr>\n<td width=\"$tdwidth\" class=\"instructions\">\n" . $i . "</td>\n";
    }

    function get_html() {
    	$size = strlen($this->default_value) > 45 ? 90 : 50;
	return $this->get_config_item_header() . 
	    "<input type=\"text\" size=\"$50\" name=\"" . $this->get_config_item_name() . "\" value=\"" . htmlspecialchars($this->default_value) . "\" " . 
	    $this->jscheck . " />" . "<p id=\"" . $this->get_config_item_id() . "\" style=\"color: green\">Input accepted.</p>";
    }
}

class unchangeable_variable
extends _variable {
    function _config_format($value) {
        return "";
    }
    // function get_html() { return false; }
    function get_html() {
	return $this->get_config_item_header() . 
	"<em>Not editable.</em>" . 
	"<pre>" . $this->default_value."</pre>";
    }
    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        return "${n}".$this->default_value;
    }
    function get_instructions($title) {
	global $tdwidth;
        $i = "<h3>" . $title . "</h3>\n    " . nl2p($this->_get_description()) . "\n";
        // $i .= "<em>Not editable.</em><br />\n<pre>" . $this->default_value."</pre>";
        return '<tr><td width="100%" class="unchangeable-variable-top" colspan="2">'."\n".$i."</td></tr>\n" 
	. '<tr style="border-top: none;"><td class="unchangeable-variable-left" width="'.$tdwidth.'">&nbsp;</td>';
    }
}

class unchangeable_define
extends unchangeable_variable {
    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        if (!$posted_value)
            $posted_value = $this->default_value;
        return "${n}".$this->_config_format($posted_value);
    }
    function _config_format($value) {
        return sprintf("%s = \"%s\"", $this->get_config_item_name(), $value);
    }
}
class unchangeable_ini_set
extends unchangeable_variable {
    function _config_format($value) {
        return "";
    }
}

class _variable_selection
extends _variable {
    function value() {
        if (!empty($_POST[$this->config_item_name]))
            return $_POST[$this->config_item_name];
        else {
	    list($option, $label) = each($this->default_value);
            return $option;
        }
    }
    function get_html() {
	$output = $this->get_config_item_header();
        $output .= '<select name="' . $this->get_config_item_name() . "\">\n";
        /* The first option is the default */
	$values = $this->default_value;
	if (defined($this->get_config_item_name()))
	    $this->default_value = constant($this->get_config_item_name());
	else
	    $this->default_value = null;
        while(list($option, $label) = each($values)) {
	    if (!is_null($this->default_value) and $this->default_value === $option)
		$output .= "  <option value=\"$option\" selected=\"selected\">$label</option>\n";
	    else
		$output .= "  <option value=\"$option\">$label</option>\n";
        }
        $output .= "</select>\n";
        return $output;
    }
}


class _define
extends _variable {
    function _config_format($value) {
        return sprintf("%s = \"%s\"", $this->get_config_item_name(), $value);
    }
    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        if ($posted_value == '')
            return "${n};" . $this->_config_format("");
        else
            return "${n}" . $this->_config_format($posted_value);
    }
    function get_html() {
    	$size = strlen($this->default_value) > 45 ? 90 : 50;
	return $this->get_config_item_header() 
            . "<input type=\"text\" size=\"$size\" name=\"" . htmlentities($this->get_config_item_name()) 
            . "\" value=\"" . htmlentities($this->default_value) . "\" {$this->jscheck} />" 
            . "<p id=\"" . $this->get_config_item_id() . "\" style=\"color: green\">Input accepted.</p>";
    }
}

class _define_commented
extends _define {
    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        if ($posted_value == $this->default_value)
            return "${n};" . $this->_config_format($posted_value);
        elseif ($posted_value == '')
            return "${n};" . $this->_config_format("");
        else
            return "${n}" . $this->_config_format($posted_value);
    }
}

/** 
 * We don't use _optional anymore, because INI-style config's don't need that. 
 * IniConfig.php does the optional logic now.
 * But we use _optional for config-default.ini options
 */
class _define_commented_optional
extends _define_commented { }

class _define_optional
extends _define { }

class _define_notempty
extends _define {
    function get_html() {
	$s = $this->get_config_item_header() 
            . "<input type=\"text\" size=\"50\" name=\"" . $this->get_config_item_name() 
            . "\" value=\"" . $this->default_value . "\" {$this->jscheck} />";
        if (empty($this->default_value))
	    return $s . "<p id=\"" . $this->get_config_item_id() . "\" style=\"color: red\">Cannot be empty.</p>";
	else
	    return $s . "<p id=\"" . $this->get_config_item_id() . "\" style=\"color: green\">Input accepted.</p>";
    }
}

class _variable_commented
extends _variable {
    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        if ($posted_value == $this->default_value)
            return "${n};" . $this->_config_format($posted_value);
        elseif ($posted_value == '')
            return "${n};" . $this->_config_format("");
        else
            return "${n}" . $this->_config_format($posted_value);
    }
}

class numeric_define
extends _define {

    function __construct($config_item_name, $default_value, $description = '', $jscheck = '') {
        parent::__construct($config_item_name, $default_value, $description, $jscheck);
        if (!$jscheck)
            $this->jscheck = "onchange=\"validate_ereg('Sorry, \'%s\' is not an integer.', '^[-+]?[0-9]+$', '" . $this->get_config_item_name() . "', this);\"";
    }
    function _config_format($value) {
        //return sprintf("define('%s', %s);", $this->get_config_item_name(), $value);
        return sprintf("%s = %s", $this->get_config_item_name(), $value);
    }
    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        if ($posted_value == '')
            return "${n};" . $this->_config_format('0');
        else
            return "${n}" . $this->_config_format($posted_value);
    }
}

class numeric_define_optional
extends numeric_define {}

class numeric_define_commented
extends numeric_define {
    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        if ($posted_value == $this->default_value)
            return "${n};" . $this->_config_format($posted_value);
        elseif ($posted_value == '')
            return "${n};" . $this->_config_format('0');
        else
            return "${n}" . $this->_config_format($posted_value);
    }
}

class _define_selection
extends _variable_selection {
    function _config_format($value) {
        return sprintf("%s = %s", $this->get_config_item_name(), $value);
    }
    function _get_config_line($posted_value) {
        return _define::_get_config_line($posted_value);
    }
    function get_html() {
        return _variable_selection::get_html();
    }
}

class _define_selection_optional
extends _define_selection { }

class _variable_selection_optional
extends _variable_selection { }

class _define_selection_optional_commented
extends _define_selection_optional { 
    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        if ($posted_value == $this->default_value)
            return "${n};" . $this->_config_format($posted_value);
        elseif ($posted_value == '')
            return "${n};" . $this->_config_format("");
        else
            return "${n}" . $this->_config_format($posted_value);
    }
}

class _define_password
extends _define {

    function __construct($config_item_name, $default_value, $description = '', $jscheck = '') {
    	if ($config_item_name == $default_value) $default_value = '';
        parent::__construct($config_item_name, $default_value, $description, $jscheck);
        if (!$jscheck)
            $this->jscheck = "onchange=\"validate_ereg('Sorry, \'%s\' cannot be empty.', '^.+$', '" 
		. $this->get_config_item_name() . "', this);\"";
    }
    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        if ($posted_value == '') {
            $p = "${n};" . $this->_config_format("");
            $p .= "\n; If you used the passencrypt.php utility to encode the password";
            $p .= "\n; then uncomment this line:";
            $p .= "\n;ENCRYPTED_PASSWD = true";
            return $p;
        } else {
            if (function_exists('crypt')) {
                $salt_length = max(CRYPT_SALT_LENGTH,
                                    2 * CRYPT_STD_DES,
                                    9 * CRYPT_EXT_DES,
                                   12 * CRYPT_MD5,
                                   16 * CRYPT_BLOWFISH);
                // generate an encrypted password
                $crypt_pass = crypt($posted_value, rand_ascii($salt_length));
                $p = "${n}" . $this->_config_format($crypt_pass);
                return $p . "\nENCRYPTED_PASSWD = true";
            } else {
                $p = "${n}" . $this->_config_format($posted_value);
                $p .= "\n; Encrypted passwords cannot be used:";
                $p .= "\n; 'function crypt()' not available in this version of php";
                $p .= "\nENCRYPTED_PASSWD = false";
                return $p;
            }
        }
    }
    function get_html() {
        return _variable_password::get_html();
    }
}

class _define_password_optional
extends _define_password { 

    function __construct($config_item_name, $default_value, $description = '', $jscheck = '') {
    	if ($config_item_name == $default_value) $default_value = '';
        parent::__construct($config_item_name, $default_value, $description, $jscheck);
    }

    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        if ($posted_value == '') {
            return "${n};" . $this->_config_format("");
        } else {
	    return "${n}" . $this->_config_format($posted_value);
        }
    }

}

class _define_password_commented_optional
extends _define_password_optional { }

class _variable_password
extends _variable {
    function __construct($config_item_name, $default_value, $description = '', $jscheck = '') {
    	if ($config_item_name == $default_value) $default_value = '';
        parent::__construct($config_item_name, $default_value, $description, $jscheck);
        if (!$jscheck)
            $this->jscheck = "onchange=\"validate_ereg('Sorry, \'%s\' cannot be empty.', '^.+$', '" . $this->get_config_item_name() . "', this);\"";
    }
    function get_html() {
	$s = $this->get_config_item_header();
        if (isset($_POST['create']) or isset($_GET['create'])) {
	    $new_password = random_good_password();
	    $this->default_value = $new_password;
	    $s .= "Created password: <strong>$new_password</strong><br />&nbsp;<br />";
	}
	// dont re-encrypt already encrypted passwords
	$value = $this->value();
	$encrypted = !empty($GLOBALS['properties']["Encrypted Passwords"]) and 
	             $GLOBALS['properties']["Encrypted Passwords"]->value();
	if (empty($value))
	    $encrypted = false;
        $s .= "<input type=\"". ($encrypted ? "text" : "password") . "\" name=\"" . $this->get_config_item_name()
           . "\" value=\"" . $value . "\" {$this->jscheck} />" 
           . "&nbsp;&nbsp;<input type=\"submit\" name=\"create\" value=\"Create Random Password\" />";
	if (empty($value))
	    $s .= "<p id=\"" . $this->get_config_item_id() . "\" style=\"color: red\">Cannot be empty.</p>";
	elseif (strlen($this->default_value) < 4)
	    $s .= "<p id=\"" . $this->get_config_item_id() . "\" style=\"color: red\">Must be longer than 4 chars.</p>";
	else
	    $s .= "<p id=\"" . $this->get_config_item_id() . "\" style=\"color: green\">Input accepted.</p>";
	return $s;
    }
}

class list_variable
extends _variable {
    function _get_config_line($posted_value) {
        // split the phrase by any number of commas or space characters,
        // which include " ", \r, \t, \n and \f
        $list_values = preg_split("/[\s,]+/", $posted_value, -1, PREG_SPLIT_NO_EMPTY);
        $list_values = join("|", $list_values);
        return _variable::_get_config_line($list_values);
    }
    function get_html() {
        $list_values = explode("|", $this->default_value);
        $rows = max(3, count($list_values) +1);
        $list_values = join("\n", $list_values);
        $ta = $this->get_config_item_header();
	$ta .= "<textarea cols=\"18\" rows=\"". $rows ."\" name=\"".$this->get_config_item_name()."\" {$this->jscheck}>";
        $ta .= $list_values . "</textarea>";
	$ta .= "<p id=\"" . $this->get_config_item_id() . "\" style=\"color: green\">Input accepted.</p>";
        return $ta;
    }
}

class list_define
extends _define {
    function _get_config_line($posted_value) {
        $list_values = preg_split("/[\s,]+/", $posted_value, -1, PREG_SPLIT_NO_EMPTY);
        $list_values = join("|", $list_values);
        return _variable::_get_config_line($list_values);
    }
    function get_html() {
        $list_values = explode("|", $this->default_value);
        $rows = max(3, count($list_values) +1);
        $list_values = join("\n", $list_values);
        $ta = $this->get_config_item_header();
	$ta .= "<textarea cols=\"18\" rows=\"". $rows ."\" name=\"".$this->get_config_item_name()."\" {$this->jscheck}>";
        $ta .= $list_values . "</textarea>";
	$ta .= "<p id=\"" . $this->get_config_item_id() . "\" style=\"color: green\">Input accepted.</p>";
        return $ta;
    }
}

class array_variable
extends _variable {
    function _config_format($value) {
        return sprintf("%s = \"%s\"", $this->get_config_item_name(), 
                       is_array($value) ? join(':', $value) : $value);
    }
    function _get_config_line($posted_value) {
        // split the phrase by any number of commas or space characters,
        // which include " ", \r, \t, \n and \f
        $list_values = preg_split("/[\s,]+/", $posted_value, -1, PREG_SPLIT_NO_EMPTY);
        if (!empty($list_values)) {
            $list_values = "'".join("', '", $list_values)."'";
            return "\n" . $this->_config_format($list_values);
        } else
            return "\n;" . $this->_config_format('');
    }
    function get_html() {
        $list_values = join("\n", $this->default_value);
        $rows = max(3, count($this->default_value) +1);
        $ta = $this->get_config_item_header();
        $ta .= "<textarea cols=\"18\" rows=\"". $rows ."\" name=\"".$this->get_config_item_name()."\" {$this->jscheck}>";
        $ta .= $list_values . "</textarea>";
	$ta .= "<p id=\"" . $this->get_config_item_id() . "\" style=\"color: green\">Input accepted.</p>";
        return $ta;
    }
}

class array_define
extends _define {
    function _config_format($value) {
        return sprintf("%s = \"%s\"", $this->get_config_item_name(), 
                       is_array($value) ? join(' : ', $value) : $value);
    }
    function _get_config_line($posted_value) {
        // split the phrase by any number of commas or space characters,
        // which include " ", \r, \t, \n and \f
        $list_values = preg_split("/[\s,:]+/", $posted_value, -1, PREG_SPLIT_NO_EMPTY);
        if (!empty($list_values)) {
            $list_values = join(" : ", $list_values);
            return "\n" . $this->_config_format($list_values);
        } else
            return "\n;" . $this->_config_format('');
    }
    function get_html() {
	if (!$this->default_value)
	    $this->default_value = array();
	elseif (is_string($this->default_value))
	    $this->default_value = preg_split("/[\s,:]+/", $this->default_value, -1, PREG_SPLIT_NO_EMPTY);
	$list_values = join(" : \n", $this->default_value);
        $rows = max(3, count($this->default_value) + 1);
        $ta = $this->get_config_item_header();
        $ta .= "<textarea cols=\"18\" rows=\"". $rows ."\" name=\"".$this->get_config_item_name()."\" {$this->jscheck}>";
        $ta .= $list_values . "</textarea>";
	$ta .= "<p id=\"" . $this->get_config_item_id() . "\" style=\"color: green\">Input accepted.</p>";
        return $ta;
    }
}

class boolean_define
extends _define {
    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        return "${n}" . $this->_config_format($posted_value);
    }
    function _config_format($value) {
        if (strtolower(trim($value)) == 'false')
            $value = false;
        return sprintf("%s = %s", $this->get_config_item_name(),
                       (bool)$value ? 'true' : 'false');
    }
    function get_html() {
        $output = $this->get_config_item_header();
	$name = $this->get_config_item_name();
        $output .= '<select name="' . $name . "\" {$this->jscheck}>\n";
	$values = $this->default_value;
	if (defined($name))
	    $this->default_value = constant($name);
	else {
	    $this->default_value = null;
	    list($option, $label) = each($values);
	    $output .= "  <option value=\"$option\" selected=\"selected\">$label</option>\n";
	}
        /* There can usually, only be two options, there can be
         * three options in the case of a boolean_define_commented_optional */
        while (list($option, $label) = each($values)) {
	    if (!is_null($this->default_value) and $this->default_value === $option)
		$output .= "  <option value=\"$option\" selected=\"selected\">$label</option>\n";
	    else
		$output .= "  <option value=\"$option\">$label</option>\n";
	}
        $output .= "</select>\n";
        return $output;
    }
}

class boolean_define_optional
extends boolean_define {}

class boolean_define_commented
extends boolean_define {
    function _get_config_line($posted_value) {
        if ($this->description)
            $n = "\n";
        list($default_value, $label) = each($this->default_value);
        if ($posted_value == $default_value)
            return "${n};" . $this->_config_format($posted_value);
        elseif ($posted_value == '')
            return "${n};" . $this->_config_format('false');
        else
            return "${n}" . $this->_config_format($posted_value);
    }
}

class boolean_define_commented_optional
extends boolean_define_commented {}

class part
extends _variable {
    function value () { return; }
    function get_config($posted_value) {
        $d = stripHtml($this->_get_description());
        global $SEPARATOR;
        return "\n".$SEPARATOR . str_replace("\n", "\n; ", $d) ."\n".$this->default_value;
    }
    function get_instructions($title) {
	$id = preg_replace("/\W/","",$this->config_item_name);
	$group_name = preg_replace("/\W/","",$title);
	$i = "<tr class=\"header\" id=\"$id\">\n<td class=\"part\" width=\"100%\" colspan=\"2\" bgcolor=\"#eeeeee\">\n";
        $i .= "<h2>" . $title . "</h2>\n    " . nl2p($this->_get_description()) ."\n";
	$i .= "<p><a href=\"javascript:toggle_group('$id')\" id=\"{$id}_text\">Hide options.</a></p>";
        return  $i ."</td>\n";
    }
    function get_html() {
        return "";
    }
}

// html utility functions
function nl2p($text) {
  preg_match_all("@\s*(<pre>.*?</pre>|<dl>.*?</dl>|.*?(?=\n\n|<pre>|<dl>|$))@s",
                 $text, $m, PREG_PATTERN_ORDER);

  $text = '';
  foreach ($m[1] as $par) {
    if (!($par = trim($par)))
      continue;
    if (!preg_match('/^<(pre|dl)>/', $par))
      $par = "<p>$par</p>";
    $text .= $par;
  }
  return $text;
}

function text_from_dist($var) {
    static $distfile = 0;
    static $f;
    
    if (!$distfile) {
    	$sep = (substr(PHP_OS,0,3) == 'WIN' ? '\\' : '/');
	$distfile = dirname(__FILE__) . $sep . "config" . $sep . "config-dist.ini";
	$f = fopen($distfile, "r");
    }
    if ($var == '_MAGIC_CLOSE_FILE') {
	fclose($f);
	return;
    }
    // if all vars would be in natural order as in the config-dist this would not be needed.
    fseek($f, 0); 
    $par = "\n";
    while (!feof($f)) {
	$s = fgets($f);
	if (preg_match("/^; \w/", $s)) {
	    $par .= (substr($s,2) . " ");
	} elseif (preg_match("/^;\s*$/", $s)) {
	    $par .= "\n\n";
	}
	if (preg_match("/^;?".preg_quote($var)."\s*=/", $s))
	    return $par;
	if (preg_match("/^\s*$/", $s)) // new paragraph
	    $par = "\n";
    }
    return '';
}

function stripHtml($text) {
        $d = str_replace("<pre>", "", $text);
        $d = str_replace("</pre>", "", $d);
        $d = str_replace("<dl>", "", $d);
        $d = str_replace("</dl>", "", $d);
        $d = str_replace("<dt>", "", $d);
        $d = str_replace("</dt>", "", $d);
        $d = str_replace("<dd>", "", $d);
        $d = str_replace("</dd>", "", $d);
        $d = str_replace("<p>", "", $d);
        $d = str_replace("</p>", "", $d);
        //restore html entities into characters
        // http://www.php.net/manual/en/function.htmlentities.php
        $trans = get_html_translation_table (HTML_ENTITIES);
        $trans = array_flip ($trans);
        $d = strtr($d, $trans);
        return $d;
}

include_once(dirname(__FILE__)."/lib/stdlib.php");

////
// Function to create better user passwords (much larger keyspace),
// suitable for user passwords.
// Sequence of random ASCII numbers, letters and some special chars.
// Note: There exist other algorithms for easy-to-remember passwords.
function random_good_password ($minlength = 5, $maxlength = 8) {
  $newpass = '';
  // assume ASCII ordering (not valid on EBCDIC systems!)
  $valid_chars = "!#%&+-.0123456789=@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz";
  $start = ord($valid_chars);
  $end   = ord(substr($valid_chars,-1));
  better_srand();
  if (function_exists('mt_rand')) // mersenne twister
      $length = mt_rand($minlength, $maxlength);
  else	// the usually bad glibc rand()
      $length = rand($minlength, $maxlength);
  while ($length > 0) {
      if (function_exists('mt_rand'))
	  $newchar = mt_rand($start, $end);
      else
	  $newchar = rand($start, $end);
      if (! strrpos($valid_chars,$newchar) ) continue; // skip holes
      $newpass .= sprintf("%c", $newchar);
      $length--;
  }
  return($newpass);
}

// debugging
function printArray($a) {
    echo "<hr />\n<pre>\n";
    print_r($a);
    echo "\n</pre>\n<hr />\n";
}

// end of class definitions
/////////////////////////////
// begin auto generation code

if (!function_exists('is_a')) {
  function is_a($object, $class) {
    $class = strtolower($class);
    return (get_class($object) == $class) or is_subclass_of($object, $class);
  }
}


if (!empty($_POST['action'])
    and $_POST['action'] == 'make_config'
    and !empty($_POST['ADMIN_USER'])
    and !empty($_POST['ADMIN_PASSWD'])
    )
{

    $timestamp = date ('dS of F, Y H:i:s');

    $config = "
; This is a local configuration file for PhpWiki.
; It was automatically generated by the configurator script
; on the $timestamp.
;
; $preamble
";

    $posted = $_POST;

    foreach ($properties as $option_name => $a) {
        $posted_value = stripslashes($posted[$a->config_item_name]);
        $config .= $properties[$option_name]->get_config($posted_value);
    }

    $config .= $end;

    if (is_writable($fs_config_file)) {
      // We first check if the config-file exists.
      if (file_exists($fs_config_file)) {
        // We make a backup copy of the file
        $new_filename = preg_replace('/\.ini$/', '-' . time() . '.ini', $fs_config_file);
        if (@copy($fs_config_file, $new_filename)) {
            $fp = @fopen($fs_config_file, 'w');
        }
      } else {
        $fp = @fopen($fs_config_file, 'w');
      }
    }
    else {
      $fp = false;
    }
    
    if ($fp) {
        fputs($fp, $config);
        fclose($fp);
        echo "<p>The configuration was written to <code><b>$config_file</b></code>.</p>\n";
        if ($new_filename) {
            echo "<p>A backup was made to <code><b>$new_filename</b></code>.</p>\n";
        } else {
            ; //echo "<p><strong>You must rename or copy this</strong> <code><b>$config_file</b></code> <strong>file to</strong> <code><b>config/config.ini</b></code>.</p>\n";
        }
    } else {
        echo "<p>The configuration file could <b>not</b> be written.<br />\n",
	    " You should copy the above configuration to a file, ",
	    "and manually save it as <code><b>config/config.ini</b></code>.</p>\n";
    }

    echo "<hr />\n<p>Here's the configuration file based on your answers:</p>\n";
    echo "<form method=\"get\" action=\"", $configurator, "\">\n";
    echo "<textarea id='config-output' readonly='readonly' style='width:100%;' rows='30' cols='100'>\n";
    echo htmlentities($config);
    echo "</textarea></form>\n";
    echo "<hr />\n";

    echo "<p>To make any corrections, <a href=\"configurator.php\">edit the settings again</a>.</p>\n";

} else { // first time or create password
    $posted = $_POST;
    // No action has been specified - we make a form.

    if (!empty($_GET['start_debug']))
    	$configurator .= ("?start_debug=" . $_GET['start_debug']);
    echo '
<form action="',$configurator,'" method="post">
<input type="hidden" name="action" value="make_config" />
<table cellpadding="4" cellspacing="0">
';

    while (list($property, $obj) = each($properties)) {
        echo $obj->get_instructions($property);
        if ($h = $obj->get_html()) {
            if (defined('DEBUG') and DEBUG)  $h = get_class($obj) . "<br />\n" . $h;
            echo "<td>".$h."</td>\n";
        }
	echo '</tr>';
    }

    echo '
</table>
<p><input type="submit" id="submit" value="Save ',$config_file,'" /> <input type="reset" value="Clear" /></p>
</form>
';
}
?>
</body>
</html>