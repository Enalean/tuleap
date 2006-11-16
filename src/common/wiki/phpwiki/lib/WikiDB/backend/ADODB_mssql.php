<?php // -*-php-*-
rcs_id('$Id: ADODB_mssql.php,v 1.1 2004/10/12 13:50:56 rurban Exp $');

/**
 * MS SQL extensions for the ADODB DB backend.
 */

require_once('lib/WikiDB/backend/ADODB.php');

class WikiDB_backend_ADODB_mssql
extends WikiDB_backend_ADODB
{
    /**
     * Constructor.
     */
    function WikiDB_backend_ADODB_mssql($dbparams) {
        // Lowercase Assoc arrays
        define('ADODB_ASSOC_CASE',0);

        // Backend constructor
        $this->WikiDB_backend_ADODB($dbparams);

        // Empty strings in MSSQL?  NULLS?
        $this->_expressions['notempty'] = "NOT LIKE ''";
        // TEXT handling
        //$this->_expressions['iscontent'] = "content NOT LIKE ''";

        $this->_prefix = isset($dbparams['prefix']) ? $dbparams['prefix'] : '';

    }
    
    /**
     * Pack tables.
     */
    function optimize() {
        // Do nothing here -- Leave that for the DB
        // Cost Based Optimizer tuning vary from version to version
        return 1;
    }

    /**
     * Lock tables.
     *
     * We don't really need to lock exclusive, but I'll relax it when I fully 
     * understand phpWiki locking ;-)
     *
     */
    function _lock_tables($tables, $write_lock = true) {
        if (!$tables) return;

        $dbh = &$this->_dbh;
        if($write_lock) {
            // Next line is default behaviour, so just skip it
            // $dbh->Execute("SET TRANSACTION READ WRITE");
            foreach ($tables as $table) {
                if ($this->_prefix && !strstr($table, $this->_prefix)) {
                    $table = $this->_prefix . $table;
                }
                $dbh->Execute("LOCK TABLE $table IN EXCLUSIVE MODE");
            }
        } else {
            // Just ensure read consistency
            $dbh->Execute("SET TRANSACTION READ ONLY");
        }
    }

    /**
     * Release the locks.
     */
    function _unlock_tables($tables) {
        $dbh = &$this->_dbh;
        $dbh->Execute("COMMIT WORK");
    }

    // Search callabcks
    // Page name
    function _sql_match_clause($word) {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $word = $this->_dbh->qstr("%$word%");
        return "LOWER(pagename) LIKE $word";
    }

    // Fulltext -- case sensitive :-\
    function _fullsearch_sql_match_clause($word) {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $wordq = $this->_dbh->qstr("%$word%");
        return "LOWER(pagename) LIKE $wordq " 
               . "OR CHARINDEX(content, '$word') > 0";
    }

    /**
     * Serialize data
     */
    function _serialize($data) {
        if (empty($data))
            return '';
        assert(is_array($data));
        return serialize(addslashes($data));
    }

    /**
     * Unserialize data
     */
    function _unserialize($data) {
        return empty($data) ? array() : unserialize(stripslashes($data));
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
