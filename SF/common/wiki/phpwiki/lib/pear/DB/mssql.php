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
// | Author: Sterling Hughes <sterling@php.net>                           |
// +----------------------------------------------------------------------+
//
// $Id: mssql.php 1422 2005-04-12 13:33:49Z guerin $
//
// Database independent query interface definition for PHP's Microsoft SQL Server
// extension.
//
// Based on DB 1.3 from the pear.php.net repository. 
// The only modifications made have been modification of the include paths. 
//
rcs_id('$Id: mssql.php 1422 2005-04-12 13:33:49Z guerin $');
rcs_id('From Pear CVS: Id: mssql.php,v 1.4 2002/05/23 09:09:39 cox Exp');

require_once 'DB/common.php';

class DB_mssql extends DB_common
{
    var $connection;
    var $phptype, $dbsyntax;
    var $prepare_tokens = array();
    var $prepare_types = array();
    var $transaction_opcount = 0;
    var $autocommit = true;
    var $_db = null;

    function DB_mssql()
    {
        $this->DB_common();
        $this->phptype = 'mssql';
        $this->dbsyntax = 'mssql';
        $this->features = array(
            'prepare' => false,
            'pconnect' => true,
            'transactions' => true,
            'limit' => 'emulate'
        );
        // XXX Add here error codes ie: 'S100E' => DB_ERROR_SYNTAX
        $this->errorcode_map = array(

        );
    }

    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('mssql') && !DB::assertExtension('sybase')
            && !DB::assertExtension('sybase_ct'))
        {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }
        $this->dsn = $dsninfo;
        $user = $dsninfo['username'];
        $pw = $dsninfo['password'];
        $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';

        $connect_function = $persistent ? 'mssql_pconnect' : 'mssql_connect';

        if ($dbhost && $user && $pw) {
            $conn = @$connect_function($dbhost, $user, $pw);
        } elseif ($dbhost && $user) {
            $conn = @$connect_function($dbhost, $user);
        } else {
            $conn = @$connect_function($dbhost);
        }
        if (!$conn) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED, null, null,
                                         null, mssql_get_last_message());
        }
        if ($dsninfo['database']) {
            if (!@mssql_select_db($dsninfo['database'], $conn)) {
                return $this->raiseError(DB_ERROR_NODBSELECTED, null, null,
                                         null, mssql_get_last_message());
            }
            $this->_db = $dsninfo['database'];
        }
        $this->connection = $conn;
        return DB_OK;
    }

    function disconnect()
    {
        $ret = @mssql_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    function simpleQuery($query)
    {
        $ismanip = DB::isManip($query);
        $this->last_query = $query;
        if (!@mssql_select_db($this->_db, $this->connection)) {
            return $this->mssqlRaiseError(DB_ERROR_NODBSELECTED);
        }
        $query = $this->modifyQuery($query);
        if (!$this->autocommit && $ismanip) {
            if ($this->transaction_opcount == 0) {
                $result = @mssql_query('BEGIN TRAN', $this->connection);
                if (!$result) {
                    return $this->mssqlRaiseError();
                }
            }
            $this->transaction_opcount++;
        }
        $result = @mssql_query($query, $this->connection);
        if (!$result) {
            return $this->mssqlRaiseError();
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        return $ismanip ? DB_OK : $result;
    }

    // {{{ nextResult()

    /**
     * Move the internal mssql result pointer to the next available result
     *
     * @param a valid fbsql result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return mssql_next_result($result);
    }

    // }}}

    function &fetchRow($result, $fetchmode = DB_FETCHMODE_DEFAULT, $rownum=null)
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

    function fetchInto($result, &$ar, $fetchmode, $rownum=null)
    {
        if ($rownum !== null) {
            if (!@mssql_data_seek($result, $rownum)) {
                return null;
            }
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $ar = @mssql_fetch_array($result);
        } else {
            $ar = @mssql_fetch_row($result);
        }
        if (!$ar) {
            /* This throws informative error messages,
               don't use it for now
            if ($msg = mssql_get_last_message()) {
                return $this->raiseError($msg);
            }
            */
            return null;
        }
        return DB_OK;
    }

    function freeResult($result)
    {
        if (is_resource($result)) {
            return @mssql_free_result($result);
        }
        if (!isset($this->prepare_tokens[$result])) {
            return false;
        }
        unset($this->prepare_tokens[$result]);
        unset($this->prepare_types[$result]);
        return true;
    }

    function numCols($result)
    {
        $cols = @mssql_num_fields($result);
        if (!$cols) {
            return $this->mssqlRaiseError();
        }
        return $cols;
    }

    function numRows($result)
    {
        $rows = @mssql_num_rows($result);
        if ($rows === false) {
            return $this->mssqlRaiseError();
        }
        return $rows;
    }

    /**
     * Enable/disable automatic commits
     */
    function autoCommit($onoff = false)
    {
        // XXX if $this->transaction_opcount > 0, we should probably
        // issue a warning here.
        $this->autocommit = $onoff ? true : false;
        return DB_OK;
    }

    // }}}
    // {{{ commit()

    /**
     * Commit the current transaction.
     */
    function commit()
    {
        if ($this->transaction_opcount > 0) {
            if (!@mssql_select_db($this->_db, $this->connection)) {
                return $this->mssqlRaiseError(DB_ERROR_NODBSELECTED);
            }
            $result = @mssql_query('COMMIT TRAN', $this->connection);
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->mssqlRaiseError();
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ rollback()

    /**
     * Roll back (undo) the current transaction.
     */
    function rollback()
    {
        if ($this->transaction_opcount > 0) {
            if (!@mssql_select_db($this->_db, $this->connection)) {
                return $this->mssqlRaiseError(DB_ERROR_NODBSELECTED);
            }
            $result = @mssql_query('ROLLBACK TRAN', $this->connection);
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->mssqlRaiseError();
            }
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
        if (DB::isManip($this->last_query)) {
            $res = @mssql_query('select @@rowcount', $this->connection);
            if (!$res) {
                return $this->mssqlRaiseError();
            }
            $ar = @mssql_fetch_row($res);
            if (!$ar) {
                $result = 0;
            } else {
                @mssql_free_result($res);
                $result = $ar[0];
            }
        } else {
            $result = 0;
        }
        return $result;
    }
    // {{{ nextId()

    /**
     * Get the next value in a sequence.  We emulate sequences
     * for MSSQL.  Will create the sequence if it does not exist.
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
        if (!@mssql_select_db($this->_db, $this->connection)) {
            return $this->mssqlRaiseError(DB_ERROR_NODBSELECTED);
        }
        $repeat = 0;
        do {
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result = $this->query("INSERT INTO $seqname (vapor) VALUES (0)");
            $this->popErrorHandling();
            if ($ondemand && DB::isError($result) &&
                ($result->getCode() == DB_ERROR || $result->getCode() == DB_ERROR_NOSUCHTABLE))
            {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $result;
                }
            } else {
                $result = $this->query("SELECT @@IDENTITY FROM $seqname");
                $repeat = 0;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $this->raiseError($result);
        }
        $result = $result->fetchRow(DB_FETCHMODE_ORDERED);
        return $result[0];
    }

    // }}}
    // {{{ createSequence()

    function createSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("CREATE TABLE $seqname ".
                            '([id] [int] IDENTITY (1, 1) NOT NULL ,' .
                            '[vapor] [int] NULL)');
    }
    // }}}
    // {{{ dropSequence()

    function dropSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("DROP TABLE $seqname");
    }
    // }}}

    function errorCode()
    {
        $this->pushErrorHandling(PEAR_ERROR_RETURN);
        $error_code = $this->getOne('select @@ERROR as ErrorCode');
        $this->popErrorHandling();
        // XXX Debug
        if (!isset($this->errorcode_map[$error_code])) {
            return DB_ERROR;
        }
        return $error_code;
    }

    function mssqlRaiseError($code = null)
    {
        if ($code !== null) {
            $code = $this->errorCode();
            if (DB::isError($code)) {
                return $this->raiseError($code);
            }
        }
        return $this->raiseError($code, null, null, null, mssql_get_last_message());
    }

  /**

     * Returns information about a table or a result set
     *
     * NOTE: doesn't support table name and flags if called from a db_result
     *
     * @param  mixed $resource SQL Server result identifier or table name
     * @param  int $mode A valid tableInfo mode (DB_TABLEINFO_ORDERTABLE or
     *                   DB_TABLEINFO_ORDER)
     *
     * @return array An array with all the information
     */

    function tableInfo($result, $mode = null)
    {

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
            if (!@mssql_select_db($this->_db, $this->connection)) {
                return $this->mssqlRaiseError(DB_ERROR_NODBSELECTED);
            }
            $id = mssql_query("SELECT * FROM $result", $this->connection);
            if (empty($id)) {
                return $this->mssqlRaiseError();
            }
        } else { // else we want information about a resultset
            $id = $result;
            if (empty($id)) {
                return $this->mssqlRaiseError();
            }
        }

        $count = @mssql_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (empty($mode)) {

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['name']  = @mssql_field_name($id, $i);
                $res[$i]['type']  = @mssql_field_type($id, $i);
                $res[$i]['len']   = @mssql_field_length($id, $i);
                $res[$i]['flags'] = '';
            }

        } else { // full
            $res['num_fields']= $count;

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['name']  = @mssql_field_name($id, $i);
                $res[$i]['type']  = @mssql_field_type($id, $i);
                $res[$i]['len']   = @mssql_field_length($id, $i);
                $res[$i]['flags'] = '';
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
            @mssql_free_result($id);
        }
        return $res;
    }

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
                $sql = "select name from sysobjects where type = 'U' order by name";
                break;
            case 'views':
                $sql = "select name from sysobjects where type = 'V'";
                break;
            default:
                return null;
        }
        return $sql;
    }

    // }}}

}
?>