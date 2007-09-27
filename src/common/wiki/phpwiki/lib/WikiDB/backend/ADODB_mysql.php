<?php // -*-php-*-
rcs_id('$Id: ADODB_mysql.php,v 1.15 2005/04/10 10:43:25 rurban Exp $');

require_once('lib/WikiDB/backend/ADODB.php');

/*
 * PROBLEM: mysql seems to be the simpliest (or most stupid) db on earth. 
 * (tested with 4.0.18)
 * Whenever a table is write-locked, you cannot even write to other unrelated 
 * tables. So it seems that we have to lock all tables!
 * As workaround we try it with application locks, uniquely named locks, 
 * to prevent from concurrent writes of locks with the same name.
 * The lock name is a strcat of the involved tables.
 */
define('DO_APP_LOCK',true);
define('DO_FULL_LOCK',false);

/**
 * WikiDB layer for ADODB-mysql, called by lib/WikiDB/ADODB.php.
 * Now with support for the newer adodb library, the adodb extension library 
 * and more database drivers.
 * To use transactions use the mysqlt driver: "mysqlt:..."
 * 
 * @author: Lawrence Akka, Reini Urban
 */
class WikiDB_backend_ADODB_mysql
extends WikiDB_backend_ADODB
{
    /**
     * Constructor.
     */
    function WikiDB_backend_ADODB_mysql($dbparams) {
        $this->WikiDB_backend_ADODB($dbparams);

        $this->_serverinfo = $this->_dbh->ServerInfo();
        if (!empty($this->_serverinfo['version'])) {
            $arr = explode('.',$this->_serverinfo['version']);
            $this->_serverinfo['version'] = (string)(($arr[0] * 100) + $arr[1]) . "." . (integer)$arr[2];
        }
        if ($this->_serverinfo['version'] < 323.0) {
            // Older MySQL's don't have CASE WHEN ... END
            $this->_expressions['maxmajor'] = "MAX(IF(minor_edit=0,version,0))";
            $this->_expressions['maxminor'] = "MAX(IF(minor_edit<>0,version,0))";
        }

        // esp. needed for utf databases
        if ($this->_serverinfo['version'] > 401.0) {
            global $charset;
            $aliases = array('iso-8859-1' => 'latin1',
                             'utf-8'      => 'utf8');
            //http://dev.mysql.com/doc/mysql/en/charset-connection.html
            if (isset($aliases[strtolower($charset)])) {
                // mysql needs special unusual names and doesn't resolve aliases
                mysql_query("SET NAMES '". $aliases[$charset] . "'");
            } else {
                mysql_query("SET NAMES '$charset'");
            }
        }
    }
    
    /**
     * Kill timed out processes. ( so far only called on about every 50-th save. )
     */
    function _timeout() {
    	if (empty($this->_dbparams['timeout'])) return;
	$result = mysql_query("SHOW processlist");
	while ($row = mysql_fetch_array($result)) { 
	    if ($row["db"] == $this->_dsn['database']
	        and $row["User"] == $this->_dsn['username']
	        and $row["Time"] > $this->_dbparams['timeout']
	        and $row["Command"] == "Sleep")
            {
                $process_id = $row["Id"]; 
                mysql_query("KILL $process_id");
	    }
	}
    }

    /**
     * Pack tables.
     */
    function optimize() {
        $dbh = &$this->_dbh;
        $this->_timeout();
        foreach ($this->_table_names as $table) {
            $dbh->Execute("OPTIMIZE TABLE $table");
        }
        return 1;
    }

    /**
     * Lock tables. As fine-grained application lock, which locks only the same transaction
     * (conflicting updates and edits), and as full table write lock.
     *
     * New: which tables as params,
     *      support nested locks via app locks
     *
     */
    function _lock_tables($tables, $write_lock = true) {
    	if (!$tables) return;
    	if (DO_APP_LOCK) {
            $lock = join('-',$tables);
            $result = $this->_dbh->GetRow("SELECT GET_LOCK('$lock',10)");
            if (!$result or $result[0] == 0) {
                trigger_error( "WARNING: Couldn't obtain application lock " . $lock . "\n<br />",
                               E_USER_WARNING);
                return;                          
            }
    	}
        if (DO_FULL_LOCK) {
            // if this is not enough:
            $lock_type = $write_lock ? "WRITE" : "READ";
            foreach ($this->_table_names as $key => $table) {
                $locks[] = "$table $lock_type";
            }
            $this->_dbh->Execute("LOCK TABLES " . join(",", $locks));
        }
    }

    /**
     * Release the locks.
     * Support nested locks
     */
    function _unlock_tables($tables) {
    	if (!$tables) {
    	    $this->_dbh->Execute("UNLOCK TABLES");
    	    return;
    	}
        if (DO_APP_LOCK) {
            $lock = join('-',$tables);
            $result = $this->_dbh->Execute("SELECT RELEASE_LOCK('$lock')");
        }
        if (DO_FULL_LOCK) {
            // if this is not enough:
            $this->_dbh->Execute("UNLOCK TABLES");
        }
    }

    function increaseHitCount($pagename) {
        $dbh = &$this->_dbh;
        // Hits is the only thing we can update in a fast manner.
        // Note that this will fail silently if the page does not
        // have a record in the page table.  Since it's just the
        // hit count, who cares?
        // LIMIT since 3.23
        $dbh->Execute(sprintf("UPDATE LOW_PRIORITY %s SET hits=hits+1 WHERE pagename=%s %s",
                              $this->_table_names['page_tbl'],
                              $dbh->qstr($pagename),
                              ($this->_serverinfo['version'] >= 323.0) ? "LIMIT 1": "")
                              );
        return;
    }

    function _get_pageid($pagename, $create_if_missing = false) {

        // check id_cache
        global $request;
        $cache =& $request->_dbi->_cache->_id_cache;
        if (isset($cache[$pagename])) {
            if ($cache[$pagename] or !$create_if_missing) {
                return $cache[$pagename];
            }
        }
        
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
        $query = sprintf("SELECT id FROM $page_tbl WHERE pagename=%s",
                         $dbh->qstr($pagename));
        if (! $create_if_missing ) {
            $row = $dbh->GetRow($query);
            return $row ? $row[0] : false;
        }
        $row = $dbh->GetRow($query);
        if (! $row ) {
	    // have auto-incrementing, atomic version
	    $rs = $dbh->Execute(sprintf("INSERT INTO $page_tbl"
					. " (id,pagename)"
					. " VALUES(NULL,%s)",
					$dbh->qstr($pagename)));
	    $id = $dbh->_insertid();
        } else {
            $id = $row[0];
        }
        assert($id);
        return $id;
    }

    /**
     * Create a new revision of a page.
     */
    function set_versiondata($pagename, $version, $data) {
        $dbh = &$this->_dbh;
        $version_tbl = $this->_table_names['version_tbl'];
        
        $minor_edit = (int) !empty($data['is_minor_edit']);
        unset($data['is_minor_edit']);
        
        $mtime = (int)$data['mtime'];
        unset($data['mtime']);
        assert(!empty($mtime));

        @$content = (string) $data['%content'];
        unset($data['%content']);
        unset($data['%pagedata']);
        
        $this->lock(array('page','recent','version','nonempty'));
        $dbh->BeginTrans( );
        $dbh->CommitLock($version_tbl);
        $id = $this->_get_pageid($pagename, true);
        $backend_type = $this->backendType();
        // optimize: mysql can do this with one REPLACE INTO.
	$rs = $dbh->Execute(sprintf("REPLACE INTO $version_tbl"
				    . " (id,version,mtime,minor_edit,content,versiondata)"
				    . " VALUES(%d,%d,%d,%d,%s,%s)",
				    $id, $version, $mtime, $minor_edit,
				    $dbh->qstr($content),
				    $dbh->qstr($this->_serialize($data))));
        $this->_update_recent_table($id);
        $this->_update_nonempty_table($id);
        if ($rs) $dbh->CommitTrans( );
        else $dbh->RollbackTrans( );
        $this->unlock(array('page','recent','version','nonempty'));
    }
    
};

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>