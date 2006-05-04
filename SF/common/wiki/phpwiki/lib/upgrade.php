<?php //-*-php-*-
rcs_id('$Id: upgrade.php 2691 2006-03-02 15:31:51Z guerin $');

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
 * Upgrade the WikiDB and config settings after installing a new 
 * PhpWiki upgrade.
 * Status: experimental, no queries for verification yet, no db update,
 *         no merge conflict
 * Installation on an existing PhpWiki database needs some 
 * additional worksteps. Each step will require multiple pages.
 *
 * This is the plan:
 *  1. Check for new or changed database schema and update it 
 *     according to some predefined upgrade tables. (medium)
 *  2. Check for new or changed (localized) pgsrc/ pages and ask 
 *     for upgrading these. Check timestamps, upgrade silently or 
 *     show diffs if existing. Overwrite or merge (easy)
 *  3. Check for new or changed or deprecated index.php settings
 *     and help in upgrading these. (hard)
 *  4. Check for changed plugin invocation arguments. (hard)
 *  5. Check for changed theme variables. (hard)
 *
 * @author: Reini Urban
 */
require_once("lib/loadsave.php");

// see loadsave.php for saving new pages.
function CheckPgsrcUpdate(&$request) {
    echo "<h3>",_("check for necessary pgsrc updates"),"</h3>\n";
    $dbi = $request->getDbh(); 
    $path = FindLocalizedFile(WIKI_PGSRC);
    $pgsrc = new fileSet($path);
    // fixme: verification, ...
    foreach ($pgsrc->getFiles() as $filename) {
        if (substr($filename,-1,1) == '~') continue;
        $pagename = urldecode($filename);
        $page = $dbi->getPage($pagename);
        if ($page->exists()) {
            // check mtime: update automatically if pgsrc is newer
            $rev = $page->getCurrentRevision();
            $page_mtime = $rev->get('mtime');
            $data  = implode("", file($path."/".$filename));
            if (($parts = ParseMimeifiedPages($data))) {
                usort($parts, 'SortByPageVersion');
                reset($parts);
                $pageinfo = $parts[0];
                $stat  = stat($path."/".$filename);
                $new_mtime = @$pageinfo['versiondata']['mtime'];
                if (!$new_mtime)
                    $new_mtime = @$pageinfo['versiondata']['lastmodified'];
                if (!$new_mtime)
                    $new_mtime = @$pageinfo['pagedata']['date'];
                if (!$new_mtime)
                    $new_mtime = $stat[9];
                if ($new_mtime > $page_mtime) {
                    echo "$path/$pagename: newer than the existing page. replace ($new_mtime &gt; $page_mtime)<br />\n";
                    LoadAny($request,$path."/".$filename);
                    echo "<br />\n";
                } else {
                    echo "$path/$pagename: older than the existing page. skipped.<br />\n";
                }
            } else {
                echo "$path/$pagename: unknown format, skipped.<br />\n";
            }
        } else {
            echo "$pagename does not exist<br />\n";
            LoadAny($request,$path."/".$filename);
            echo "<br />\n";
        }
    }
    return;
}

/**
 * TODO: Search table definition in appropriate schema
 *       and create it.
 * Supported: mysql and generic SQL, for ADODB and PearDB.
 */
function installTable(&$dbh, $table, $backend_type) {
    global $DBParams;
    if (!in_array($DBParams['dbtype'],array('SQL','ADODB'))) return;
    echo _("MISSING")," ... \n";
    $backend = &$dbh->_backend->_dbh;
    /*
    $schema = findFile("schemas/${backend_type}.sql");
    if (!$schema) {
        echo "  ",_("FAILED"),": ",sprintf(_("no schema %s found"),"schemas/${backend_type}.sql")," ... <br />\n";
        return false;
    }
    */
    extract($dbh->_backend->_table_names);
    $prefix = isset($DBParams['prefix']) ? $DBParams['prefix'] : '';
    switch ($table) {
    case 'session':
        assert($session_tbl);
        if ($backend_type == 'mysql') {
            $dbh->simpleQuery("
CREATE TABLE $session_tbl (
    	sess_id 	CHAR(32) NOT NULL DEFAULT '',
    	sess_data 	BLOB NOT NULL,
    	sess_date 	INT UNSIGNED NOT NULL,
    	sess_ip 	CHAR(15) NOT NULL,
    	PRIMARY KEY (sess_id),
	INDEX (sess_date)
)");
        } else {
            $dbh->simpleQuery("
CREATE TABLE $session_tbl (
	sess_id 	CHAR(32) NOT NULL DEFAULT '',
    	sess_data 	".($backend_type == 'pgsql'?'TEXT':'BLOB')." NOT NULL,
    	sess_date 	INT,
    	sess_ip 	CHAR(15) NOT NULL
)");
            $dbh->simpleQuery("CREATE UNIQUE INDEX sess_id ON $session_tbl (sess_id)");
        }
        $dbh->simpleQuery("CREATE INDEX sess_date on session (sess_date)");
        break;
    case 'user':
        $user_tbl = $prefix.'user';
        if ($backend_type == 'mysql') {
            $dbh->simpleQuery("
CREATE TABLE $user_tbl (
  	userid 	CHAR(48) BINARY NOT NULL UNIQUE,
  	passwd 	CHAR(48) BINARY DEFAULT '',
  	PRIMARY KEY (userid)
)");
        } else {
            $dbh->simpleQuery("
CREATE TABLE $user_tbl (
  	userid 	CHAR(48) NOT NULL,
  	passwd 	CHAR(48) DEFAULT ''
)");
            $dbh->simpleQuery("CREATE UNIQUE INDEX userid ON $user_tbl (userid)");
        }
        break;
    case 'pref':
        $pref_tbl = $prefix.'pref';
        if ($backend_type == 'mysql') {
            $dbh->simpleQuery("
CREATE TABLE $pref_tbl (
  	userid 	CHAR(48) BINARY NOT NULL UNIQUE,
  	prefs  	TEXT NULL DEFAULT '',
  	PRIMARY KEY (userid)
)");
        } else {
            $dbh->simpleQuery("
CREATE TABLE $pref_tbl (
  	userid 	CHAR(48) NOT NULL,
  	prefs  	TEXT NULL DEFAULT '',
)");
            $dbh->simpleQuery("CREATE UNIQUE INDEX userid ON $pref_tbl (userid)");
        }
        break;
    case 'member':
        $member_tbl = $prefix.'member';
        if ($backend_type == 'mysql') {
            $dbh->simpleQuery("
CREATE TABLE $member_tbl (
	userid    CHAR(48) BINARY NOT NULL,
   	groupname CHAR(48) BINARY NOT NULL DEFAULT 'users',
   	INDEX (userid),
   	INDEX (groupname)
)");
        } else {
            $dbh->simpleQuery("
CREATE TABLE $member_tbl (
	userid    CHAR(48) NOT NULL,
   	groupname CHAR(48) NOT NULL DEFAULT 'users',
)");
            $dbh->simpleQuery("CREATE INDEX userid ON $member_tbl (userid)");
            $dbh->simpleQuery("CREATE INDEX groupname ON $member_tbl (groupname)");
        }
        break;
    case 'rating':
        $rating_tbl = $prefix.'rating';
        if ($backend_type == 'mysql') {
            $dbh->simpleQuery("
CREATE TABLE $rating_tbl (
        dimension INT(4) NOT NULL,
        raterpage INT(11) NOT NULL,
        rateepage INT(11) NOT NULL,
        ratingvalue FLOAT NOT NULL,
        rateeversion INT(11) NOT NULL,
        tstamp TIMESTAMP(14) NOT NULL,
        PRIMARY KEY (dimension, raterpage, rateepage)
)");
        } else {
            $dbh->simpleQuery("
CREATE TABLE $rating_tbl (
        dimension INT(4) NOT NULL,
        raterpage INT(11) NOT NULL,
        rateepage INT(11) NOT NULL,
        ratingvalue FLOAT NOT NULL,
        rateeversion INT(11) NOT NULL,
        tstamp TIMESTAMP(14) NOT NULL,
)");
            $dbh->simpleQuery("CREATE UNIQUE INDEX rating ON $rating_tbl (dimension, raterpage, rateepage)");
        }
        break;
    }
    echo "  ",_("CREATED"),"<br />\n";
}

/**
 * currently update only session, user, pref and member
 * jeffs-hacks database api (around 1.3.2) later
 *   people should export/import their pages if using that old versions.
 */
function CheckDatabaseUpdate($request) {
    global $DBParams, $DBAuthParams;
    if (!in_array($DBParams['dbtype'],array('SQL','ADODB'))) return;
    echo "<h3>",_("check for necessary database updates"),"</h3>\n";
    $dbh = &$request->_dbi;
    $tables = $dbh->_backend->listOfTables();
    $backend_type = $dbh->_backend->backendType();
    $prefix = isset($DBParams['prefix']) ? $DBParams['prefix'] : '';
    extract($dbh->_backend->_table_names);
    foreach (explode(':','session:user:pref:member') as $table) {
        echo _("check for table $table")," ...";    	
    	if (!in_array($table,$tables)) {
            installTable(&$dbh, $table, $backend_type);
    	} else {
    	    echo "OK <br />\n";
        }
    }
    $backend = &$dbh->_backend->_dbh;
    // 1.3.8 added session.sess_ip
    if (phpwiki_version() >= 1030.08 and USE_DB_SESSION and isset($request->_dbsession)) {
  	echo _("check for new session.sess_ip column")," ... ";
  	$database = $dbh->_backend->database();
  	assert(!empty($DBParams['db_session_table']));
        $session_tbl = $prefix . $DBParams['db_session_table'];
        $sess_fields = $dbh->_backend->listOfFields($database,$session_tbl);
        if (!in_array("sess_ip",$sess_fields)) {
            echo "<b>",_("ADDING"),"</b>"," ... ";		
            $dbh->simpleQuery("ALTER TABLE $session_tbl ADD sess_ip CHAR(15) NOT NULL");
        } else {
            echo _("OK");
        }
        echo "<br />\n";
    }
    // 1.3.10 mysql requires page.id auto_increment
    // mysql, mysqli or mysqlt
    if (phpwiki_version() >= 1030.099 and substr($backend_type,0,5) == 'mysql') {
  	echo _("check for page.id auto_increment flag")," ...";
        assert(!empty($page_tbl));
  	$database = $dbh->_backend->database();
  	$fields = mysql_list_fields($database,$page_tbl,$dbh->_backend->connection());
  	$columns = mysql_num_fields($fields); 
        for ($i = 0; $i < $columns; $i++) {
            if (mysql_field_name($fields, $i) == 'id') {
            	$flags = mysql_field_flags($fields, $i);
            	if (!strstr($flags,"auto_increment")) {
                    echo "<b>",_("ADDING"),"</b>"," ... ";		
                    // MODIFY col_def valid since mysql 3.22.16,
                    // older mysql's need CHANGE old_col col_def
                    $dbh->simpleQuery("ALTER TABLE $page_tbl CHANGE id id INT NOT NULL AUTO_INCREMENT");
                    $fields = mysql_list_fields($database,$page_tbl);
                    if (!strstr(mysql_field_flags($fields, $i),"auto_increment"))
                        echo " <b><font color=\"red\">",_("FAILED"),"</font></b><br />\n";		
                    else     
                        echo _("OK"),"<br />\n";            		
            	} else {
                    echo _("OK"),"<br />\n";            		
            	}
            	break;
            }
        }
        mysql_free_result($fields);
    }
    return;
}

/**
 * Upgrade: Base class for multipage worksteps
 * identify, validate, display options, next step
 */
class Upgrade {
}

class Upgrade_CheckPgsrc extends Upgrade {
}

class Upgrade_CheckDatabaseUpdate extends Upgrade {
}

// TODO: At which step are we? 
// validate and do it again or go on with next step.

/** entry function from lib/main.php
 */
function DoUpgrade($request) {

    if (!$request->_user->isAdmin()) {
        $request->_notAuthorized(WIKIAUTH_ADMIN);
        $request->finish(
                         HTML::div(array('class' => 'disabled-plugin'),
                                   fmt("Upgrade disabled: user != isAdmin")));
        return;
    }

    StartLoadDump($request, _("Upgrading this PhpWiki"));
    CheckDatabaseUpdate($request);
    CheckPgsrcUpdate($request);
    //CheckThemeUpdate($request);
    EndLoadDump($request);
}


/**
 $Log$
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
?>
