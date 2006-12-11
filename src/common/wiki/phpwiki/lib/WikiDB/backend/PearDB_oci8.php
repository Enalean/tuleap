<?php // -*-php-*-
rcs_id('$Id: PearDB_oci8.php,v 1.9 2005/11/14 22:24:33 rurban Exp $');

/**
 * Oracle extensions for the Pear DB backend.
 * @author: Philippe.Vanhaesendonck@topgame.be
 */

require_once('lib/WikiDB/backend/PearDB_pgsql.php');

class WikiDB_backend_PearDB_oci8
extends WikiDB_backend_PearDB_pgsql
{
    /**
     * Constructor
     */
    function WikiDB_backend_PearDB_oci8($dbparams) {
        // Backend constructor
        $this->WikiDB_backend_PearDB($dbparams);
        
        // Empty strings are NULLS
        $this->_expressions['notempty'] = "IS NOT NULL";
        $this->_expressions['iscontent'] = "DECODE(DBMS_LOB.GETLENGTH(content), NULL, 0, 0, 0, 1)";

        // Set parameters:
        $dbh = &$this->_dbh;
        // - No persistent conections (I don't like them)
        $dbh->setOption('persistent', false);
        // - Set lowercase compatibility option
        // - Set numrows as well -- sure why this is needed, but some queries 
        //   are triggering DB_ERROR_NOT_CAPABLE
        $dbh->setOption('portability',
            DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_NULL_TO_EMPTY | DB_PORTABILITY_NUMROWS);
    }

            
    /**
     * Pack tables.
     */
    function optimize() {
        // Do nothing here -- Leave that for the DBA
        // Cost Based Optimizer tuning vary from version to version
        return 1;
    }

    /**
     * Lock all tables we might use.
     */
    function _lock_tables($write_lock=true) {
        $dbh = &$this->_dbh;
        
        // Not sure if we really need to lock tables here, the Oracle row
        // locking mechanism should be more than enough
        // For the time being, lets stay on the safe side and lock...
        if ($write_lock) {
            // Next line is default behaviour, so just skip it
            // $dbh->query("SET TRANSACTION READ WRITE");
            foreach ($this->_table_names as $table) {
                $dbh->query("LOCK TABLE $table IN EXCLUSIVE MODE");
            }
        } else {
            // Just ensure read consistency
            $dbh->query("SET TRANSACTION READ ONLY");
        }
    }
};

class WikiDB_backend_PearDB_oci8_search
extends WikiDB_backend_PearDB_search
{
    // If we want case insensitive search, one need to create a Context
    // Index on the CLOB. While it is very efficient, it requires the
    // Intermedia Text option, so let's stick to the 'simple' thing
    // Note that this does only an exact fulltext search, not using MATCH or LIKE.
    function _fulltext_match_clause($node) {
        if ($this->isStoplisted($node))
            return "1=1";
        $page = $node->sql();
        $exactword = $node->_sql_quote($node->word);
        return ($this->_case_exact
                ? "pagename LIKE '$page' OR DBMS_LOB.INSTR(content, '$exactword') > 0"
                : "LOWER(pagename) LIKE '$page' OR DBMS_LOB.INSTR(content, '$exactword') > 0");
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