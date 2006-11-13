<?php // -*-php-*-
rcs_id('$Id$');

require_once('lib/WikiDB/backend/ADODB.php');

/*
 * PROBLEM: mysql seems to be the simpliest (or most stupid) db on earth. 
 * (tested with 4.0.18)
 * Whenever a table is write-locked, you cannot even write to other unrelated 
 * tables. So it seems that we have to lock all tables!
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
            $this->_serverinfo['version'] = (string)(($arr[0] * 100) + $arr[1]) . "." . $arr[2];
        }
        if ($this->_serverinfo['version'] < 323.0) {
            // Older MySQL's don't have CASE WHEN ... END
            $this->_expressions['maxmajor'] = "MAX(IF(minor_edit=0,version,0))";
            $this->_expressions['maxminor'] = "MAX(IF(minor_edit<>0,version,0))";
        }
    }
    
    /**
     * Pack tables.
     */
    function optimize() {
        $dbh = &$this->_dbh;
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
