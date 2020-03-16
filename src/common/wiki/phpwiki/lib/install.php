<?php
//-*-php-*-
rcs_id('$Id: install.php,v 1.4 2005/09/15 05:56:12 rurban Exp $');

/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Loaded when config/config.ini was not found.
 * So we have no main loop and no request object yet.
 */

function init_install()
{
    // prevent from recursion
    static $already = 0;
    // setup default settings
    if (!$already) {
        IniConfig(dirname(__FILE__) . "/../config/config-dist.ini");
    }
    $already = 1;
}

/**
 * Display a screen of various settings:
 * 1. convert from older index.php configuration [TODO]
 * 2. database and admin_user setup based on configurator.php
 * 3. dump the current settings to config/config.ini.
 */
function run_install($part = '')
{
    static $already = 0;
    if ($part) {
        $_GET['show'] = $part;
    }
    // setup default settings
    if (!$already and !defined("_PHPWIKI_INSTALL_RUNNING")) {
        define("_PHPWIKI_INSTALL_RUNNING", true);
    }
    $already = 1;
}

init_install();

/**
 $Log: install.php,v $
 Revision 1.4  2005/09/15 05:56:12  rurban
 read configurator desc from config-dist.ini, update desc, fix some warnings

 Revision 1.3  2005/02/28 20:24:23  rurban
 _GET is different from HTPP_GET_VARS. use the correct one

 Revision 1.2  2005/02/26 17:47:57  rurban
 configurator: add (c), support show=_part1 initial expand, enable
   ENABLE_FILE_OUTPUT, use part.id not name
 install.php: fixed for multiple invocations (on various missing vars)
 IniConfig: call install.php on more errors with expanded part.

 Revision 1.1  2004/12/06 19:49:58  rurban
 enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
 renamed delete_page to purge_page.
 enable action=edit&version=-1 to force creation of a new version.
 added BABYCART_PATH config
 fixed magiqc in adodb.inc.php
 and some more docs


 */

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
