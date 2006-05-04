<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Frank M. Kromann <frank@frontbase.com>                       |
// +----------------------------------------------------------------------+
//
// $Id: fbsql.php 1422 2005-04-12 13:33:49Z guerin $
//
// Database independent query interface definition for PHP's FrontBase
// extension.
//
// Based on DB 1.3 from the pear.php.net repository. 
// The only modifications made have been modification of the include paths. 
//
rcs_id('$Id: fbsql.php 1422 2005-04-12 13:33:49Z guerin $');
rcs_id('From Pear CVS: Id: fbsql.php,v 1.3 2002/05/09 12:29:53 ssb Exp');

//
// XXX legend:
//
// XXX ERRORMSG: The error message from the fbsql function should
//               be registered here.
//

require_once 'DB/common.php';

class DB_fbsql extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $prepare_tokens = array();
    var $prepare_types = array();
    var $num_rows = array();
    var $fetchmode = DB_FETCHMODE_ORDERED; /* Default fetch mode */

    // }}}
    // {{{ constructor

    /**
     * DB_fbsql constructor.
     *
     * @access public
     */

    function DB_fbsql()
    {
        $this->DB_common();
        $this->phptype = 'fbsql';
        $this->dbsyntax = 'fbsql';
        $this->features = array(
            'prepare' => false,
            'pconnect' => true,
            'transactions' => true,
            'limit' => 'emulate'
        );
        $this->errorcode_map = array(
            1004 => DB_ERROR_CANNOT_CREATE,
            1005 => DB_ERROR_CANNOT_CREATE,
            1006 => DB_ERROR_CANNOT_CREATE,
            1007 => DB_ERROR_ALREADY_EXISTS,
            1008 => DB_ERROR_CANNOT_DROP,
            1046 => DB_ERROR_NODBSELECTED,
            1050 => DB_ERROR_ALREADY_EXISTS,
            1051 => DB_ERROR_NOSUCHTABLE,
            1054 => DB_ERROR_NOSUCHFIELD,
            1062 => DB_ERROR_ALREADY_EXISTS,
            1064 => DB_ERROR_SYNTAX,
            1100 => DB_ERROR_NOT_LOCKED,
            1136 => DB_ERROR_VALUE_COUNT_ON_ROW,
            1146 => DB_ERROR_NOSUCHTABLE,
        );
    }

    // }}}

    // {{{ connect()

    /**
     * Connect to a database and log in as the specified user.
     *
     * @param $dsn the data source name (see DB::parseDSN for syntax)
     * @param $persistent (optional) whether the connection should
     *        be persistent
     * @access public
     * @return int DB_OK on success, a DB error on failure
     */

    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('fbsql'))
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);

        $this->dsn = $dsninfo;
        $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';
        $user = $dsninfo['username'];
        $pw = $dsninfo['password'];

        $connect_function = $persistent ? 'fbsql_pconnect' : 'fbsql_connect';

        ini_set('track_errors', true);
        if ($dbhost && $user && $pw) {
            $conn = @$connect_function($dbhost, $user, $pw);
        } elseif ($dbhost && $user) {
            $conn = @$connect_function($dbhost, $user);
        } elseif ($dbhost) {
            $conn = @$connect_function($dbhost);
        } else {
            $conn = false;
        }
        ini_restore("track_errors");
        if (empty($conn)) {
            if (empty($php_errormsg)) {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED);
            } else {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED, null, null,
                                         null, $php_errormsg);
            }
        }

        if ($dsninfo['database']) {
            if (!fbsql_select_db($dsninfo['database'], $conn)) {
                return $this->fbsqlRaiseError();
            }
        }

        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @access public
     *
     * @return bool TRUE on success, FALSE if not connected.
     */
    function disconnect()
    {
        $ret = fbsql_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to fbsql and return the results as a fbsql resource
     * identifier.
     *
     * @param the SQL query
     *
     * @access public
     *
     * @return mixed returns a valid fbsql result for successful SELECT
     * queries, DB_OK for other successful queries.  A DB error is
     * returned on failure.
     */
    function simpleQuery($query)
    {
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $result = @fbsql_query("$query;", $this->connection);
        if (!$result) {
            return $this->fbsqlRaiseError();
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        if (DB::isManip($query)) {
            return DB_OK;
        }
        $numrows = $this->numrows($result);
        if (is_object($numrows)) {
            return $numrows;
        }
        $this->num_rows[$result] = $numrows;
        return $result;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal fbsql result pointer to the next available result
     *
     * @param a valid fbsql result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return @fbsql_next_result($result);
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch and return a row of data (it uses fetchInto for that)
     * @param $result fbsql result identifier
     * @param   $fetchmode  format of fetched row array
     * @param   $rownum     the absolute row number to fetch
     *
     * @return  array   a row of data, or false on error
     */
    function fetchRow($result, $fetchmode = DB_FETCHMODE_DEFAULT, $rownum=null)
    {
        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        $res = $this->fetchInto ($result, $arr, $fetchmode, $rownum);
        if ($res !== DB_OK) {
            return $res;
        }
        return $arr;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param $result fbsql result identifier
     * @param $arr (reference) array where data from the row is stored
     * @param $fetchmode how the array data should be indexed
     * @param   $rownum the row number to fetch
     * @access public
     *
     * @return int DB_OK on success, a DB error on failure
     */
    function fetchInto($result, &$arr, $fetchmode, $rownum=null)
    {
        if ($rownum !== null) {
            if (!@fbsql_data_seek($result, $rownum)) {
                return null;
            }
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $arr = @fbsql_fetch_array($result, FBSQL_ASSOC);
        } else {
            $arr = @fbsql_fetch_row($result);
        }
        if (!$arr) {
            $errno = @fbsql_errno($this->connection);
            if (!$errno) {
                return NULL;
            }
            return $this->fbsqlRaiseError($errno);
        }
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result fbsql result identifier or DB statement identifier
     *
     * @access public
     *
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freeResult($result)
    {
        if (is_resource($result)) {
            return fbsql_free_result($result);
        }

        if (!isset($this->prepare_tokens[(int)$result])) {
            return false;
        }

        unset($this->prepare_tokens[(int)$result]);
        unset($this->prepare_types[(int)$result]);

        return true;
    }

    // }}}
    // {{{ autoCommit()

    function autoCommit($onoff=false)
    {
    	if ($onoff) {
            $this->query("SET COMMIT TRUE");
    	} else {
            $this->query("SET COMMIT FALSE");
    	}
    }

    // }}}
    // {{{ commit()

    function commit()
    {
        fbsql_commit();
    }

    // }}}
    // {{{ rollback()

    function rollback()
    {
        fbsql_rollback();
    }

    // }}}
    // {{{ numCols()

    /**
     * Get the number of columns in a result set.
     *
     * @param $result fbsql result identifier
     *
     * @access public
     *
     * @return int the number of columns per row in $result
     */
    function numCols($result)
    {
        $cols = @fbsql_num_fields($result);

        if (!$cols) {
            return $this->fbsqlRaiseError();
        }

        return $cols;
    }

    // }}}
    // {{{ numRows()

    /**
     * Get the number of rows in a result set.
     *
     * @param $result fbsql result identifier
     *
     * @access public
     *
     * @return int the number of rows in $result
     */
    function numRows($result)
    {
        $rows = @fbsql_num_rows($result);
        if ($rows === null) {
            return $this->fbsqlRaiseError();
        }
        return $rows;
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Gets the number of rows affected by the data manipulation
     * query.  For other queries, this function returns 0.
     *
     * @return number of rows affected by the last query
     */

    function affectedRows()
    {
        if (DB::isManip($this->last_query)) {
            $result = @fbsql_affected_rows($this->connection);
        } else {
            $result = 0;
        }
        return $result;
     }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @access public
     *
     * @return int native fbsql error code
     */

    function errorNative()
    {
        return fbsql_errno($this->connection);
    }

    // }}}
    // {{{ nextId()

    /**
     * Get the next value in a sequence.  We emulate sequences
     * for fbsql.  Will create the sequence if it does not exist.
     *
     * @access public
     *
     * @param $seq_name the name of the sequence
     *
     * @param $ondemand whether to create the sequence table on demand
     * (default is true)
     *
     * @return a sequence integer, or a DB error
     */
    function nextId($seq_name, $ondemand = true)
    {
        $sqn = preg_replace('/[^a-z0-9_]/i', '_', $seq_name);
        $repeat = 0;
        do {
            $seqname = sprintf($this->getOption("seqname_format"), $sqn);
            $result = $this->query("INSERT INTO ${seqname} VALUES(NULL)");
            if ($ondemand && DB::isError($result) &&
                $result->getCode() == DB_ERROR_NOSUCHTABLE) {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $result;
                }
            } else {
                $repeat = 0;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $result;
        }
        return fbsql_insert_id($this->connection);
    }

    // }}}
    // {{{ createSequence()

    function createSequence($seq_name)
    {
        $sqn = preg_replace('/[^a-z0-9_]/i', '_', $seq_name);
        $seqname = sprintf($this->getOption("seqname_format"), $sqn);
        return $this->query("CREATE TABLE ${seqname} ".
                            '(id INTEGER UNSIGNED AUTO_INCREMENT NOT NULL,'.
                            ' PRIMARY KEY(id))');
    }

    // }}}
    // {{{ dropSequence()

    function dropSequence($seq_name)
    {
        $sqn = preg_replace('/[^a-z0-9_]/i', '_', $seq_name);
        $seqname = sprintf($this->getOption("seqname_format"), $sqn);
        return $this->query("DROP TABLE ${seqname} RESTRICT");
    }

    // }}}
    // {{{ modifyQuery()

    function modifyQuery($query)
    {
        if ($this->options['optimize'] == 'portability') {
            // "DELETE FROM table" gives 0 affected rows in fbsql.
            // This little hack lets you know how many rows were deleted.
            if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $query)) {
                $query = preg_replace('/^\s*DELETE\s+FROM\s+(\S+)\s*$/',
                                      'DELETE FROM \1 WHERE 1=1', $query);
            }
        }
        return $query;
    }

    // }}}
    // {{{ fbsqlRaiseError()

    function fbsqlRaiseError($errno = null)
    {
        if ($errno === null) {
            $errno = $this->errorCode(fbsql_errno($this->connection));
        }
        return $this->raiseError($errno, null, null, null,
                        fbsql_error($this->connection));
    }

    // }}}
    // {{{ tableInfo()

    function tableInfo($result, $mode = null) {
        $count = 0;
        $id    = 0;
        $res   = array();

        /*
         * depending on $mode, metadata returns the following values:
         *
         * - mode is false (default):
         * $result[]:
         *   [0]["table"]  table name
         *   [0]["name"]   field name
         *   [0]["type"]   field type
         *   [0]["len"]    field length
         *   [0]["flags"]  field flags
         *
         * - mode is DB_TABLEINFO_ORDER
         * $result[]:
         *   ["num_fields"] number of metadata records
         *   [0]["table"]  table name
         *   [0]["name"]   field name
         *   [0]["type"]   field type
         *   [0]["len"]    field length
         *   [0]["flags"]  field flags
         *   ["order"][field name]  index of field named "field name"
         *   The last one is used, if you have a field name, but no index.
         *   Test:  if (isset($result['meta']['myfield'])) { ...
         *
         * - mode is DB_TABLEINFO_ORDERTABLE
         *    the same as above. but additionally
         *   ["ordertable"][table name][field name] index of field
         *      named "field name"
         *
         *      this is, because if you have fields from different
         *      tables with the same field name * they override each
         *      other with DB_TABLEINFO_ORDER
         *
         *      you can combine DB_TABLEINFO_ORDER and
         *      DB_TABLEINFO_ORDERTABLE with DB_TABLEINFO_ORDER |
         *      DB_TABLEINFO_ORDERTABLE * or with DB_TABLEINFO_FULL
         */

        // if $result is a string, then we want information about a
        // table without a resultset
        if (is_string($result)) {
            $id = @fbsql_list_fields($this->dsn['database'],
                                     $result, $this->connection);
            if (empty($id)) {
                return $this->fbsqlRaiseError();
            }
        } else { // else we want information about a resultset
            $id = $result;
            if (empty($id)) {
                return $this->fbsqlRaiseError();
            }
        }

        $count = @fbsql_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (empty($mode)) {
            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = @fbsql_field_table ($id, $i);
                $res[$i]['name']  = @fbsql_field_name  ($id, $i);
                $res[$i]['type']  = @fbsql_field_type  ($id, $i);
                $res[$i]['len']   = @fbsql_field_len   ($id, $i);
                $res[$i]['flags'] = @fbsql_field_flags ($id, $i);
            }
        } else { // full
            $res["num_fields"]= $count;

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = @fbsql_field_table ($id, $i);
                $res[$i]['name']  = @fbsql_field_name  ($id, $i);
                $res[$i]['type']  = @fbsql_field_type  ($id, $i);
                $res[$i]['len']   = @fbsql_field_len   ($id, $i);
                $res[$i]['flags'] = @fbsql_field_flags ($id, $i);
                if ($mode & DB_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & DB_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
            }
        }

        // free the result only if we were called on a table
        if (is_string($result)) {
            @fbsql_free_result($id);
        }
        return $res;
    }

    // }}}
    // {{{ getSpecialQuery()

    /**
    * Returns the query needed to get some backend info
    * @param string $type What kind of info you want to retrieve
    * @return string The SQL query string
    */
    function getSpecialQuery($type)
    {
        switch ($type) {
            case 'tables':
                $sql = 'select "table_name" from information_schema.tables';
                break;
            default:
                return null;
        }
        return $sql;
    }

    // }}}
}

// TODO/wishlist:
// longReadlen
// binmode

?>