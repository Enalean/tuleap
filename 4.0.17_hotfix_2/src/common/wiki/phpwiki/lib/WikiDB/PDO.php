<?php // -*-php-*-
rcs_id('$Id: PDO.php,v 1.2 2005/08/06 13:20:05 rurban Exp $');

require_once('lib/WikiDB.php');

/**
 * WikiDB layer for PDO, the new php5 abstraction layer, with support for 
 * prepared statements and transactions.
 *
 * "The PHP Data Objects (PDO) extension defines a lightweight,
 * consistent interface for accessing databases in PHP. Each database
 * driver that implements the PDO interface can expose
 * database-specific features as regular extension functions. Note
 * that you cannot perform any database functions using the PDO
 * extension by itself; you must use a database-specific PDO driver to
 * access a database server."
 *
 * @author: Reini Urban
 */
class WikiDB_PDO extends WikiDB
{
    function WikiDB_PDO ($dbparams) {
        if (is_array($dbparams['dsn']))
            $backend = $dbparams['dsn']['phptype'];
        elseif (preg_match('/^(\w+):/', $dbparams['dsn'], $m))
            $backend = $m[1];
        // Do we have a override? Currently none: mysql, sqlite, oci, mssql
        if (FindFile("lib/WikiDB/backend/PDO_$backend.php",true)) {
            $backend = 'PDO_' . $backend;
        } else {
            $backend = 'PDO';
        }
        include_once("lib/WikiDB/backend/$backend.php");
        $backend_class = "WikiDB_backend_$backend";
        $backend = new $backend_class($dbparams);
        $this->WikiDB($backend, $dbparams);
    }
    
    /**
     * Determine whether page exists (in non-default form).
     * @see WikiDB::isWikiPage
     */
    function isWikiPage ($pagename) {
        $pagename = (string) $pagename;
        if ($pagename === '') return false;
        if (!array_key_exists($pagename, $this->_cache->_id_cache)) {
            $this->_cache->_id_cache[$pagename] = $this->_backend->is_wiki_page($pagename);
        }
        return $this->_cache->_id_cache[$pagename];
    }

    // With PDO we should really use native quoting using prepared statements with ?
    // Supported since PDO-0.3 (?)
    // Add surrounding quotes '' if string
    function quote ($in) {
        if (is_int($in) || is_double($in)) {
            return $in;
        } elseif (is_bool($in)) {
            return $in ? 1 : 0;
        } elseif (is_null($in)) {
            return 'NULL';
        } else {
            return $this->qstr($in);
        }
    }
    // Don't add surrounding quotes '', same as in PearDB
    // PDO-0.2.1 added now ::quote()
    function qstr ($in) {
        $in = str_replace(array('\\',"\0"),array('\\\\',"\\\0"), $in);
        return str_replace("'", "\'", $in);
    }

    function isOpen () {
        global $request;
        if (!$request->_dbi) return false;
        return is_object($this->_backend->_dbh);
    }

    // SQL result: for simple select or create/update queries
    // returns the database specific resource type
    function genericSqlQuery($sql, $args=false) {
        try {
            $sth = $this->_backend->_dbh->prepare($sql);
            if ($args) {
                foreach ($args as $key => $val ) {
                    $sth->bindParam($key, $val);
                }
            }
            if ($sth->execute())
                $result = $sth->fetch(PDO_FETCH_BOTH);
            else 
                return false;
        }
        catch (PDOException $e) {
            trigger_error("SQL Error: ".$e->getMessage(), E_USER_WARNING);
            return false;
        }
        return $result;
    }

    // SQL iter: for simple select or create/update queries
    // returns the generic iterator object (count, next)
    function genericSqlIter($sql, $field_list = NULL) {
        $result = $this->genericSqlQuery($sql);
        return new WikiDB_backend_PDO_generic_iter($this->_backend, $result, $field_list);
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