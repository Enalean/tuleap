<?php // -*-php-*-
rcs_id('$Id: ADODB_oci8po.php,v 1.4 2005/11/14 22:24:33 rurban Exp $');

/**
 * Oracle extensions for the ADODB DB backend.
 * @author: Philippe.Vanhaesendonck@topgame.be
 */

require_once('lib/WikiDB/backend/ADODB.php');

class WikiDB_backend_ADODB_oci8po
extends WikiDB_backend_ADODB
{
    var $_prefix;

    /**
     * Constructor.
     */
    function WikiDB_backend_ADODB_oci8po($dbparams) {
        // Lowercase Assoc arrays
        define('ADODB_ASSOC_CASE',0);

        // Backend constructor
        $this->WikiDB_backend_ADODB($dbparams);

        // Empty strings are NULLS in Oracle
        $this->_expressions['notempty'] = "IS NOT NULL";
        // CLOB handling
        $this->_expressions['iscontent'] = "DECODE(DBMS_LOB.GETLENGTH(content), NULL, 0, 0, 0, 1)";

        $this->_prefix = isset($dbparams['prefix']) ? $dbparams['prefix'] : '';
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

    // Search callbacks (replaced by class below)
    // Page name
    /*
    function _sql_match_clause($word) {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $word = $this->_dbh->qstr("%$word%");
        return "LOWER(pagename) LIKE $word";
    }
    */

    // Fulltext -- case sensisitive :-\
    // If we want case insensitive search, one need to create a Context
    // Index on the CLOB. While it is very efficient, it requires the
    // Intermedia Text option, so let's stick to the 'simple' thing
    /*
    function _fullsearch_sql_match_clause($word) {
        $word = preg_replace('/(?=[%_\\\\])/', "\\", $word);
        $wordq = $this->_dbh->qstr("%$word%");
        return "LOWER(pagename) LIKE $wordq " 
               . "OR DBMS_LOB.INSTR(content, '$word') > 0";
    }
    */
    
    /**
     * Serialize data
     */
    function _serialize($data) {
        if (empty($data))
            return '';
        assert(is_array($data));
        return $this->_dbh->BlobEncode(serialize($data));
    }

    /**
     * Unserialize data
     */
    function _unserialize($data) {
        if (empty($data))
            return array();
        $d = $this->_dbh->BlobDecode($data);
        if(! is_string($d)) {
          print_r($d);
        }
        return unserialize($this->_dbh->BlobDecode($data));
    }

};

class WikiDB_backend_ADODB_oci8_search
extends WikiDB_backend_ADODB_search
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