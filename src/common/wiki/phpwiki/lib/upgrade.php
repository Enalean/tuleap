<?php
//-*-php-*-
rcs_id('$Id: upgrade.php,v 1.47 2005/02/27 19:13:27 rurban Exp $');
/*
 Copyright 2004,2005 $ThePhpWikiProgrammingTeam

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
 * Upgrade the WikiDB and config settings after installing a new
 * PhpWiki upgrade.
 * Status: experimental, no queries for verification yet,
 *         no merge conflict resolution (patch?), just overwrite.
 *
 * Installation on an existing PhpWiki database needs some
 * additional worksteps. Each step will require multiple pages.
 *
 * This is the plan:
 *  1. Check for new or changed database schema and update it
 *     according to some predefined upgrade tables. (medium, complete)
 *  2. Check for new or changed (localized) pgsrc/ pages and ask
 *     for upgrading these. Check timestamps, upgrade silently or
 *     show diffs if existing. Overwrite or merge (easy, complete)
 *  3. Check for new or changed or deprecated index.php/config.ini settings
 *     and help in upgrading these. (hard)
 *  3a Convert old-style index.php into config/config.ini. (easy)
 *  4. Check for changed plugin invocation arguments. (hard)
 *  5. Check for changed theme variables. (hard)
 *  6. Convert the single-request upgrade to a class-based multi-page
 *     version. (hard)

 * Done: overwrite=1 link on edit conflicts at first occurence "Overwrite all".
 *
 * @author: Reini Urban
 */
require_once("lib/loadsave.php");

/**
 * TODO: check for the pgsrc_version number, not the revision mtime only
 */
function doPgsrcUpdate(&$request, $pagename, $path, $filename, $checkonly = false)
{
    $dbi = $request->getDbh();
    $page = $dbi->getPage($pagename);
    if ($page->exists()) {
        // check mtime: update automatically if pgsrc is newer
        $rev = $page->getCurrentRevision();
        $page_mtime = $rev->get('mtime');
        $data  = implode("", file($path . "/" . $filename));
        if (($parts = ParseMimeifiedPages($data))) {
            usort($parts, 'SortByPageVersion');
            reset($parts);
            $pageinfo = $parts[0];
            $stat  = stat($path . "/" . $filename);
            $new_mtime = @$pageinfo['versiondata']['mtime'];
            if (!$new_mtime) {
                $new_mtime = @$pageinfo['versiondata']['lastmodified'];
            }
            if (!$new_mtime) {
                $new_mtime = @$pageinfo['pagedata']['date'];
            }
            if (!$new_mtime) {
                $new_mtime = $stat[9];
            }
            if ($new_mtime > $page_mtime) {
                echo "$path/$pagename: ",_("newer than the existing page."),
                    _(" replace "),"($new_mtime &gt; $page_mtime)","<br />\n";
                if (!$checkonly) {
                    LoadAny($request, $path . "/" . $filename);
                }
                echo "<br />\n";
            } else {
                /*echo "$path/$pagename: ",_("older than the existing page."),
                    _(" skipped"),".<br />\n";*/
            }
        } else {
            echo "$path/$pagename: ",("unknown format."),
                    _(" skipped"),".<br />\n";
        }
    } else {
        echo sprintf(_("%s does not exist"), $pagename),"<br />\n";
        if (!$checkonly) {
            LoadAny($request, $path . "/" . $filename);
        }
        echo "<br />\n";
    }
}

/** Need the english filename (required precondition: urlencode == urldecode).
 *  Returns the plugin name.
 */
function isActionPage($filename)
{
    static $special = array("DebugInfo"     => "_BackendInfo",
                            "PhpWikiRecentChanges" => "RssFeed",
                            "ProjectSummary"      => "RssFeed",
                            "RecentReleases"      => "RssFeed",
                            "InterWikiMap"      => "InterWikiMap",
                            );
    $base = preg_replace("/\..{1,4}$/", "", basename($filename));
    if (isset($special[$base])) {
        return $special[$base];
    }
    if (FindFile("lib/plugin/" . $base . ".php", true)) {
        return $base;
    } else {
        return false;
    }
}

function CheckActionPageUpdate(&$request, $checkonly = false)
{
    echo "<h3>",_("check for necessary ActionPage updates"),"</h3>\n";
    $dbi = $request->getDbh();
    $path = FindFile('codendipgsrc');
    $pgsrc = new fileSet($path);
    // most actionpages have the same name as the plugin
    $loc_path = FindLocalizedFile('pgsrc');
    foreach ($pgsrc->getFiles() as $filename) {
        if (substr($filename, -1, 1) == '~') {
            continue;
        }
        $pagename = urldecode($filename);
        if (isActionPage($filename)) {
            $translation = gettext($pagename);
            if ($translation == $pagename) {
                doPgsrcUpdate(
                    $request,
                    $pagename,
                    $path,
                    $filename,
                    $checkonly
                );
            } elseif (FindLocalizedFile('pgsrc/' . urlencode($translation), 1)) {
                doPgsrcUpdate(
                    $request,
                    $translation,
                    $loc_path,
                    urlencode($translation),
                    $checkonly
                );
            } else {
                doPgsrcUpdate(
                    $request,
                    $pagename,
                    $path,
                    $filename,
                    $checkonly
                );
            }
        }
    }
}

// see loadsave.php for saving new pages.
function CheckPgsrcUpdate(&$request, $checkonly = false)
{
    echo "<h3>",_("check for necessary pgsrc updates"),"</h3>\n";
    $dbi = $request->getDbh();
    $path = FindLocalizedFile(WIKI_PGSRC);
    $pgsrc = new fileSet($path);
    // fixme: verification, ...
    $isHomePage = false;
    foreach ($pgsrc->getFiles() as $filename) {
        if (substr($filename, -1, 1) == '~') {
            continue;
        }
        $pagename = urldecode($filename);
        // don't ever update the HomePage
        if (defined(HOME_PAGE)) {
            if ($pagename == HOME_PAGE) {
                $isHomePage = true;
            } elseif ($pagename == _("HomePage")) {
                $isHomePage = true;
            }
        }
        if ($pagename == "HomePage") {
            $isHomePage = true;
        }
        if ($isHomePage) {
            echo "$path/$pagename: ",_("always skip the HomePage."),
                _(" skipped"),".<br />\n";
            $isHomePage = false;
            continue;
        }
        if (!isActionPage($filename)) {
            doPgsrcUpdate($request, $pagename, $path, $filename, $checkonly);
        }
    }
    return;
}

function fixConfigIni($match, $new)
{
    $file = FindFile("config/config.ini");
    $found = false;
    if (is_writable($file)) {
        $in = fopen($file, "rb");
        $out = fopen($tmp = tempnam(FindFile("uploads"), "cfg"), "wb");
        if (isWindows()) {
            $tmp = str_replace("/", "\\", $tmp);
        }
        while ($s = fgets($in)) {
            if (preg_match($match, $s)) {
                $s = $new . (isWindows() ? "\r\n" : "\n");
                $found = true;
            }
            fputs($out, $s);
        }
        fclose($in);
        fclose($out);
        if (!$found) {
            echo " <b><font color=\"red\">",_("FAILED"),"</font></b>: ",
                sprintf(_("%s not found"), $match);
            unlink($out);
        } else {
            @unlink("$file.bak");
            @rename($file, "$file.bak");
            if (rename($tmp, $file)) {
                echo " <b>",_("FIXED"),"</b>";
            } else {
                echo " <b>",_("FAILED"),"</b>: ";
                sprintf(_("couldn't move %s to %s"), $tmp, $file);
                return false;
            }
        }
        return $found;
    } else {
        echo " <b><font color=\"red\">",_("FAILED"),"</font></b>: ",
            sprintf(_("%s is not writable"), $file);
        return false;
    }
}

function CheckConfigUpdate(&$request)
{
    echo "<h3>",_("check for necessary config updates"),"</h3>\n";
    echo _("check for old CACHE_CONTROL = NONE")," ... ";
    if (defined('CACHE_CONTROL') and CACHE_CONTROL == '') {
        echo "<br />&nbsp;&nbsp;",
            _("CACHE_CONTROL is set to 'NONE', and must be changed to 'NO_CACHE'"),
            " ...";
        fixConfigIni("/^\s*CACHE_CONTROL\s*=\s*NONE/", "CACHE_CONTROL = NO_CACHE");
    } else {
        echo _("OK");
    }
    echo "<br />\n";
    echo _("check for GROUP_METHOD = NONE")," ... ";
    if (defined('GROUP_METHOD') and GROUP_METHOD == '') {
        echo "<br />&nbsp;&nbsp;",
            _("GROUP_METHOD is set to NONE, and must be changed to \"NONE\""),
            " ...";
        fixConfigIni("/^\s*GROUP_METHOD\s*=\s*NONE/", "GROUP_METHOD = \"NONE\"");
    } else {
        echo _("OK");
    }
    echo "<br />\n";
}

/**
 * TODO:
 *
 * Upgrade: Base class for multipage worksteps
 * identify, validate, display options, next step
 */
/*
class Upgrade {
}

class Upgrade_CheckPgsrc extends Upgrade {
}

class Upgrade_CheckDatabaseUpdate extends Upgrade {
}
*/

// TODO: At which step are we?
// validate and do it again or go on with next step.

/** entry function from lib/main.php
 */
function DoUpgrade($request)
{
    if (!$request->_user->isAdmin()) {
        $request->_notAuthorized(WIKIAUTH_ADMIN);
        $request->finish(
            HTML::div(
                array('class' => 'disabled-plugin'),
                fmt("Upgrade disabled: user != isAdmin")
            )
        );
        return;
    }

    //print("<br>This action is blocked by administrator. Sorry for the inconvenience !<br>");
    exit("<br>This action is blocked by administrator. Sorry for the inconvenience !<br>");
}


/*
 $Log: upgrade.php,v $
 Revision 1.47  2005/02/27 19:13:27  rurban
 latin1 mysql fix

 Revision 1.46  2005/02/12 17:22:18  rurban
 locale update: missing . : fixed. unified strings
 proper linebreaks

 Revision 1.45  2005/02/10 19:01:19  rurban
 add PDO support

 Revision 1.44  2005/02/07 15:40:42  rurban
 use defined CHARSET for db. more comments

 Revision 1.43  2005/02/04 11:44:07  rurban
 check passwd in access_log

 Revision 1.42  2005/02/02 19:38:13  rurban
 prefer utf8 pagenames for collate issues

 Revision 1.41  2005/01/31 12:15:29  rurban
 print OK

 Revision 1.40  2005/01/30 23:22:17  rurban
 clarify messages

 Revision 1.39  2005/01/30 23:09:17  rurban
 sanify session fields

 Revision 1.38  2005/01/25 07:57:02  rurban
 add dbadmin form, add mysql LOCK TABLES check, add plugin args updater (not yet activated)

 Revision 1.37  2005/01/20 10:19:08  rurban
 add InterWikiMap to special pages

 Revision 1.36  2004/12/20 12:56:11  rurban
 patch #1088128 by Kai Krakow. avoid chicken & egg problem

 Revision 1.35  2004/12/13 14:35:41  rurban
 verbose arg

 Revision 1.34  2004/12/11 09:39:28  rurban
 needed init for ref

 Revision 1.33  2004/12/10 22:33:39  rurban
 add WikiAdminUtils method for convert-cached-html
 missed some vars.

 Revision 1.32  2004/12/10 22:15:00  rurban
 fix $page->get('_cached_html)
 refactor upgrade db helper _convert_cached_html() to be able to call them from WikiAdminUtils also.
 support 2nd genericSqlQuery param (bind huge arg)

 Revision 1.31  2004/12/10 02:45:26  rurban
 SQL optimization:
   put _cached_html from pagedata into a new seperate blob, not huge serialized string.
   it is only rarelely needed: for current page only, if-not-modified
   but was extracted for every simple page iteration.

 Revision 1.30  2004/11/29 17:58:57  rurban
 just aesthetics

 Revision 1.29  2004/11/29 16:08:31  rurban
 added missing nl

 Revision 1.28  2004/11/16 16:25:14  rurban
 fix accesslog tablename, print CREATED only if really done

 Revision 1.27  2004/11/07 16:02:52  rurban
 new sql access log (for spam prevention), and restructured access log class
 dbh->quote (generic)
 pear_db: mysql specific parts seperated (using replace)

 Revision 1.26  2004/10/14 19:19:34  rurban
 loadsave: check if the dumped file will be accessible from outside.
 and some other minor fixes. (cvsclient native not yet ready)

 Revision 1.25  2004/09/06 08:28:00  rurban
 rename genericQuery to genericSqlQuery

 Revision 1.24  2004/07/05 13:56:22  rurban
 sqlite autoincrement fix

 Revision 1.23  2004/07/04 10:28:06  rurban
 DBADMIN_USER fix

 Revision 1.22  2004/07/03 17:21:28  rurban
 updated docs: submitted new mysql bugreport (#1491 did not fix it)

 Revision 1.21  2004/07/03 16:51:05  rurban
 optional DBADMIN_USER:DBADMIN_PASSWD for action=upgrade (if no ALTER permission)
 added atomic mysql REPLACE for PearDB as in ADODB
 fixed _lock_tables typo links => link
 fixes unserialize ADODB bug in line 180

 Revision 1.20  2004/07/03 14:48:18  rurban
 Tested new mysql 4.1.3-beta: binary search bug as fixed.
 => fixed action=upgrade,
 => version check in PearDB also (as in ADODB)

 Revision 1.19  2004/06/19 12:19:09  rurban
 slightly improved docs

 Revision 1.18  2004/06/19 11:47:17  rurban
 added CheckConfigUpdate: CACHE_CONTROL = NONE => NO_CACHE

 Revision 1.17  2004/06/17 11:31:50  rurban
 check necessary localized actionpages

 Revision 1.16  2004/06/16 10:38:58  rurban
 Disallow refernces in calls if the declaration is a reference
 ("allow_call_time_pass_reference clean").
   PhpWiki is now allow_call_time_pass_reference = Off clean,
   but several external libraries may not.
   In detail these libs look to be affected (not tested):
   * Pear_DB odbc
   * adodb oracle

 Revision 1.15  2004/06/07 19:50:40  rurban
 add owner field to mimified dump

 Revision 1.14  2004/06/07 18:38:18  rurban
 added mysql 4.1.x search fix

 Revision 1.13  2004/06/04 20:32:53  rurban
 Several locale related improvements suggested by Pierrick Meignen
 LDAP fix by John Cole
 reanable admin check without ENABLE_PAGEPERM in the admin plugins

 Revision 1.12  2004/05/18 13:59:15  rurban
 rename simpleQuery to genericSqlQuery

 Revision 1.11  2004/05/15 13:06:17  rurban
 skip the HomePage, at first upgrade the ActionPages, then the database, then the rest

 Revision 1.10  2004/05/15 01:19:41  rurban
 upgrade prefix fix by Kai Krakow

 Revision 1.9  2004/05/14 11:33:03  rurban
 version updated to 1.3.11pre
 upgrade stability fix

 Revision 1.8  2004/05/12 10:49:55  rurban
 require_once fix for those libs which are loaded before FileFinder and
   its automatic include_path fix, and where require_once doesn't grok
   dirname(__FILE__) != './lib'
 upgrade fix with PearDB
 navbar.tmpl: remove spaces for IE &nbsp; button alignment

 Revision 1.7  2004/05/06 17:30:38  rurban
 CategoryGroup: oops, dos2unix eol
 improved phpwiki_version:
   pre -= .0001 (1.3.10pre: 1030.099)
   -p1 += .001 (1.3.9-p1: 1030.091)
 improved InstallTable for mysql and generic SQL versions and all newer tables so far.
 abstracted more ADODB/PearDB methods for action=upgrade stuff:
   backend->backendType(), backend->database(),
   backend->listOfFields(),
   backend->listOfTables(),

 Revision 1.6  2004/05/03 15:05:36  rurban
 + table messages

 Revision 1.4  2004/05/02 21:26:38  rurban
 limit user session data (HomePageHandle and auth_dbi have to invalidated anyway)
   because they will not survive db sessions, if too large.
 extended action=upgrade
 some WikiTranslation button work
 revert WIKIAUTH_UNOBTAINABLE (need it for main.php)
 some temp. session debug statements

 Revision 1.3  2004/04/29 22:33:30  rurban
 fixed sf.net bug #943366 (Kai Krakow)
   couldn't load localized url-undecoded pagenames

 Revision 1.2  2004/03/12 15:48:07  rurban
 fixed explodePageList: wrong sortby argument order in UnfoldSubpages
 simplified lib/stdlib.php:explodePageList

 */

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
