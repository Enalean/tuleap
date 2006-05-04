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
// | Author: James L. Pine <jlp@valinux.com>                              |
// +----------------------------------------------------------------------+
//
// $Id: oci8.php 1422 2005-04-12 13:33:49Z guerin $
//
// Database independent query interface definition for PHP's Oracle 8
// call-interface extension.
//

//
// be aware...  OCIError() only appears to return anything when given a
// statement, so functions return the generic DB_ERROR instead of more
// useful errors that have to do with feedback from the database.
//
// Based on DB 1.3 from the pear.php.net repository. 
// The only modifications made have been modification of the include paths. 
//
rcs_id('$Id: oci8.php 1422 2005-04-12 13:33:49Z guerin $');
rcs_id('From Pear CVS: Id: oci8.php,v 1.4 2002/07/02 16:39:16 mj Exp');

require_once 'DB/common.php';

class DB_oci8 extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $manip_query = array();
    var $prepare_types = array();
    var $autoCommit = 1;
    var $last_stmt = false;

    // }}}
    // {{{ constructor

    function DB_oci8()
    {
        $this->DB_common();
        $this->phptype = 'oci8';
        $this->dbsyntax = 'oci8';
        $this->features = array(
            'prepare' => false,
            'pconnect' => true,
            'transactions' => true,
            'limit' => 'alter'
        );
        $this->errorcode_map = array(
            900 => DB_ERROR_SYNTAX,
            904 => DB_ERROR_NOSUCHFIELD,
            923 => DB_ERROR_SYNTAX,
            942 => DB_ERROR_NOSUCHTABLE,
            955 => DB_ERROR_ALREADY_EXISTS,
            1476 => DB_ERROR_DIVZERO,
            1722 => DB_ERROR_INVALID_NUMBER,
            2289 => DB_ERROR_NOSUCHTABLE,
            2291 => DB_ERROR_CONSTRAINT,
            2449 => DB_ERROR_CONSTRAINT,
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
     *
     * @return int DB_OK on success, a DB error code on failure
     */
    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('oci8')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }
        $this->dsn = $dsninfo;
        $user = $dsninfo['username'];
        $pw = $dsninfo['password'];
        $hostspec = $dsninfo['hostspec'];

        $connect_function = $persistent ? 'OCIPLogon' : 'OCILogon';

        if ($hostspec) {
            $conn = @$connect_function($user,$pw,$hostspec);
        } elseif ($user || $pw) {
            $conn = @$connect_function($user,$pw);
        } else {
            $conn = false;
        }
        if ($conn == false) {
            $error = OCIError();
            $error = (is_array($error)) ? $error['message'] : null;
            return $this->raiseError(DB_ERROR_CONNECT_FAILED, null, null,
                                     null, $error);
        }
        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @return bool TRUE on success, FALSE if not connected.
     */
    function disconnect()
    {
        $ret = @OCILogOff($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to oracle and return the results as an oci8 resource
     * identifier.
     *
     * @param $query the SQL query
     *
     * @return int returns a valid oci8 result for successful SELECT
     * queries, DB_OK for other successful queries.  A DB error code
     * is returned on failure.
     */
    function simpleQuery($query)
    {
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $result = @OCIParse($this->connection, $query);
        if (!$result) {
            return $this->oci8RaiseError();
        }
        if ($this->autoCommit) {
            $success = @OCIExecute($result,OCI_COMMIT_ON_SUCCESS);
        } else {
            $success = @OCIExecute($result,OCI_DEFAULT);
        }
        if (!$success) {
            return $this->oci8RaiseError($result);
        }
        $this->last_stmt=$result;
        // Determine which queries that should return data, and which
        // should return an error code only.
        return DB::isManip($query) ? DB_OK : $result;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal oracle result pointer to the next available result
     *
     * @param a valid oci8 result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return false;
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch a row and return as array.
     *
     * @param $result oci8 result identifier
     * @param $fetchmode how the resulting array should be indexed
     *
     * @return int an array on success, a DB error code on failure, NULL
     *             if there is no more data
     */
    function &fetchRow($result, $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $moredata = @OCIFetchInto($result, $row, OCI_ASSOC + OCI_RETURN_NULLS + OCI_RETURN_LOBS);
        } else {
            $moredata = @OCIFetchInto($result, $row, OCI_RETURN_NULLS + OCI_RETURN_LOBS);
        }
        if (!$moredata) {
            return NULL;
        }
        return $row;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param $result oci8 result identifier
     * @param $arr (reference) array where data from the row is stored
     * @param $fetchmode how the array data should be indexed
     * @param $rownum the row number to fetch (not yet supported)
     *
     * @return int DB_OK on success, a DB error code on failure
     */
    function fetchInto($result, &$arr, $fetchmode = DB_FETCHMODE_DEFAULT, $rownum=NULL)
    {
        if ($rownum !== NULL) {
            return $this->raiseError(DB_ERROR_NOT_CAPABLE);
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $moredata = @OCIFetchInto($result,$arr,OCI_ASSOC+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
            if ($moredata && $this->options['optimize'] == 'portability') {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $moredata = @OCIFetchInto($result,$arr,OCI_RETURN_NULLS+OCI_RETURN_LOBS);
        }
        if (!$moredata) {
            return NULL;
        }
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result oci8 result identifier or DB statement identifier
     *
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freeResult($result)
    {
        if (is_resource($result)) {
            return @OCIFreeStatement($result);
        }
        if (!isset($this->prepare_tokens[(int)$result])) {
            return false;
        }
        unset($this->prepare_tokens[(int)$result]);
        unset($this->prepare_types[(int)$result]);
        unset($this->manip_query[(int)$result]);
        return true;
    }

    // }}}
    // {{{ numRows()

    function numRows($result)
    {
        // emulate numRows for Oracle.  yuck.
        if ($this->options['optimize'] == 'portability' &&
            $result === $this->last_stmt) {
            $countquery = "SELECT COUNT(*) FROM (".$this->last_query.")";
            $save_query = $this->last_query;
            $save_stmt = $this->last_stmt;
            $count = $this->query($countquery);
            if (DB::isError($count) ||
                DB::isError($row = $count->fetchRow(DB_FETCHMODE_ORDERED)))
            {
                $this->last_query = $save_query;
                $this->last_stmt = $save_stmt;
                return $this->raiseError(DB_ERROR_NOT_CAPABLE);
            }
            return $row[0];
        }
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ numCols()

    /**
     * Get the number of columns in a result set.
     *
     * @param $result oci8 result identifier
     *
     * @return int the number of columns per row in $result
     */
    function numCols($result)
    {
        $cols = @OCINumCols($result);
        if (!$cols) {
            return $this->oci8RaiseError($result);
        }
        return $cols;
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that occured
     * on the current connection.  This does not work, as OCIError does
     * not work unless given a statement.  If OCIError does return
     * something, so will this.
     *
     * @return int native oci8 error code
     */
    function errorNative()
    {
        if (is_resource($this->last_stmt)) {
            $error = @OCIError($this->last_stmt);
        } else {
            $error = @OCIError($this->connection);
        }
        if (is_array($error)) {
            return $error['code'];
        }
        return false;
    }

    // }}}
    // {{{ prepare()

    /**
     * Prepares a query for multiple execution with execute().  With
     * oci8, this is emulated.
     * @param $query query to be prepared
     *
     * @return DB statement resource
     */
    function prepare($query)
    {
        $tokens = split('[\&\?]', $query);
        $token = 0;
        $types = array();
        for ($i = 0; $i < strlen($query); $i++) {
            switch ($query[$i]) {
                case '?':
                    $types[$token++] = DB_PARAM_SCALAR;
                    break;
                case '&':
                    $types[$token++] = DB_PARAM_OPAQUE;
                    break;
            }
        }
        $binds = sizeof($tokens) - 1;
        $newquery = '';
        for ($i = 0; $i < $binds; $i++) {
            $newquery .= $tokens[$i] . ":bind" . $i;
        }
        $newquery .= $tokens[$i];
        $this->last_query = $query;
        $newquery = $this->modifyQuery($newquery);
        $stmt = @OCIParse($this->connection, $newquery);
        $this->prepare_types[$stmt] = $types;
        $this->manip_query[(int)$stmt] = DB::isManip($query);
        return $stmt;
    }

    // }}}
    // {{{ execute()

    /**
     * Executes a DB statement prepared with prepare().
     *
     * @param $stmt a DB statement resource (returned from prepare())
     * @param $data data to be used in execution of the statement
     *
     * @return int returns an oci8 result resource for successful
     * SELECT queries, DB_OK for other successful queries.  A DB error
     * code is returned on failure.
     */
    function execute($stmt, $data = false)
    {
        $types=&$this->prepare_types[$stmt];
        if (($size = sizeof($types)) != sizeof($data)) {
            return $this->raiseError(DB_ERROR_MISMATCH);
        }
        for ($i = 0; $i < $size; $i++) {
            if (is_array($data)) {
                $pdata[$i] = &$data[$i];
            }
            else {
                $pdata[$i] = &$data;
            }
            if ($types[$i] == DB_PARAM_OPAQUE) {
                $fp = fopen($pdata[$i], "r");
                $pdata[$i] = '';
                if ($fp) {
                    while (($buf = fread($fp, 4096)) != false) {
                        $pdata[$i] .= $buf;
                    }
                }
            }
            if (!@OCIBindByName($stmt, ":bind" . $i, $pdata[$i], -1)) {
                return $this->oci8RaiseError($stmt);
            }
        }
        if ($this->autoCommit) {
            $success = @OCIExecute($stmt, OCI_COMMIT_ON_SUCCESS);
        }
        else {
            $success = @OCIExecute($stmt, OCI_DEFAULT);
        }
        if (!$success) {
            return $this->oci8RaiseError($stmt);
        }
        $this->last_stmt = $stmt;
        if ($this->manip_query[(int)$stmt]) {
            return DB_OK;
        } else {
            return new DB_result($this, $stmt);
        }
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Enable/disable automatic commits
     *
     * @param $onoff true/false whether to autocommit
     */
    function autoCommit($onoff = false)
    {
        $this->autoCommit = (bool)$onoff;;
        return DB_OK;
    }

    // }}}
    // {{{ commit()

    /**
     * Commit transactions on the current connection
     *
     * @return DB_ERROR or DB_OK
     */
    function commit()
    {
        $result = @OCICommit($this->connection);
        if (!$result) {
            return $this->oci8RaiseError();
        }
        return DB_OK;
    }

    // }}}
    // {{{ rollback()

    /**
     * Roll back all uncommitted transactions on the current connection.
     *
     * @return DB_ERROR or DB_OK
     */
    function rollback()
    {
        $result = @OCIRollback($this->connection);
        if (!$result) {
            return $this->oci8RaiseError();
        }
        return DB_OK;
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Gets the number of rows affected by the last query.
     * if the last query was a select, returns 0.
     *
     * @return number of rows affected by the last query or DB_ERROR
     */
    function affectedRows()
    {
        if ($this->last_stmt === false) {
            return $this->oci8RaiseError();
        }
        $result = @OCIRowCount($this->last_stmt);
        if ($result === false) {
            return $this->oci8RaiseError($this->last_stmt);
        }
        return $result;
    }

    // }}}
    // {{{ modifyQuery()

    function modifyQuery($query)
    {
        // "SELECT 2+2" must be "SELECT 2+2 FROM dual" in Oracle
        if (preg_match('/^\s*SELECT/i', $query) &&
            !preg_match('/\sFROM\s/i', $query)) {
            $query .= " FROM dual";
        }
        return $query;
    }

    // }}}
    // {{{ modifyLimitQuery()

    /**
    * Emulate the row limit support altering the query
    *
    * @param string $query The query to treat
    * @param int    $from  The row to start to fetch from
    * @param int    $count The offset
    * @return string The modified query
    *
    * @author Tomas V.V.Cox <cox@idecnet.com>
    */
    function modifyLimitQuery($query, $from, $count)
    {
        // Let Oracle return the name of the columns instead of
        // coding a "home" SQL parser
        $q_fields = "SELECT * FROM ($query) WHERE NULL = NULL";
        if (!$result = OCIParse($this->connection, $q_fields)) {
            return $this->oci8RaiseError();
        }
        if (!OCIExecute($result, OCI_DEFAULT)) {
            return $this->oci8RaiseError($result);
        }
        $ncols = OCINumCols($result);
        $cols  = array();
        for ( $i = 1; $i <= $ncols; $i++ ) {
            $cols[] = OCIColumnName($result, $i);
        }
        $fields = implode(', ', $cols);
        // XXX Test that (tip by John Lim)
        //if(preg_match('/^\s*SELECT\s+/is', $query, $match)) {
        //    // Introduce the FIRST_ROWS Oracle query optimizer
        //    $query = substr($query, strlen($match[0]), strlen($query));
        //    $query = "SELECT /* +FIRST_ROWS */ " . $query;
        //}

        // Construct the query
        // more at: http://marc.theaimsgroup.com/?l=php-db&m=99831958101212&w=2
        // Perhaps this could be optimized with the use of Unions
        $from += 1; // in Oracle rownum starts at 1
        $query = "SELECT $fields FROM".
                 "  (SELECT rownum as linenum, $fields FROM".
                 "      ($query)".
                 "  WHERE rownum <= ". ($from + $count) .
                 ") WHERE linenum >= $from";
        return $query;
    }

    // }}}
    // {{{ nextId()

    /**
     * Get the next value in a sequence.  We emulate sequences
     * for MySQL.  Will create the sequence if it does not exist.
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
        $seqname = $this->getSequenceName($seq_name);
        $repeat = 0;
        do {
            $this->expectError(DB_ERROR_NOSUCHTABLE);
            $result = $this->query("SELECT ${seqname}.nextval FROM dual");
            $this->popExpect();
            if ($ondemand && DB::isError($result) &&
                $result->getCode() == DB_ERROR_NOSUCHTABLE) {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }
            } else {
                $repeat = 0;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $this->raiseError($result);
        }
        $arr = $result->fetchRow(DB_FETCHMODE_ORDERED);
        return $arr[0];
    }

    // }}}
    // {{{ createSequence()

    function createSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("CREATE SEQUENCE ${seqname}");
    }

    // }}}
    // {{{ dropSequence()

    function dropSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("DROP SEQUENCE ${seqname}");
    }

    // }}}
    // {{{ oci8RaiseError()

    function oci8RaiseError($errno = null)
    {
        if ($errno === null) {
            $error = @OCIError($this->connection);
            return $this->raiseError($this->errorCode($error['code']),
                                     null, null, null, $error['message']);
        } elseif (is_resource($errno)) {
            $error = @OCIError($errno);
            return $this->raiseError($this->errorCode($error['code']),
                                     null, null, null, $error['message']);
        }
        return $this->raiseError($this->errorCode($errno));
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
                $sql = "SELECT table_name FROM user_tables";
                break;
            default:
                return null;
        }
        return $sql;
    }

    // }}}

    function tableInfo($result, $mode = null)
    {
        $count = 0;
        $res   = array();
        /*
         * depending on $mode, metadata returns the following values:
         *
         * - mode is false (default):
         * $res[]:
         *   [0]["table"]       table name
         *   [0]["name"]        field name
         *   [0]["type"]        field type
         *   [0]["len"]         field length
         *   [0]["nullable"]    field can be null (boolean)
         *   [0]["format"]      field precision if NUMBER
         *   [0]["default"]     field default value
         *
         * - mode is DB_TABLEINFO_ORDER
         * $res[]:
         *   ["num_fields"]     number of fields
         *   [0]["table"]       table name
         *   [0]["name"]        field name
         *   [0]["type"]        field type
         *   [0]["len"]         field length
         *   [0]["nullable"]    field can be null (boolean)
         *   [0]["format"]      field precision if NUMBER
         *   [0]["default"]     field default value
         *   ['order'][field name] index of field named "field name"
         *   The last one is used, if you have a field name, but no index.
         *   Test:  if (isset($result['order']['myfield'])) { ...
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

        // if $result is a string, we collect info for a table only
        if (is_string($result)) {
            $result = strtoupper($result);
            $q_fields = "select column_name, data_type, data_length, data_precision,
                         nullable, data_default from user_tab_columns
                         where table_name='$result' order by column_id";
            if (!$stmt = OCIParse($this->connection, $q_fields)) {
                return $this->oci8RaiseError();
            }
            if (!OCIExecute($stmt, OCI_DEFAULT)) {
                return $this->oci8RaiseError($stmt);
            }
            while (OCIFetch($stmt)) {
                $res[$count]['table']       = $result;
                $res[$count]['name']        = @OCIResult($stmt, 1);
                $res[$count]['type']        = @OCIResult($stmt, 2);
                $res[$count]['len']         = @OCIResult($stmt, 3);
                $res[$count]['format']      = @OCIResult($stmt, 4);
                $res[$count]['nullable']    = (@OCIResult($stmt, 5) == 'Y') ? true : false;
                $res[$count]['default']     = @OCIResult($stmt, 6);
                if ($mode & DB_TABLEINFO_ORDER) {
                    $res['order'][$res[$count]['name']] = $count;
                }
                if ($mode & DB_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$count]['table']][$res[$count]['name']] = $count;
                }
                $count++;
            }
            $res['num_fields'] = $count;
            @OCIFreeStatement($stmt);
        } else { // else we want information about a resultset
            if ($result === $this->last_stmt) {
                $count = @OCINumCols($result);
                for ($i=0; $i<$count; $i++) {
                    $res[$i]['name']  = @OCIColumnName($result, $i+1);
                    $res[$i]['type']  = @OCIColumnType($result, $i+1);
                    $res[$i]['len']   = @OCIColumnSize($result, $i+1);

                    $q_fields = "select table_name, data_precision, nullable, data_default from user_tab_columns where column_name='".$res[$i]['name']."'";
                    if (!$stmt = OCIParse($this->connection, $q_fields)) {
                        return $this->oci8RaiseError();
                    }
                    if (!OCIExecute($stmt, OCI_DEFAULT)) {
                        return $this->oci8RaiseError($stmt);
                    }
                    OCIFetch($stmt);
                    $res[$i]['table']       = OCIResult($stmt, 1);
                    $res[$i]['format']      = OCIResult($stmt, 2);
                    $res[$i]['nullable']    = (OCIResult($stmt, 3) == 'Y') ? true : false;
                    $res[$i]['default']     = OCIResult($stmt, 4);
                    OCIFreeStatement($stmt);

                    if ($mode & DB_TABLEINFO_ORDER) {
                        $res['order'][$res[$i]['name']] = $i;
                    }
                    if ($mode & DB_TABLEINFO_ORDERTABLE) {
                        $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                    }
                }
                $res['num_fields'] = $count;

            } else {
                return $this->raiseError(DB_ERROR_NOT_CAPABLE);
            }
        }
        return $res;
    }
}
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// End:
?>