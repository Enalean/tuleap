<?php
/**
 * GitPHP Config defaults
 *
 * Lists all the config options and their default values
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Config
 */


/**
 * This file is not usable as an actual config file.
 * To use a config value you should copy the value
 * into gitphp.conf.php
 */
throw new Exception('The defaults file should not be used as your config.');


/*********************************************************
 * Projects
 */

/*
 * projectroot
 * Full directory on server where projects are located
 */
//$gitphp_conf['projectroot'] = '/pub/gitprojects/';

/*
 * exportedonly
 * When listing all projects in the project root,
 * (not specifying any projects manually or using a project list file)
 * set this to true to only allow repositories with the
 * special file git-daemon-export-ok (see the git-daemon man page)
 */
$gitphp_conf['exportedonly'] = false;



/*********************************************************
 * Appearance
 */

/*
 * locale
 * This is the default locale/language used in the interface.
 * The locale must exist in include/resources/locale
 */
$gitphp_conf['locale'] = 'en_US';

/*
 * title
 * The string that will be used as the page title
 * The variable '$gitphp_appstring' will expand to
 * the name (gitphp) and version
 * The variable '$gitphp_version' will expand to the
 * version number only
 */
$gitphp_conf['title'] = "$gitphp_appstring";

/*
 * homelink
 * This is the text of the link in the upper left corner
 * that takes you back to the project list.
 */
$gitphp_conf['homelink'] = 'projects';

/*
 * cloneurl
 * Sets the base clone url to display for a project.
 * This is the publicly-accessible url of the projectroot
 * that gets prepended to the project path to create the clone
 * url.  It can be any format, for example:
 *
 * http://server.com/
 * ssh://server.com/git/
 * git://server.com/gitprojects/
 * 
 * If left blank/commented, no clone url will display.
 */
$gitphp_conf['cloneurl'] = 'http://localhost/git/';

/*
 * pushurl
 * Sets the base push url to display for a project.
 * Works the same as cloneurl.
 */
$gitphp_conf['pushurl'] = 'ssh://localhost/git/';

/*
 * bugpattern
 * Sets the regular expression to use to find bug number
 * references in log messages.  The pattern should have a
 * group that extracts just the bug ID to pass to the 
 * bug tracker.
 * For example, '/#([0-9+)/' will recognize any number
 * with a '#' in front of it, and groups the numeric part
 * only.  Another common example is '/bug:([0-9]+)/' to
 * extract bug numbers with 'bug:' in front of them.
 */
//$gitphp_conf['bugpattern'] = '/#([0-9]+)/';

/*
 * bugurl
 * Sets the URL for the bug tracker.  This URL must have
 * a backreference to the group in the bug pattern that
 * contains the ID.  For example, ${1} uses the first
 * group.
 */
//$gitphp_conf['bugurl'] = 'http://localhost/mantis/view.php?id=${1}';

/*
 * self
 * This is the path to the script that will be inserted
 * in urls.  If you leave this blank/commented the script
 * will try to guess the correct URL, but you can override
 * it here if it's not being guessed correctly.
 */
$gitphp_conf['self'] = 'http://localhost/gitphp/';

/*
 * stylesheet
 * Path to look and feel (skin) stylesheet
 */
$gitphp_conf['stylesheet'] = 'gitphpskin.css';

/*
 * javascript
 * Toggles on javascript features
 */
$gitphp_conf['javascript'] = true;



/*********************************************************
 * Features
 */

/*
 * compat
 * Set this to true to turn on compatibility mode.  This will cause
 * GitPHP to rely more on the git executable for loading data,
 * which will bypass some of the limitations of PHP at the expense
 * of performance.
 * Turn this on if you are experiencing issues viewing data for
 * your projects.
 */
$gitphp_conf['compat'] = false;

/**
 * largeskip
 * When GitPHP is reading through the history for pages of the shortlog/log
 * beyond the first, it needs to read from the tip but skip a number of commits
 * for the previous pages.  The more commits it needs to skip, the longer it takes.
 * Calling the git executable is faster when skipping a large number of commits,
 * ie reading a log page significantly beyond the first.  This determines
 * the threshold at which GitPHP will fall back to using the git exe for the log.
 * Currently each log page shows 100 commits, so this would be calculated at
 * page number * 100.  So for example at the default of 200, pages 0-2 would be
 * loaded natively and pages 3+ would fall back on the git exe.
 */
$gitphp_conf['largeskip'] = 200;

/*
 * compressformat
 * Indicates what kind of compression will be done on the
 * snapshot archive.  Recognized settings are:
 *
 * GITPHP_COMPRESS_BZ2 - create a tar.bz2 file (php must have bz2 support)
 * GITPHP_COMPRESS_GZ - create a tar.gz file (php must have gzip support)
 * GITPHP_COMPRESS_ZIP - create a zip file
 *
 * Any other setting, or no setting, will create uncompressed tar archives
 * If you choose a compression format and your php does not support it,
 * gitphp will fall back to uncompressed tar archives
 */
$gitphp_conf['compressformat'] = GITPHP_COMPRESS_ZIP;

/*
 * compresslevel
 * Sets the compression level for snapshots.  Ranges from 1-9, with
 * 9 being the most compression but requiring the most processing
 * (bzip defaults to 4, gzip defaults to -1)
 */
$gitphp_conf['compresslevel'] = 9;

/*
 * geshi
 * Run blob output through geshi syntax highlighting
 * and line numbering
 */
$gitphp_conf['geshi'] = true;

/*
 * search
 * Set this to false to disable searching
 */
$gitphp_conf['search'] = true;

/*
 * filesearch
 * Set this to false to disable searching within files
 * (it can be resource intensive)
 */
$gitphp_conf['filesearch'] = true;

/*
 * filemimetype
 * Attempt to read the file's mimetype when displaying
 * (for example, displaying an image as an actual image
 *  in a browser)
 * This requires either PHP >= 5.3.0, PECL fileinfo, or
 * Linux
 */
$gitphp_conf['filemimetype'] = true;




/*********************************************************
 * Executable/filesystem options
 * Important to check if you're running windows
 */

/*
 * gitbin
 * Path to git binary
 * For example, /usr/bin/git on Linux
 * or C:\\Program Files\\Git\\bin\\git.exe on Windows
 * with msysgit.  You can also omit the full path and just
 * use the executable name to search the user's $PATH.
 * Note: Versions of PHP below 5.2 have buggy handling of spaces
 * in paths.  Use the 8.3 version of the filename if you're
 * having trouble, e.g. C:\\Progra~1\\Git\\bin\\git.exe
 */
// Linux:
$gitphp_conf['gitbin'] = 'git';
// Windows (msysgit):
$gitphp_conf['gitbin'] = 'C:\\Progra~1\\Git\\bin\\git.exe';

/*
 * diffbin
 * Path to diff binary
 * Same rules as gitbin
 */
// Linux:
$gitphp_conf['diffbin'] = 'diff';
// Windows (msysgit):
$gitphp_conf['diffbin'] = 'C:\\Progra~1\\Git\\bin\\diff.exe';

/*
 * gittmp
 * Location for temporary files for diffs
 */
$gitphp_conf['gittmp'] = '/tmp/gitphp/';

/*
 * magicdb
 * Path to the libmagic db used to read mimetype
 * Only applies if filemimetype = true
 * You can leave this as null and let the system
 * try to find the database for you, but that method
 * is known to have issues
 * If the path is correct but it's still not working,
 * try removing the file extension if you have it on,
 * or vice versa
 */
// Linux:
$gitphp_conf['magicdb'] = '/usr/share/misc/magic';
// Windows:
$gitphp_conf['magicdb'] = 'C:\\wamp\\php\\extras\\magic';





/*******************************************************
 * Cache options
 */

/*
 * cache
 * Turns on template caching. If in doubt, leave it off
 * You will need to create a directory 'cache' and make it
 * writable by the server
 */
$gitphp_conf['cache'] = false;

/*
 * objectcache
 * Turns on object caching.  This caches immutable pieces of
 * data from the git repository.  You will need to create a
 * directory 'cache' and make it writable by the server.
 * This can be used in place of the template cache, or
 * in addition to it for the maximum benefit.
 */
$gitphp_conf['objectcache'] = false;

/*
 * cacheexpire
 * Attempts to automatically expire cache when a new commit renders
 * it out of date.
 * This is a good option for most users because it ensures the cache
 * is always up to date and users are seeing correct information,
 * although it is a slight performance hit.
 * However, if your commits are coming in so quickly that the cache
 * is constantly being expired, turn this off.
 */
$gitphp_conf['cacheexpire'] = true;

/*
 * cachelifetime
 * Sets how long a page will be cached, in seconds
 * If you are automatically expiring the cache
 * (see the 'cacheexpire' option above), then this can be set
 * relatively high - 3600 seconds (1 hour) or even longer.
 * -1 means no timeout.
 * If you have turned cacheexpire off because of too many
 * cache expirations, set this low (5-10 seconds).
 */
$gitphp_conf['cachelifetime'] = 3600;

/*
 * objectcachelifetime
 * Sets how long git objects will be cached, in seconds
 * The object cache only stores immutable objects from
 * the git repository, so there's no harm in setting
 * this to a high number.  Set to -1 to never expire.
 */
$gitphp_conf['objectcachelifetime'] = 86400;

/*
 * memcache
 * Enables memcache support for caching data, instead of
 * Smarty's standard on-disk cache.
 * Only applies if cache = true or objectcache = true (or both)
 * Requires either the Memcached or Memcache PHP extensions.
 * This is an array of servers.  Each server is specified as an
 * array.
 * Index 0 (required): The server hostname/IP
 * Index 1 (optional): The port, default is 11211
 * Index 2 (optional): The weight, default is 1
 */
//$gitphp_conf['memcache'] = array(
//	array('127.0.0.1', 11211, 2),
//	array('memcacheserver1', 11211),
//	array('memcacheserver2')
//);



/*******************************************************
 * Paths to php libraries
 */

/*
 * smarty_prefix
 * This is the prefix where smarty is installed.
 * If an absolute (starts with /) path is given,
 * Smarty.class.php will be searched for in that directory.
 * If a relative (doesn't start with /) path is given,
 * that subdirectory inside the php include dirs will be
 * searched.  So, for example, if you specify the path as
 * "/usr/share/Smarty/" then the script will look for
 * /usr/share/Smarty/Smarty.class.php.
 * If you specify the path as "smarty/" then it will search
 * the include directories in php.ini's include_path directive,
 * so it would search in places like /usr/share/php and /usr/lib/php:
 * /usr/share/php/smarty/Smarty.class.php,
 * /usr/lib/php/smarty/Smarty.class.php, etc.
 * Leave blank to just search in the root of the php include directories
 * like /usr/share/php/Smarty.class.php, /usr/lib/php/Smarty.class.php, etc.
 */
$gitphp_conf['smarty_prefix'] = 'lib/smarty/libs/';

/*
 * geshiroot
 * Directory where geshi is installed, only applies if geshi is enabled
 * NOTE: this is the path to the base geshi.php file to include,
 * NOT the various other geshi php source files!
 * Leave blank if geshi.php is in the gitphp root
 */
$gitphp_conf['geshiroot'] = 'lib/geshi/';




/*******************************************************
 * Debugging options
 */

/*
 * debug
 * Turns on extra warning messages
 * Not recommended for production systems, as it will give
 * way more info about what's happening than you care about, and
 * will screw up non-html output (rss, opml, snapshots, etc)
 */
$gitphp_conf['debug'] = false;

/*
 * benchmark
 * Turns on extra timestamp and memory benchmarking info
 * when debug mode is turned on.  Generates lots of output.
 */
$gitphp_conf['benchmark'] = false;

