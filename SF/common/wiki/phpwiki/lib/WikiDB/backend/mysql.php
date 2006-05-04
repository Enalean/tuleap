<?php // -*-php-*-
rcs_id('$Id: mysql.php 1422 2005-04-12 13:33:49Z guerin $');

require_once('lib/WikiDB/backend/PearDB.php');

class WikiDB_backend_mysql
extends WikiDB_backend_PearDB
{
    /**
     * Constructor.
     */
    function WikiDB_backend_mysql($dbparams) {
        $this->WikiDB_backend_PearDB($dbparams);

        // Older MySQL's don't have CASE WHEN ... END
        $this->_expressions['maxmajor'] = "MAX(IF(minor_edit=0,version,0))";
        $this->_expressions['maxminor'] = "MAX(IF(minor_edit<>0,version,0))";
    }
    
    /**
     * Pack tables.
     */
    function optimize() {
        $dbh = &$this->_dbh;
        foreach ($this->_table_names as $table) {
            $dbh->query("OPTIMIZE TABLE $table");
        }
        return 1;
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
