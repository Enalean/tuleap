<?php // -*-php-*-
rcs_id('$Id$');

require_once('lib/ErrorManager.php');
require_once('lib/WikiDB/backend/PearDB.php');

class WikiDB_backend_pgsql
extends WikiDB_backend_PearDB
{
    function WikiDB_backend_pgsql($dbparams) {
        // The pgsql handler of (at least my version of) the PEAR::DB
        // library generates three warnings when a database is opened:
        //
        //     Undefined index: options
        //     Undefined index: tty
        //     Undefined index: port
        //
        // This stuff is all just to catch and ignore these warnings,
        // so that they don't get reported to the user.  (They are
        // not consequential.)  

        global $ErrorManager;
        $ErrorManager->pushErrorHandler(new WikiMethodCb($this,'_pgsql_open_error'));
        $this->WikiDB_backend_PearDB($dbparams);
        $ErrorManager->popErrorHandler();
    }

    function _pgsql_open_error($error) {
        if (preg_match('/^Undefined\s+index:\s+(options|tty|port)/',
                       $error->errstr))
            return true;        // Ignore error
        return false;
    }
            
    /**
     * Pack tables.
     */
    function optimize() {
        $dbh = &$this->_dbh;
        foreach ($this->_table_names as $table) {
            $dbh->query("VACUUM ANALYZE $table");
        }
        return 1;
    }

    /**
     * Lock all tables we might use.
     */
    function _lock_tables($write_lock = true) {
        $dbh = &$this->_dbh;
        
        $dbh->query("BEGIN WORK");
        foreach ($this->_table_names as $table) {
            // FIXME: can we use less restrictive locking.
            //        (postgres supports transactions, after all.)
            $dbh->query("LOCK TABLE $table");
        }
    }

    /**
     * Unlock all tables.
     */
    function _unlock_tables() {
        $dbh = &$this->_dbh;
        $dbh->query("COMMIT WORK");
    }

    
    // Use Postgres' caseless ILIKE
    function _sql_match_clause($word) {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $word = $this->_dbh->quoteString($word);
        return "pagename ILIKE '%$word%'";
    }

    // Use Postgres' caseless ILIKE
    function _fullsearch_sql_match_clause($word) {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $word = $this->_dbh->quoteString($word);
        return "pagename ILIKE '%$word%' OR content ILIKE '%$word%'";
    }


    /**
     * Serialize data
     */
    function _serialize($data) {
        if (empty($data))
            return '';
        assert(is_array($data));
        return base64_encode(serialize($data));
    }

    /**
     * Unserialize data
     */
    function _unserialize($data) {
        if (empty($data))
            return array();
        // Base64 encoded data does not contain colons.
        //  (only alphanumerics and '+' and '/'.)
        if (substr($data,0,2) == 'a:')
            return unserialize($data);
        return unserialize(base64_decode($data));
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
