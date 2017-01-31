<?php
/*
 *  gitphp.conf.php
 *  gitphp: A PHP git repository browser
 *  Component: Configuration
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 */
/*
 * stylesheet
 * Path to page stylesheet
 */
$gitphp_conf['stylesheet'] = "";

/*
 * projectroot
 * Absolute filesystem path prepended to project paths
 * (don't forget trailing slash!)
 */
$gitphp_conf['projectroot'] = $_REQUEST['git_root_path'].$_REQUEST['project_dir'].'/';

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
$gitphp_conf['gitbin'] = "/usr/bin/git";

/*
 * diffbin
 * Path to diff binary
 * Same rules as gitbin
 * Windows (msysgit): C:\\Progra~1\\Git\\bin\\diff.exe
 */
$gitphp_conf['diffbin'] = "/usr/bin/diff";

/*
 * gittmp
 * Location for temporary files for diffs
 * (don't forget trailing slash!)
 */
$gitphp_conf['gittmp'] = "/tmp/";

/*
 * title
 * The string that will be used as the page title
 * The variable '$gitphp_appstring' will expand to
 * the name (gitphp) and version
 * The variable '$gitphp_version' will expand to the
 * version number only
 */
$gitphp_conf['title'] = "Tuleap";

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
$gitphp_conf['compressformat'] = GITPHP_COMPRESS_BZ2;

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
$gitphp_conf['geshi'] = TRUE;


/*
 * filemimetype
 * Attempt to read the file's mimetype when displaying
 * (for example, displaying an image as an actual image
 *  in a browser)
 * This requires either PHP >= 5.3.0, PECL fileinfo, or
 * Linux
 */
$gitphp_conf['filemimetype'] = TRUE;

/*
 * magicdb
 * Path to the libmagic db used to read mimetype
 * You can leave this as null and let the system
 * try to find the database for you, but that method
 * is known to have issues
 * If the path is correct but it's still not working,
 * try removing the file extension if you have it on,
 * or vice versa
 */
$gitphp_conf['magicdb'] = "/usr/share/misc/magic.mgc";


/*
 * search
 * Set this to false or comment it out to disable searching
 */
$gitphp_conf['search'] = TRUE;

/*
 * filesearch
 * Set this to false to disable searching within files
 * (it can be resource intensive)
 */
$gitphp_conf['filesearch'] = FALSE;

/*
 * debug
 * Turns on extra warning messages and benchmarking.
 * Not recommended for production systems
 */
$gitphp_conf['debug'] = False;

/*
 * cache
 * Turns on smarty caching
 * Be careful with this.  If in doubt, leave it off
 */
$gitphp_conf['cache'] = FALSE;

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
$gitphp_conf['cacheexpire'] = TRUE;

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

/**
 * Allow to redefine where smarty temporary files should be dumped.
 * Useful when you don't have write access to the smarty path (shared smarty
 * install).
 */
$gitphp_conf['smarty_tmp'] = '/tmp/gitphp-tuleap/smarty';

?>
