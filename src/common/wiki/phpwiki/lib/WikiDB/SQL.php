<?php rcs_id('$Id: SQL.php,v 1.13 2004/12/10 22:15:00 rurban Exp $');

require_once('lib/WikiDB.php');
//require_once('lib/WikiDB/backend/PearDB.php');
//require_once('DB.php'); // Always favor use our local pear copy

/**
 *
 */
class WikiDB_SQL extends WikiDB
{
    function WikiDB_SQL ($dbparams) {
        $backend = 'PearDB';
        if (is_array($dbparams['dsn']))
            $backend = $dbparams['dsn']['phptype'];
        elseif (preg_match('/^(\w+):/', $dbparams['dsn'], $m))
            $backend = $m[1];
	if ($backend == 'postgres7') { // ADODB cross-compatiblity hack (for unit testing)
	    $backend = 'pgsql';
	    if (is_string($dbparams['dsn']))
		$dbparams['dsn'] = $backend . ':' . substr($dbparams['dsn'], 10);
	}
        include_once ("lib/WikiDB/backend/PearDB_".$backend.".php");
        $backend_class = "WikiDB_backend_PearDB_".$backend;
        $backend = & new $backend_class($dbparams);
        $this->WikiDB($backend, $dbparams);
    }
    
    function view_dsn ($dsn = false) {
        if (!$dsn)
            $dsninfo = DB::parseDSN($GLOBALS['DBParams']['dsn']);
        else
            $dsninfo = DB::parseDSN($dsn);
        return sprintf("%s://%s:<not displayed>@%s/%s",
                       $dsninfo['phptype'],
                       $dsninfo['username'],
                       $dsninfo['hostspec'],
                       $dsninfo['database']
                       );
    }

    
    /**
     * Determine whether page exists (in non-default form).
     * @see WikiDB::isWikiPage for the slow generic version
     */
    function isWikiPage ($pagename) {
        $pagename = (string) $pagename;
        if ($pagename === '') return false;
        //if (empty($this->_iwpcache)) {  $this->_iwpcache = array();  }
        if (empty($this->_cache->id_cache[$pagename])) {
            $this->_cache->_id_cache[$pagename] = $this->_backend->is_wiki_page($pagename);
        }
        return $this->_cache->_id_cache[$pagename];
    }

    // adds surrounding quotes 
    function quote ($s) { return $this->_backend->_dbh->quoteSmart($s); }
    // no surrounding quotes because we know it's a string
    function qstr ($s) {  return $this->_backend->_dbh->escapeSimple($s); }

    function isOpen () {
        global $request;
        if (!$request->_dbi) return false;
        return is_resource($this->_backend->connection());
    }

    // SQL result: for simple select or create/update queries
    // returns the database specific resource type
    function genericSqlQuery($sql, $args=false) {
        if ($args)
            $result = $this->_backend->_dbh->query($sql, $args);
        else
            $result = $this->_backend->_dbh->query($sql);
        if (DB::isError($result)) {
            $msg = $result->getMessage();
            trigger_error("SQL Error: ".DB::errorMessage($result), E_USER_WARNING);
            return false;
        } else {
            return $result;
        }
    }

    // SQL iter: for simple select or create/update queries
    // returns the generic iterator object (count,next)
    function genericSqlIter($sql, $field_list = NULL) {
        $result = $this->genericSqlQuery($sql);
        return new WikiDB_backend_PearDB_generic_iter($this->_backend, $result);
    }

};


// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:

?>
