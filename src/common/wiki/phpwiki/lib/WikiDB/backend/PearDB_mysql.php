<?php // -*-php-*-
rcs_id('$Id: PearDB_mysql.php,v 1.21 2005/10/10 19:42:15 rurban Exp $');

require_once('lib/WikiDB/backend/PearDB.php');

// The slowest function overall is mysql_connect with [680ms]
// 2nd is db_mysql::simpleQuery with [257ms]
class WikiDB_backend_PearDB_mysql
extends WikiDB_backend_PearDB
{
    /**
     * Constructor.
     */
    function __construct($dbparams) {
        parent::__construct($dbparams);
        //$this->_serverinfo = $this->_dbh->ServerInfo();
        $row = $this->_dbh->GetOne("SELECT version()");
        if (!DB::isError($row) and !empty($row)) {
            $arr = explode('.',$row);
            $this->_serverinfo['version'] = (string)(($arr[0] * 100) + $arr[1]) . 
                                            "." . (integer)$arr[2];
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
    }
    
    /**
     * Kill timed out processes. ( so far only called on about every 50-th save. )
     */
    function _timeout() {
    	if (empty($this->_dbparams['timeout'])) return;
	$result = mysql_query("SHOW processlist");
	while ($row = mysql_fetch_array($result)) { 
	    if ($row["db"] == $this->_dbh->dsn['database']
	        and $row["User"] == $this->_dbh->dsn['username']
	        and $row["Time"] > $this->_dbparams['timeout']
	        and $row["Command"] == "Sleep") 
            {
	            $process_id = $row["Id"]; 
	            mysql_query("KILL $process_id");
	    }
	}
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
        
        $this->lock();
        $id = $this->_get_pageid($pagename, true);
        // requires PRIMARY KEY (id,version)!
        // VALUES supported since mysql-3.22.5
        $dbh->query(sprintf("REPLACE INTO $version_tbl"
                            . " (id,version,mtime,minor_edit,content,versiondata)"
                            . " VALUES(%d,%d,%d,%d,'%s','%s')",
                            $id, $version, $mtime, $minor_edit,
                            $dbh->escapeSimple($content),
                            $dbh->escapeSimple($this->_serialize($data))
                            ));
        // real binding (prepare,execute) only since mysqli + PHP5
        $this->_update_recent_table($id);
        $this->_update_nonempty_table($id);
        $this->unlock();
    }

    function _update_recent_table($pageid = false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        extract($this->_expressions);

        $pageid = (int)$pageid;

        // optimized: mysql can do this with one REPLACE INTO.
        // supported in every (?) mysql version
        // requires PRIMARY KEY (id)!
        if ($pageid) {
            $stmt = " WHERE id=$pageid";
        } else {
            $stmt = " JOIN wiki_page USING (id) WHERE group_id = ".GROUP_ID;
        }
        $dbh->query("REPLACE INTO $recent_tbl"
                    . " (id, latestversion, latestmajor, latestminor)"
                    . " SELECT id, $maxversion, $maxmajor, $maxminor"
                    . " FROM $version_tbl"
                    . $stmt
                    . " GROUP BY id" );
    }

    /* ISNULL is mysql specific */
    function wanted_pages($exclude_from='', $exclude='', $sortby=false, $limit=false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);
        if ($orderby = $this->sortby($sortby, 'db', array('pagename','wantedfrom')))
            $orderby = 'ORDER BY ' . $orderby;

        if ($exclude_from) // array of pagenames
            $exclude_from = " AND linked.pagename NOT IN ".$this->_sql_set($exclude_from);
        if ($exclude) // array of pagenames
            $exclude = " AND $page_tbl.pagename NOT IN ".$this->_sql_set($exclude);

        $sql = "SELECT $page_tbl.pagename,linked.pagename as wantedfrom"
            . " FROM $page_tbl as linked, $link_tbl "
            . " LEFT JOIN $page_tbl ON ($link_tbl.linkto=$page_tbl.id)"
            . " LEFT JOIN $nonempty_tbl ON ($link_tbl.linkto=$nonempty_tbl.id)" 
            . " WHERE ISNULL($nonempty_tbl.id) AND linked.id=$link_tbl.linkfrom AND linked.group_id=".GROUP_ID
            . $exclude_from
            . $exclude
            . $orderby;
        if ($limit) {
            list($from, $count) = $this->limit($limit);
            $result = $dbh->limitQuery($sql, $from, $count * 3);
        } else {
            $result = $dbh->query($sql);
        }
        return new WikiDB_backend_PearDB_generic_iter($this, $result);
    }

    /* // REPLACE will not delete empy pages, so it was removed --ru
    function _update_nonempty_table($pageid = false) {
        $dbh = &$this->_dbh;
        extract($this->_table_names);

        $pageid = (int)$pageid;

        // Optimized: mysql can do this with one REPLACE INTO.
        // supported in every (?) mysql version
        // requires PRIMARY KEY (id)
        $dbh->query("REPLACE INTO $nonempty_tbl (id)"
                    . " SELECT $recent_tbl.id"
                    . " FROM $recent_tbl, $version_tbl"
                    . " WHERE $recent_tbl.id=$version_tbl.id"
                    . "       AND version=latestversion"
                    . "  AND content<>''"
                    . ( $pageid ? " AND $recent_tbl.id=$pageid" : ""));
    }
    */
   
    /**
     * Pack tables.
     */
    function optimize() {
        if(DATABASE_AUTO_OPTIMIZE) {        
            $dbh = &$this->_dbh;
	       $this->_timeout();
            foreach ($this->_table_names as $table) {
                $dbh->query("OPTIMIZE TABLE $table");
            }
            return 1;
        }else {
            return 0;
        }
    }

    /**
     * Lock tables.
     */
    function _lock_tables($write_lock = true) {
        $lock_type = $write_lock ? "WRITE" : "READ";
        foreach ($this->_table_names as $table) {
            $tables[] = "$table $lock_type";
        }
        $this->_dbh->query("LOCK TABLES " . join(",", $tables));
    }

    /**
     * Release all locks.
     */
    function _unlock_tables() {
        $this->_dbh->query("UNLOCK TABLES");
    }

    function increaseHitCount($pagename) {
        $dbh = &$this->_dbh;
        // Hits is the only thing we can update in a fast manner.
        // Note that this will fail silently if the page does not
        // have a record in the page table.  Since it's just the
        // hit count, who cares?
        // LIMIT since 3.23
        $dbh->query(sprintf("UPDATE LOW_PRIORITY %s SET hits=hits+1 WHERE pagename='%s' AND group_id=%d %s",
                            $this->_table_names['page_tbl'],
                            $dbh->escapeSimple($pagename),
                            GROUP_ID,
                            ($this->_serverinfo['version'] >= 323.0) ? "LIMIT 1": ""));
        return;
    }

};

class WikiDB_backend_PearDB_mysql_search
extends WikiDB_backend_PearDB_search
{
    function _pagename_match_clause($node) { 
        $word = $node->sql();
        if ($node->op == 'REGEX') { // posix regex extensions
            return "pagename REGEXP '$word'";
        } else {
            return ($this->_case_exact 
                    ? "pagename LIKE '$word'" 
                    : "LOWER(pagename) LIKE '$word'");
        }
    }
}

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
