<?php // -*-php-*-
rcs_id('$Id: ADODB_postgres7.php,v 1.3 2005/10/31 16:49:02 rurban Exp $');

require_once('lib/WikiDB/backend/ADODB.php');

if (!defined("USE_BYTEA"))     // see schemas/psql-initialize.sql
    define("USE_BYTEA", true); // only BYTEA is binary safe
    //define("USE_BYTEA", false);

/**
 * WikiDB layer for ADODB-postgres (7 or 8), called by lib/WikiDB/ADODB.php.
 * Pending changes from CVS HEAD:
 *  - use Foreign Keys and ON DELETE CASCADE.
 *  - bytea blob type
 *  - tsearch2
 * 
 * @author: Reini Urban
 */
class WikiDB_backend_ADODB_postgres7
extends WikiDB_backend_ADODB
{
    /**
     * Constructor.
     */
    function WikiDB_backend_ADODB_postgres7($dbparams) {
        $this->WikiDB_backend_ADODB($dbparams);

        $this->_serverinfo = $this->_dbh->ServerInfo();
        if (!empty($this->_serverinfo['version'])) {
            $arr = explode('.',$this->_serverinfo['version']);
            $this->_serverinfo['version'] = (string)(($arr[0] * 100) + $arr[1]);
	    if (!empty($arr[2]))
		$this->_serverinfo['version'] .= ("." . (integer)$arr[2]);
        }
    }

    /**
     * Pack tables.
     */
    function optimize() {
        foreach ($this->_table_names as $table) {
            $this->_dbh->Execute("VACUUM ANALYZE $table");
        }
        return 1;
    }

    // just for blobs. the rest is escaped with qstr()
    function _quote($s) {
	if (USE_BYTEA)
	    return $this->_dbh->BlobEncode($s);
        return base64_encode($s);
    }

    // just for blobs, which might be base64_encoded
    function _unquote($s) {
	if (USE_BYTEA) {
	    //if function_exists('pg_unescape_bytea')
	    //return pg_unescape_bytea($s);
	    // TODO: already unescaped by ADORecordSet_postgres64::_decode?
	    return $s;
	}
        return base64_decode($s);
    }

    function get_cached_html($pagename) {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
	$data = $dbh->GetOne(sprintf("SELECT cached_html FROM $page_tbl WHERE pagename=%s",
                                     $dbh->qstr($pagename)));
        if ($data) return $this->_unquote($data);
        else return '';
    }

    function set_cached_html($pagename, $data) {
        $dbh = &$this->_dbh;
        $page_tbl = $this->_table_names['page_tbl'];
	if (USE_BYTEA) {
	    $dbh->UpdateBlob($page_tbl,'cached_html',$data,"pagename=".$dbh->qstr($pagename));
	    /*
	    $dbh->Execute(sprintf("UPDATE $page_tbl"
				  . " SET cached_html='%s'"
				  . " WHERE pagename=%s",
				  $this->_quote($data), 
				  $dbh->qstr($pagename)));
	    */
	} else {
	    $dbh->Execute("UPDATE $page_tbl"
			  . " SET cached_html=?"
			  . " WHERE pagename=?",
			  array($this->_quote($data), $pagename));
        }
    }

    /**
     * Lock all tables we might use.
     * postgresql has proper transactions so we dont need table locks.
     */
    function _lock_tables($tables, $write_lock = true) {
        ;
    }

    /**
     * Unlock all tables.
     * postgresql has proper transactions so we dont need table locks.
     */
    function _unlock_tables($tables, $write_lock=false) {
        ;
    }

    /**
     * Serialize data
     */
    function _serialize($data) {
        if (empty($data))
            return '';
        assert(is_array($data));
        return $this->_quote(serialize($data));
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
        return unserialize($this->_unquote($data));
    }

};

class WikiDB_backend_ADODB_postgres7_search
extends WikiDB_backend_ADODB_search
{
    function _pagename_match_clause($node) {
        $word = $node->sql();
        if ($node->op == 'REGEX') { // posix regex extensions
            return ($this->_case_exact 
                    ? "pagename ~* '$word'"
                    : "pagename ~ '$word'");
        } else {
            return ($this->_case_exact 
                    ? "pagename LIKE '$word'" 
                    : "pagename ILIKE '$word'");
        }
    }

    // TODO: use tsearch2
    //function _fulltext_match_clause($node)
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
