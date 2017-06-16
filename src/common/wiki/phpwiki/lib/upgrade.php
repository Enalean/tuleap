<?php //-*-php-*-
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
function doPgsrcUpdate(&$request,$pagename,$path,$filename,$checkonly=false) {
    $dbi = $request->getDbh(); 
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
                echo "$path/$pagename: ",_("newer than the existing page."),
                    _(" replace "),"($new_mtime &gt; $page_mtime)","<br />\n";
                if(!$checkonly) {
                    LoadAny($request,$path."/".$filename);
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
        echo sprintf(_("%s does not exist"),$pagename),"<br />\n";
        if(!$checkonly) {
            LoadAny($request,$path."/".$filename);
        }
        echo "<br />\n";
    }
}

/** Need the english filename (required precondition: urlencode == urldecode).
 *  Returns the plugin name.
 */ 
function isActionPage($filename) {
    static $special = array("DebugInfo" 	=> "_BackendInfo",
                            "PhpWikiRecentChanges" => "RssFeed",
                            "ProjectSummary"  	=> "RssFeed",
                            "RecentReleases"  	=> "RssFeed",
                            "InterWikiMap"      => "InterWikiMap",
                            );
    $base = preg_replace("/\..{1,4}$/","",basename($filename));
    if (isset($special[$base])) return $special[$base];
    if (FindFile("lib/plugin/".$base.".php",true)) return $base;
    else return false;
}

function CheckActionPageUpdate(&$request, $checkonly=false)  {
    echo "<h3>",_("check for necessary ActionPage updates"),"</h3>\n";
    $dbi = $request->getDbh(); 
    $path = FindFile('codendipgsrc');
    $pgsrc = new fileSet($path);
    // most actionpages have the same name as the plugin
    $loc_path = FindLocalizedFile('pgsrc');
    foreach ($pgsrc->getFiles() as $filename) {
        if (substr($filename,-1,1) == '~') continue;
        $pagename = urldecode($filename);
        if (isActionPage($filename)) {
            $translation = gettext($pagename);
            if ($translation == $pagename)
                doPgsrcUpdate($request, $pagename, $path, $filename, 
                        $checkonly);
            elseif (FindLocalizedFile('pgsrc/'.urlencode($translation),1))
                doPgsrcUpdate($request, $translation, $loc_path, 
                              urlencode($translation), $checkonly);
            else
                doPgsrcUpdate($request, $pagename, $path, $filename, 
                        $checkonly);
        }
    }
}

// see loadsave.php for saving new pages.
function CheckPgsrcUpdate(&$request, $checkonly=false) {
    echo "<h3>",_("check for necessary pgsrc updates"),"</h3>\n";
    $dbi = $request->getDbh(); 
    $path = FindLocalizedFile(WIKI_PGSRC);
    $pgsrc = new fileSet($path);
    // fixme: verification, ...
    $isHomePage = false;
    foreach ($pgsrc->getFiles() as $filename) {
        if (substr($filename,-1,1) == '~') continue;
        $pagename = urldecode($filename);
        // don't ever update the HomePage
        if (defined(HOME_PAGE))
            if ($pagename == HOME_PAGE) $isHomePage = true;
        else
            if ($pagename == _("HomePage")) $isHomePage = true;
        if ($pagename == "HomePage") $isHomePage = true;
        if ($isHomePage) {
            echo "$path/$pagename: ",_("always skip the HomePage."),
                _(" skipped"),".<br />\n";
            $isHomePage = false;
            continue;
        }
        if (!isActionPage($filename)) {
            doPgsrcUpdate($request,$pagename,$path,$filename,$checkonly);
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
    if (!in_array($DBParams['dbtype'],array('SQL','ADODB','PDO'))) return;
    echo _("MISSING")," ... \n";
    $backend = &$dbh->_backend->_dbh;
    /*
    $schema = findFile("schemas/${backend_type}.sql");
    if (!$schema) {
        echo "  ",_("FAILED"),": ",sprintf(_("no schema %s found"),
        "schemas/${backend_type}.sql")," ... <br />\n";
        return false;
    }
    */
    extract($dbh->_backend->_table_names);
    $prefix = isset($DBParams['prefix']) ? $DBParams['prefix'] : '';
    switch ($table) {
    case 'session':
        assert($session_tbl);
        if ($backend_type == 'mysql') {
            $dbh->genericSqlQuery("
CREATE TABLE $session_tbl (
    	sess_id 	CHAR(32) NOT NULL DEFAULT '',
    	sess_data 	BLOB NOT NULL,
    	sess_date 	INT UNSIGNED NOT NULL,
    	sess_ip 	CHAR(15) NOT NULL,
    	PRIMARY KEY (sess_id),
	INDEX (sess_date)
)");
        } else {
            $dbh->genericSqlQuery("
CREATE TABLE $session_tbl (
	sess_id 	CHAR(32) NOT NULL DEFAULT '',
    	sess_data 	".($backend_type == 'pgsql'?'TEXT':'BLOB')." NOT NULL,
    	sess_date 	INT,
    	sess_ip 	CHAR(15) NOT NULL
)");
            $dbh->genericSqlQuery("CREATE UNIQUE INDEX sess_id ON $session_tbl (sess_id)");
        }
        $dbh->genericSqlQuery("CREATE INDEX sess_date on session (sess_date)");
        echo "  ",_("CREATED");
        break;
    case 'user':
        $user_tbl = $prefix.'user';
        if ($backend_type == 'mysql') {
            $dbh->genericSqlQuery("
CREATE TABLE $user_tbl (
  	userid 	CHAR(48) BINARY NOT NULL UNIQUE,
  	passwd 	CHAR(48) BINARY DEFAULT '',
  	PRIMARY KEY (userid)
)");
        } else {
            $dbh->genericSqlQuery("
CREATE TABLE $user_tbl (
  	userid 	CHAR(48) NOT NULL,
  	passwd 	CHAR(48) DEFAULT ''
)");
            $dbh->genericSqlQuery("CREATE UNIQUE INDEX userid ON $user_tbl (userid)");
        }
        echo "  ",_("CREATED");
        break;
    case 'pref':
        $pref_tbl = $prefix.'pref';
        if ($backend_type == 'mysql') {
            $dbh->genericSqlQuery("
CREATE TABLE $pref_tbl (
  	userid 	CHAR(48) BINARY NOT NULL UNIQUE,
  	prefs  	TEXT NULL DEFAULT '',
  	PRIMARY KEY (userid)
)");
        } else {
            $dbh->genericSqlQuery("
CREATE TABLE $pref_tbl (
  	userid 	CHAR(48) NOT NULL,
  	prefs  	TEXT NULL DEFAULT '',
)");
            $dbh->genericSqlQuery("CREATE UNIQUE INDEX userid ON $pref_tbl (userid)");
        }
        echo "  ",_("CREATED");
        break;
    case 'member':
        $member_tbl = $prefix.'member';
        if ($backend_type == 'mysql') {
            $dbh->genericSqlQuery("
CREATE TABLE $member_tbl (
	userid    CHAR(48) BINARY NOT NULL,
   	groupname CHAR(48) BINARY NOT NULL DEFAULT 'users',
   	INDEX (userid),
   	INDEX (groupname)
)");
        } else {
            $dbh->genericSqlQuery("
CREATE TABLE $member_tbl (
	userid    CHAR(48) NOT NULL,
   	groupname CHAR(48) NOT NULL DEFAULT 'users',
)");
            $dbh->genericSqlQuery("CREATE INDEX userid ON $member_tbl (userid)");
            $dbh->genericSqlQuery("CREATE INDEX groupname ON $member_tbl (groupname)");
        }
        echo "  ",_("CREATED");
        break;
    case 'rating':
        $rating_tbl = $prefix.'rating';
        if ($backend_type == 'mysql') {
            $dbh->genericSqlQuery("
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
            $dbh->genericSqlQuery("
CREATE TABLE $rating_tbl (
        dimension INT(4) NOT NULL,
        raterpage INT(11) NOT NULL,
        rateepage INT(11) NOT NULL,
        ratingvalue FLOAT NOT NULL,
        rateeversion INT(11) NOT NULL,
        tstamp TIMESTAMP(14) NOT NULL,
)");
            $dbh->genericSqlQuery("CREATE UNIQUE INDEX rating"
                                  ." ON $rating_tbl (dimension, raterpage, rateepage)");
        }
        echo "  ",_("CREATED");
        break;
    case 'accesslog':
        $log_tbl = $prefix.'accesslog';
        // fields according to http://www.outoforder.cc/projects/apache/mod_log_sql/docs-2.0/#id2756178
        /*
A	User Agent agent	varchar(255)	Mozilla/4.0 (compat; MSIE 6.0; Windows)
a	CGi request arguments	request_args	varchar(255)	user=Smith&cart=1231&item=532
b	Bytes transfered	bytes_sent	int unsigned	32561
c???	Text of cookie	cookie	varchar(255)	Apache=sdyn.fooonline.net 1300102700823
f	Local filename requested	request_file	varchar(255)	/var/www/html/books-cycroad.html
H	HTTP request_protocol	request_protocol	varchar(10)	HTTP/1.1
h	Name of remote host	remote_host	varchar(50)	blah.foobar.com
I	Request ID (from modd_unique_id)	id	char(19)	POlFcUBRH30AAALdBG8
l	Ident user info	remote_logname	varcgar(50)	bobby
M	Machine ID???	machine_id	varchar(25)	web01
m	HTTP request method	request_method	varchar(10)	GET
P	httpd cchild PID	child_pid	smallint unsigned	3215
p	http port	server_port	smallint unsigned	80
R	Referer	referer	varchar(255)	http://www.biglinks4u.com/linkpage.html
r	Request in full form	request_line	varchar(255)	GET /books-cycroad.html HTTP/1.1
S	Time of request in UNIX time_t format	time_stamp	int unsigned	1005598029
T	Seconds to service request	request_duration	smallint unsigned	2
t	Time of request in human format	request_time	char(28)	[02/Dec/2001:15:01:26 -0800]
U	Request in simple form	request_uri	varchar(255)	/books-cycroad.html
u	User info from HTTP auth	remote_user	varchar(50)	bobby
v	Virtual host servicing the request	virtual_host	varchar(255)
        */
        $dbh->genericSqlQuery("
CREATE TABLE $log_tbl (
        time_stamp    int unsigned,
	remote_host   varchar(50),
	remote_user   varchar(50),
        request_method varchar(10),
	request_line  varchar(255),
	request_args  varchar(255),
	request_uri   varchar(255),
	request_time  char(28),
	status 	      smallint unsigned,
	bytes_sent    smallint unsigned,
        referer       varchar(255), 
	agent         varchar(255),
	request_duration float
)");
        $dbh->genericSqlQuery("CREATE INDEX log_time ON $log_tbl (time_stamp)");
        $dbh->genericSqlQuery("CREATE INDEX log_host ON $log_tbl (remote_host)");
        echo "  ",_("CREATED");
        break;
    }
    echo "<br />\n";
}

/**
 * Update from ~1.3.4 to current.
 * Only session, user, pref and member
 * jeffs-hacks database api (around 1.3.2) later:
 *   people should export/import their pages if using that old versions.
 */
function CheckDatabaseUpdate(&$request) {
    global $DBParams, $DBAuthParams;
    if (!in_array($DBParams['dbtype'], array('SQL','ADODB','PDO'))) return;
    echo "<h3>",_("check for necessary database updates"), " - ", $DBParams['dbtype'], "</h3>\n";

    $dbh = $request->getDbh(); 
    $dbadmin = $request->getArg('dbadmin');
    _upgrade_db_init($dbh);
    if (isset($dbadmin['cancel'])) {
        echo _("CANCEL")," <br />\n";
        return;
    }

    $tables = $dbh->_backend->listOfTables();
    $backend_type = $dbh->_backend->backendType();
    echo "<h4>",_("Backend type: "),$backend_type,"</h4>\n";
    $prefix = isset($DBParams['prefix']) ? $DBParams['prefix'] : '';
    foreach (explode(':','session:user:pref:member') as $table) {
        echo sprintf(_("check for table %s"), $table)," ...";
    	if (!in_array($prefix.$table, $tables)) {
            installTable($dbh, $table, $backend_type);
    	} else {
    	    echo _("OK")," <br />\n";
        }
    }
    if (ACCESS_LOG_SQL) {
        $table = "accesslog";
        echo sprintf(_("check for table %s"), $table)," ...";
    	if (!in_array($prefix.$table, $tables)) {
            installTable($dbh, $table, $backend_type);
    	} else {
    	    echo _("OK")," <br />\n";
        }
    }
    $backend = &$dbh->_backend->_dbh;
    extract($dbh->_backend->_table_names);

    // 1.3.8 added session.sess_ip
    if (phpwiki_version() >= 1030.08 and USE_DB_SESSION and isset($request->_dbsession)) {
  	echo _("check for new session.sess_ip column")," ... ";
  	$database = $dbh->_backend->database();
  	assert(!empty($DBParams['db_session_table']));
        $session_tbl = $prefix . $DBParams['db_session_table'];
        $sess_fields = $dbh->_backend->listOfFields($database, $session_tbl);
        if (!strstr(strtolower(join(':', $sess_fields)), "sess_ip")) {
            // TODO: postgres test (should be able to add columns at the end, but not in between)
            echo "<b>",_("ADDING"),"</b>"," ... ";		
            $dbh->genericSqlQuery("ALTER TABLE $session_tbl ADD sess_ip CHAR(15) NOT NULL");
            $dbh->genericSqlQuery("CREATE INDEX sess_date ON $session_tbl (sess_date)");
        } else {
            echo _("OK");
        }
        echo "<br />\n";
        if (substr($backend_type,0,5) == 'mysql') {
            // upgrade to 4.1.8 destroyed my session table: 
            // sess_id => varchar(10), sess_data => varchar(5). For others obviously also.
  	    echo _("check for mysql session.sess_id sanity")," ... ";
            $result = $dbh->genericSqlQuery("DESCRIBE $session_tbl");
            if ($DBParams['dbtype'] == 'SQL') {
            	$iter = new WikiDB_backend_PearDB_generic_iter($backend, $result);
            } elseif ($DBParams['dbtype'] == 'ADODB') {
            	$iter = new WikiDB_backend_ADODB_generic_iter($backend, $result, 
            		        array("Field", "Type", "Null", "Key", "Default", "Extra"));
            } elseif ($DBParams['dbtype'] == 'PDO') {
            	$iter = new WikiDB_backend_PDO_generic_iter($backend, $result);
            }
            while ($col = $iter->next()) {
                if ($col["Field"] == 'sess_id' and !strstr(strtolower($col["Type"]), 'char(32)')) {
            	    $dbh->genericSqlQuery("ALTER TABLE $session_tbl CHANGE sess_id"
                                          ." sess_id CHAR(32) NOT NULL");
            	    echo "sess_id ", $col["Type"], " ", _("fixed"), " =&gt; CHAR(32) ";
            	}
            	if ($col["Field"] == 'sess_ip' and !strstr(strtolower($col["Type"]), 'char(15)')) {
            	    $dbh->genericSqlQuery("ALTER TABLE $session_tbl CHANGE sess_ip"
                                          ." sess_ip CHAR(15) NOT NULL");
            	    echo "sess_ip ", $col["Type"], " ", _("fixed"), " =&gt; CHAR(15) ";
            	}
            }
            echo _("OK"), "<br />\n";
        }
    }

    // mysql >= 4.0.4 requires LOCK TABLE privileges
    if (substr($backend_type,0,5) == 'mysql'/* and $DBParams['dbtype'] != 'PDO' */) {
  	echo _("check for mysql LOCK TABLE privilege")," ...";
        $mysql_version = $dbh->_backend->_serverinfo['version'];
        if ($mysql_version > 400.40) {
            if (!empty($dbh->_backend->_parsedDSN))
                $parseDSN = $dbh->_backend->_parsedDSN;
            elseif (function_exists('parseDSN')) // ADODB or PDO
                $parseDSN = parseDSN($DBParams['dsn']);
            else 			     // pear
                $parseDSN = DB::parseDSN($DBParams['dsn']);
            $username = $dbh->_backend->qstr($parseDSN['username']);
            // on db level
            $query = "SELECT lock_tables_priv FROM mysql.db WHERE user='$username'";
            //mysql_select_db("mysql", $dbh->_backend->connection());
            $db_fields = $dbh->_backend->listOfFields("mysql", "db");
            if (!strstr(strtolower(join(':', $db_fields)), "lock_tables_priv")) {
                echo join(':', $db_fields);
            	die("lock_tables_priv missing. The DB Admin must run mysql_fix_privilege_tables");
            }
            $row = $dbh->_backend->getRow($query);
            if (isset($row[0]) and $row[0] == 'N') {
                $dbh->genericSqlQuery("UPDATE mysql.db SET lock_tables_priv='Y'"
                                      ." WHERE mysql.user='$username'");
                $dbh->genericSqlQuery("FLUSH PRIVILEGES");
                echo "mysql.db user='$username'", _("fixed"), "<br />\n";
            } elseif (!$row) {
                // or on user level
                $query = "SELECT lock_tables_priv FROM mysql.user WHERE user='$username'";
                $row = $dbh->_backend->getRow($query);
                if ($row and $row[0] == 'N') {
                    $dbh->genericSqlQuery("UPDATE mysql.user SET lock_tables_priv='Y'"
                                          ." WHERE mysql.user='$username'");
                    $dbh->genericSqlQuery("FLUSH PRIVILEGES");
                    echo "mysql.user user='$username'", _("fixed"), "<br />\n";
                } elseif (!$row) {
                    echo " <b><font color=\"red\">", _("FAILED"), "</font></b>: ",
                        "Neither mysql.db nor mysql.user has a user='$username'"
                        ." or the lock_tables_priv field",
                        "<br />\n";
                } else {
                    echo _("OK"), "<br />\n";
                }
            } else {
                echo _("OK"), "<br />\n";
            }
            //mysql_select_db($dbh->_backend->database(), $dbh->_backend->connection());
        } else {
            echo sprintf(_("version <em>%s</em> not affected"), $mysql_version),"<br />\n";
        }
    }

    // 1.3.10 mysql requires page.id auto_increment
    // mysql, mysqli or mysqlt
    if (phpwiki_version() >= 1030.099 and substr($backend_type,0,5) == 'mysql' 
        and $DBParams['dbtype'] != 'PDO') {
  	echo _("check for mysql page.id auto_increment flag")," ...";
        assert(!empty($page_tbl));
  	$database = $dbh->_backend->database();
  	$fields = mysql_list_fields($database, $page_tbl, $dbh->_backend->connection());
  	$columns = mysql_num_fields($fields); 
        for ($i = 0; $i < $columns; $i++) {
            if (mysql_field_name($fields, $i) == 'id') {
            	$flags = mysql_field_flags($fields, $i);
                //DONE: something was wrong with ADODB here.
            	if (!strstr(strtolower($flags), "auto_increment")) {
                    echo "<b>",_("ADDING"),"</b>"," ... ";
                    // MODIFY col_def valid since mysql 3.22.16,
                    // older mysql's need CHANGE old_col col_def
                    $dbh->genericSqlQuery("ALTER TABLE $page_tbl CHANGE id"
                                          ." id INT NOT NULL AUTO_INCREMENT");
                    $fields = mysql_list_fields($database, $page_tbl);
                    if (!strstr(strtolower(mysql_field_flags($fields, $i)), "auto_increment"))
                        echo " <b><font color=\"red\">", _("FAILED"), "</font></b><br />\n";
                    else     
                        echo _("OK"), "<br />\n";
            	} else {
                    echo _("OK"), "<br />\n";
            	}
            	break;
            }
        }
        mysql_free_result($fields);
    }

    // Check for mysql 4.1.x/5.0.0a binary search problem.
    //   http://bugs.mysql.com/bug.php?id=4398
    // "select * from page where LOWER(pagename) like '%search%'" does not apply LOWER!
    // Confirmed for 4.1.0alpha,4.1.3-beta,5.0.0a; not yet tested for 4.1.2alpha,
    // On windows only, though utf8 would be useful elsewhere also.
    // Illegal mix of collations (latin1_bin,IMPLICIT) and 
    // (utf8_general_ci, COERCIBLE) for operation '='])
    if (isWindows() and substr($backend_type,0,5) == 'mysql') {
  	echo _("check for mysql 4.1.x/5.0.0 binary search on windows problem")," ...";
        $mysql_version = $dbh->_backend->_serverinfo['version'];
        if ($mysql_version < 401.0) { 
            echo sprintf(_("version <em>%s</em>"), $mysql_version)," ",
                _("not affected"),"<br />\n";
        } elseif ($mysql_version >= 401.6) { // FIXME: since which version?
            $row = $dbh->_backend->getRow("SHOW CREATE TABLE $page_tbl");
            $result = join(" ", $row);
            if (strstr(strtolower($result), "character set") 
                and strstr(strtolower($result), "collate")) 
            {
                echo _("OK"), "<br />\n";
            } else {
                //SET CHARACTER SET latin1
                $charset = CHARSET;
                if ($charset == 'iso-8859-1') $charset = 'latin1';
                $dbh->genericSqlQuery("ALTER TABLE $page_tbl CHANGE pagename "
                                      ."pagename VARCHAR(100) "
                                      ."CHARACTER SET '$charset' COLLATE '$charset"."_bin' NOT NULL");
                echo sprintf(_("version <em>%s</em>"), $mysql_version), 
                    " <b>",_("FIXED"),"</b>",
                    "<br />\n";
            }
        } elseif ($DBParams['dbtype'] != 'PDO') {
            // check if already fixed
            extract($dbh->_backend->_table_names);
            assert(!empty($page_tbl));
  	    $database = $dbh->_backend->database();
  	    $fields = mysql_list_fields($database, $page_tbl, $dbh->_backend->connection());
  	    $columns = mysql_num_fields($fields); 
            for ($i = 0; $i < $columns; $i++) {
                if (mysql_field_name($fields, $i) == 'pagename') {
            	    $flags = mysql_field_flags($fields, $i);
                    // I think it was fixed with 4.1.6, but I tested it only with 4.1.8
                    if ($mysql_version > 401.0 and $mysql_version < 401.6) {
                    	// remove the binary flag
            	        if (strstr(strtolower($flags), "binary")) {
            	            // FIXME: on duplicate pagenames this will fail!
                            $dbh->genericSqlQuery("ALTER TABLE $page_tbl CHANGE pagename"
                                                  ." pagename VARCHAR(100) NOT NULL");
                            echo sprintf(_("version <em>%s</em>"), $mysql_version), 
                                "<b>",_("FIXED"),"</b>"
                                ,"<br />\n";	
            	        }
                    }
                    break;
                }
            }
        }
    }
    if ((ACCESS_LOG_SQL & 2)) {
    	echo _("check for ACCESS_LOG_SQL passwords in POST requests")," ...";
        // Don't display passwords in POST requests (up to 2005-02-04 12:03:20)
        $result = $dbh->genericSqlQuery(
                    "UPDATE ".$prefix."accesslog"
                    .' SET request_args=CONCAT(left(request_args, LOCATE("s:6:\"passwd\"",request_args)+12),"...")'
                    .' WHERE LOCATE("s:6:\"passwd\"", request_args)'
                    .' AND NOT(LOCATE("s:6:\"passwd\";s:15:\"<not displayed>\"", request_args))'
                    .' AND request_method="POST"');
        if ((DATABASE_TYPE == 'SQL' and $backend->AffectedRows()) 
            or (DATABASE_TYPE == 'ADODB' and $backend->Affected_Rows())
            or (DATABASE_TYPE == 'PDO' and $result))
            echo "<b>",_("FIXED"),"</b>", "<br />\n";
        else 
            echo _("OK"),"<br />\n";
    }
    _upgrade_cached_html($dbh);

    return;
}

function _upgrade_db_init (&$dbh) {
    global $request, $DBParams, $DBAuthParams;
    if (!in_array($DBParams['dbtype'], array('SQL','ADODB','PDO'))) return;

    if (DBADMIN_USER) {
        // if need to connect as the root user, for CREATE and ALTER privileges
        $AdminParams = $DBParams;
        if ($DBParams['dbtype'] == 'SQL')
            $dsn = DB::parseDSN($AdminParams['dsn']);
        else // ADODB or PDO
            $dsn = parseDSN($AdminParams['dsn']);
        $AdminParams['dsn'] = sprintf("%s://%s:%s@%s/%s",
                                      $dsn['phptype'],
                                      DBADMIN_USER,
                                      DBADMIN_PASSWD,
                                      $dsn['hostspec'],
                                      $dsn['database']);
        if (DEBUG & _DEBUG_SQL and $DBParams['dbtype'] == 'PDO') {
            echo "<br>\nDBParams['dsn']: '", $DBParams['dsn'], "'";
            echo "<br>\ndsn: '", print_r($dsn), "'";
            echo "<br>\nAdminParams['dsn']: '", $AdminParams['dsn'], "'";
        }
        $dbh = WikiDB::open($AdminParams);
    } elseif ($dbadmin = $request->getArg('dbadmin')) {
        if (empty($dbadmin['user']) or isset($dbadmin['cancel']))
            $dbh = &$request->_dbi;
        else {
            $AdminParams = $DBParams;
            if ($DBParams['dbtype'] == 'SQL')
                $dsn = DB::parseDSN($AdminParams['dsn']);
            else
                $dsn = parseDSN($AdminParams['dsn']);
            $AdminParams['dsn'] = sprintf("%s://%s:%s@%s/%s",
                                      $dsn['phptype'],
                                      $dbadmin['user'],
                                      $dbadmin['passwd'],
                                      $dsn['hostspec'],
                                      $dsn['database']);
            $dbh = WikiDB::open($AdminParams);
        }
    } else {
        // Check if the privileges are enough. Need CREATE and ALTER perms. 
        // And on windows: SELECT FROM mysql, possibly: UPDATE mysql.
        $form = HTML::form(array("method" => "post", 
                                 "action" => $request->getPostURL(),
                                 "accept-charset"=>$GLOBALS['charset']),
HTML::p(_("Upgrade requires database privileges to CREATE and ALTER the phpwiki database."),
                                   HTML::br(),
_("And on windows at least the privilege to SELECT FROM mysql, and possibly UPDATE mysql")),
                           HiddenInputs(array('action' => 'upgrade')),
                           HTML::table(array("cellspacing"=>4),
                                       HTML::tr(HTML::td(array('align'=>'right'),
                                                         _("DB admin user:")),
                                                HTML::td(HTML::input(array('name'=>"dbadmin[user]",
                                                                           'size'=>12,
                                                                           'maxlength'=>256,
                                                                           'value'=>'root')))),
                                       HTML::tr(HTML::td(array('align'=>'right'),
                                                         _("DB admin password:")),
                                                HTML::td(HTML::input(array('name'=>"dbadmin[passwd]",
                                                                           'type'=>'password',
                                                                           'size'=>12,
                                                                           'maxlength'=>256)))),
                                       HTML::tr(HTML::td(array('align'=>'center', 'colspan' => 2),
                                                         Button("submit:", _("Submit"), 'wikiaction'), 
                                                         HTML::raw('&nbsp;'),
                                                         Button("submit:dbadmin[cancel]", _("Cancel"), 
                                                                'button')))));
        $form->printXml();
        echo "</div><!-- content -->\n";
        echo asXML(Template("bottom"));
        echo "</body></html>\n";
        $request->finish();
        exit();
    }
}

/**
 * if page.cached_html does not exists:
 *   put _cached_html from pagedata into a new seperate blob, not huge serialized string.
 *
 * it is only rarelely needed: for current page only, if-not-modified
 * but was extracetd for every simple page iteration.
 */
function _upgrade_cached_html (&$dbh, $verbose=true) {
    global $DBParams;
    if (!in_array($DBParams['dbtype'], array('SQL','ADODB'))) return;
    $count = 0;
    if (phpwiki_version() >= 1030.10) {
        if ($verbose)
            echo _("check for extra page.cached_html column")," ... ";
  	$database = $dbh->_backend->database();
        extract($dbh->_backend->_table_names);
        $fields = $dbh->_backend->listOfFields($database, $page_tbl);
        if (!strstr(strtolower(join(':', $fields)), "cached_html")) {
            if ($verbose)
                echo "<b>",_("ADDING"),"</b>"," ... ";
            $backend_type = $dbh->_backend->backendType();
            if (substr($backend_type,0,5) == 'mysql')
                $dbh->genericSqlQuery("ALTER TABLE $page_tbl ADD cached_html MEDIUMBLOB");
            else
                $dbh->genericSqlQuery("ALTER TABLE $page_tbl ADD cached_html BLOB");
            if ($verbose)
                echo "<b>",_("CONVERTING"),"</b>"," ... ";
            $count = _convert_cached_html($dbh);
            if ($verbose)
                echo $count, " ", _("OK"), "<br />\n";
        } else {
            if ($verbose)
                echo _("OK"), "<br />\n";
        }
    }
    return $count;
}

/** 
 * move _cached_html for all pages from pagedata into a new seperate blob.
 * decoupled from action=upgrade, so that it can be used by a WikiAdminUtils button also.
 */
function _convert_cached_html (&$dbh) {
    global $DBParams;
    if (!in_array($DBParams['dbtype'], array('SQL','ADODB'))) return;

    $pages = $dbh->getAllPages();
    $cache =& $dbh->_cache;
    $count = 0;
    extract($dbh->_backend->_table_names);
    while ($page = $pages->next()) {
        $pagename = $page->getName();
        $data = $dbh->_backend->get_pagedata($pagename);
        if (!empty($data['_cached_html'])) {
            $cached_html = $data['_cached_html'];
            $data['_cached_html'] = '';
            $cache->update_pagedata($pagename, $data);
            // store as blob, not serialized
            $dbh->genericSqlQuery("UPDATE $page_tbl SET cached_html=? WHERE pagename=?",
                                  array($cached_html, $pagename));
            $count++;
        }
    }
    return $count;
}

function CheckPluginUpdate(&$request) {
    echo "<h3>",_("check for necessary plugin argument updates"),"</h3>\n";
    $process = array('msg' => _("change RandomPage pages => numpages"),
                     'match' => "/(<\?\s*plugin\s+ RandomPage\s+)pages/",
                     'replace' => "\\1numpages");
    $dbi = $request->getDbh();
    $allpages = $dbi->getAllPages(false);
    while ($page = $allpages->next()) {
        $current = $page->getCurrentRevision();
        $pagetext = $current->getPackedContent();
        foreach ($process as $p) {
            if (preg_match($p['match'], $pagetext)) {
                echo $page->getName()," ",$p['msg']," ... ";
                if ($newtext = preg_replace($p['match'], $p['replace'], $pagetext)) {
                    $meta = $current->_data;
                    $meta['summary'] = "upgrade: ".$p['msg'];
                    $page->save($newtext, $current->getVersion() + 1, $meta);
                    echo _("OK"), "<br />\n";
                } else {
                    echo " <b><font color=\"red\">", _("FAILED"), "</font></b><br />\n";
                }
            }
        }
    }
}

function fixConfigIni($match, $new) {
    $file = FindFile("config/config.ini");
    $found = false;
    if (is_writable($file)) {
        $in = fopen($file,"rb");
        $out = fopen($tmp = tempnam(FindFile("uploads"),"cfg"),"wb");
        if (isWindows())
            $tmp = str_replace("/","\\",$tmp);
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
            @rename($file,"$file.bak");
            if (rename($tmp, $file))
                echo " <b>",_("FIXED"),"</b>";
            else {
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

function CheckConfigUpdate(&$request) {
    echo "<h3>",_("check for necessary config updates"),"</h3>\n";
    echo _("check for old CACHE_CONTROL = NONE")," ... ";
    if (defined('CACHE_CONTROL') and CACHE_CONTROL == '') {
        echo "<br />&nbsp;&nbsp;",
            _("CACHE_CONTROL is set to 'NONE', and must be changed to 'NO_CACHE'"),
            " ...";
        fixConfigIni("/^\s*CACHE_CONTROL\s*=\s*NONE/","CACHE_CONTROL = NO_CACHE");
    } else {
        echo _("OK");
    }
    echo "<br />\n";
    echo _("check for GROUP_METHOD = NONE")," ... ";
    if (defined('GROUP_METHOD') and GROUP_METHOD == '') {
        echo "<br />&nbsp;&nbsp;",
            _("GROUP_METHOD is set to NONE, and must be changed to \"NONE\""),
            " ...";
        fixConfigIni("/^\s*GROUP_METHOD\s*=\s*NONE/","GROUP_METHOD = \"NONE\"");
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
function DoUpgrade($request) {
	

    if (!$request->_user->isAdmin()) {
        $request->_notAuthorized(WIKIAUTH_ADMIN);
        $request->finish(
                         HTML::div(array('class' => 'disabled-plugin'),
                                   fmt("Upgrade disabled: user != isAdmin")));
        return;
    }
    
    //print("<br>This action is blocked by administrator. Sorry for the inconvenience !<br>");
    exit("<br>This action is blocked by administrator. Sorry for the inconvenience !<br>");
    StartLoadDump($request, _("Upgrading this PhpWiki"));
    //CheckOldIndexUpdate($request); // to upgrade from < 1.3.10
    CheckDatabaseUpdate($request);   // first check cached_html and friends
    CheckActionPageUpdate($request);
    CheckPgsrcUpdate($request);
    //CheckThemeUpdate($request);
    //CheckPluginUpdate($request);
    CheckConfigUpdate($request);
    EndLoadDump($request);
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
?>