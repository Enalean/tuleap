<?php // -*-php-*-
rcs_id('$Id: ADODB_sqlite.php,v 1.2 2004/07/05 13:56:23 rurban Exp $');

require_once('lib/WikiDB/backend/ADODB.php');

/**
 * WikiDB layer for ADODB-sqlite, called by lib/WikiDB/ADODB.php.
 * Just to create a not existing database.
 * 
 * @author: Reini Urban
 */
class WikiDB_backend_ADODB_sqlite
extends WikiDB_backend_ADODB
{
    /**
     * Constructor.
     */
    function WikiDB_backend_ADODB_sqlite($dbparams) {
        $parsed = parseDSN($dbparams['dsn']);
        if (! file_exists($parsed['database'])) {
            // creating the empty database
            $db = $parsed['database'];
            $schema = FindFile("schemas/sqlite.sql");
            `sqlite $db < $schema`;
        }
        $this->WikiDB_backend_ADODB($dbparams);
    }
    
    function _get_pageid($pagename, $create_if_missing = false) {
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
            // atomic version 	
            // TODO: we have auto-increment since sqlite-2.3.4
            //   http://www.sqlite.org/faq.html#q1
            $rs = $dbh->Execute(sprintf("INSERT INTO $page_tbl"
                                        . " (id,pagename)"
              			        . " VALUES((SELECT max(id) FROM $page_tbl)+1, %s)",
                                        $dbh->qstr($pagename)));
            $id = $dbh->_insertid();
        } else {
            $id = $row[0];
        }
        return $id;
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
