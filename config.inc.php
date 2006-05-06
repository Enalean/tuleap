<?php
/*
 *  config.inc.php
 *  gitphp: A PHP git repository browser
 *  Component: Configuration
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/*
 * projectroot
 * Absolute filesystem path prepended to project paths
 */
$gitphp_conf['projectroot'] = "/storage/anime3/non-anime/git";

/*
 * gitbin
 * Path to git binaries
 * (Leave blank or comment to just use $PATH)
 */
$gitphp_conf['gitbin'] = "/usr/bin";

/*
 * gittmp
 * Location for temporary files for diffs
 */
$gitphp_conf['gittmp'] = "/tmp/gitweb";

/*
 * title
 * The string that will be used as the page title
 * The variable '$gitphp_appstring' will expand to
 * the name (gitphp) and version
 */
$gitphp_conf['title'] = "gitphp :: $gitphp_appstring";

/*
 * smarty_prefix
 * This is the prefix where smarty is installed.
 * (including trailing slash)
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
$gitphp_conf['smarty_prefix'] = "smarty/";

?>
