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
// | Author: Stig Bakken <ssb@fast.no>                                    |
// +----------------------------------------------------------------------+
//
// $Id: odbc.php 1422 2005-04-12 13:33:49Z guerin $
//
// Database independent query interface definition for PHP's ODBC
// extension.
//

//
// XXX legend:
//  More info on ODBC errors could be found here:
//  http://msdn.microsoft.com/library/default.asp?url=/library/en-us/trblsql/tr_err_odbc_5stz.asp
//
// XXX ERRORMSG: The error message from the odbc function should
//                 be registered here.
//
// Based on DB 1.3 from the pear.php.net repository. 
// The only modifications made have been modification of the include paths. 
//
rcs_id('$Id: odbc.php 1422 2005-04-12 13:33:49Z guerin $');
rcs_id('From Pear CVS: Id: odbc.php,v 1.3 2002/05/09 12:29:53 ssb Exp');

require_once 'DB/common.php';

class DB_odbc extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $row = array();

    // }}}
    // {{{ constructor

    function DB_odbc()
    {
        $this->DB_common();
        $this->phptype = 'odbc';
        $this->dbsyntax = 'sql92';
        $this->features = array(
            'prepare' => true,
            'pconnect' => true,
            'transactions' => false,
            'limit' => 'emulate'
        );
        $this->errorcode_map = array(
            '01004' => DB_ERROR_TRUNCATED,
            '07001' => DB_ERROR_MISMATCH,
            '21S01' => DB_ERROR_MISMATCH,
            '21S02' => DB_ERROR_MISMATCH,
            '22003' => DB_ERROR_INVALID_NUMBER,
            '22008' => DB_ERROR_INVALID_DATE,
            '22012' => DB_ERROR_DIVZERO,
            '23000' => DB_ERROR_CONSTRAINT,
            '24000' => DB_ERROR_INVALID,
            '34000' => DB_ERROR_INVALID,
            '37000' => DB_ERROR_SYNTAX,
            '42000' => DB_ERROR_SYNTAX,
            'IM001' => DB_ERROR_UNSUPPORTED,
            'S0000' => DB_ERROR_NOSUCHTABLE,
            'S0001' => DB_ERROR_NOT_FOUND,
            'S0002' => DB_ERROR_NOT_FOUND,
            'S0011' => DB_ERROR_ALREADY_EXISTS,
            'S0012' => DB_ERROR_NOT_FOUND,
            'S0021' => DB_ERROR_ALREADY_EXISTS,
            'S0022' => DB_ERROR_NOT_FOUND,
            'S1009' => DB_ERROR_INVALID,
            'S1090' => DB_ERROR_INVALID,
            'S1C00' => DB_ERROR_NOT_CAPABLE
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
        if (!DB::assertExtension('odbc'))
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);

        $this->dsn = $dsninfo;
        if (!empty($dsninfo['dbsyntax'])) {
            $this->dbsyntax = $dsninfo['dbsyntax'];
        }
        switch ($this->dbsyntax) {
            case 'solid':
                $this->features = array(
                    'prepare' => true,
                    'pconnect' => true,
                    'transactions' => true
                );
                $default_dsn = 'localhost';
                break;
            case 'navision':
                // the Navision driver doesn't support fetch row by number
                $this->features['limit'] = false;
                break;
            default:
                break;
        }
        $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';
        $user = $dsninfo['username'];
        $pw = $dsninfo['password'];
        if ($this->provides('pconnect')) {
            $connect_function = $persistent ? 'odbc_pconnect' : 'odbc_connect';
        } else {
            $connect_function = 'odbc_connect';
        }
        $conn = @$connect_function($dbhost, $user, $pw);
        if (!is_resource($conn)) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED, null, null,
                                         null, $this->errorNative());
        }
        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    function disconnect()
    {
        $err = @odbc_close($this->connection);
        $this->connection = null;
        return $err;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to ODBC and return the results as a ODBC resource
     * identifier.
     *
     * @param $query the SQL query
     *
     * @return int returns a valid ODBC result for successful SELECT
     * queries, DB_OK for other successful queries.  A DB error code
     * is returned on failure.
     */
    function simpleQuery($query)
    {
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $result = @odbc_exec($this->connection, $query);
        if (!$result) {
            return $this->odbcRaiseError(); // XXX ERRORMSG
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        if (DB::isManip($query)) {
            $this->manip_result = $result; // For affectedRows()
            return DB_OK;
        }
        $this->row[$result] = 0;
        $this->manip_result = 0;
        return $result;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal odbc result pointer to the next available result
     *
     * @param a valid fbsql result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return odbc_next_result($result);
    }

    // }}}
    // {{{ fetchRow()

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

    function fetchInto($result, &$row, $fetchmode, $rownum=null)
    {
        $row = array();
        if ($rownum !== null) {
            $rownum++; // ODBC first row is 1
            if (!function_exists('version_compare') || version_compare(phpversion(), "4.0.5", "lt")) {
                $cols = odbc_fetch_into($result, $rownum, &$row);
            } elseif (version_compare(phpversion(), '4.2.0', 'ge')) {
                $cols = odbc_fetch_into($result, $row, $rownum);
            } else {
                $cols = odbc_fetch_into($result, $rownum, $row);
            }
        } else {
            if (!function_exists('version_compare') || version_compare(phpversion(), "4.0.5", "lt")) {
                $cols = odbc_fetch_into($result, &$row);
            } else {
                $cols = odbc_fetch_into($result, $row);
            }
        }

        if (!$cols) {
            /* XXX FIXME: doesn't work with unixODBC and easysoft
                          (get corrupted $errno values)
            if ($errno = odbc_error($this->connection)) {
                return $this->RaiseError($errno);
            }*/
            return null;
        }
        if ($fetchmode !== DB_FETCHMODE_ORDERED) {
            for ($i = 0; $i < count($row); $i++) {
                $colName = odbc_field_name($result, $i+1);
                $a[$colName] = $row[$i];
            }
            $row = $a;
        }
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    function freeResult($result)
    {
        if (is_resource($result)) {
            // Always return true
            return odbc_free_result($result);
        }
        if (!isset($this->prepare_tokens[(int)$result])) {
            return false;
        }
        unset($this->prepare_tokens[(int)$result]);
        unset($this->prepare_types[(int)$result]);
        return true;
    }

    // }}}
    // {{{ numCols()

    function numCols($result)
    {
        $cols = @odbc_num_fields($result);
        if (!$cols) {
            return $this->odbcRaiseError();
        }
        return $cols;
    }

    // }}}
    // {{{ affectedRows()

    /**
    * Returns the number of rows affected by a manipulative query
    * (INSERT, DELETE, UPDATE)
    * @return mixed int affected rows, 0 when non manip queries or
    *               DB error on error
    */
    function affectedRows()
    {
        if (empty($this->manip_result)) {  // In case of SELECT stms
            return 0;
        }
        $nrows = odbc_num_rows($this->manip_result);
        if ($nrows == -1) {
            return $this->odbcRaiseError();
        }
        return $nrows;
    }

    // }}}
    // {{{ numRows()

    /**
     * ODBC may or may not support counting rows in the result set of
     * SELECTs.
     *
     * @param $result the odbc result resource
     * @return the number of rows, or 0
     */
    function numRows($result)
    {
        $nrows = odbc_num_rows($result);
        if ($nrows == -1) {
            return $this->odbcRaiseError(DB_ERROR_UNSUPPORTED);
        }
        return $nrows;
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @access public
     *
     * @return int ODBC error code
     */

    function errorNative()
    {
        if (!isset($this->connection) || !is_resource($this->connection)) {
            return odbc_error() . ' ' . odbc_errormsg();
        }
        return odbc_error($this->connection) . ' ' . odbc_errormsg($this->connection);
    }

    // }}}
    // {{{ nextId()

    /**
     * Get the next value in a sequence.  We emulate sequences
     * for odbc. Will create the sequence if it does not exist.
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
            $result = $this->query("update ${seqname} set id = id + 1");
            if ($ondemand && DB::isError($result) &&
                $result->getCode() == DB_ERROR_NOT_FOUND) {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $result;
                }
                $result = $this->query("insert into ${seqname} (id) values(0)");
            } else {
                $repeat = 0;
            }
        } while ($repeat);

        if (DB::isError($result)) {
            return $result;
        }

        $result = $this->query("select id from ${seqname}");
        if (DB::isError($result)) {
            return $result;
        }

        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if (DB::isError($row || !$row)) {
            return $row;
        }

        return $row['id'];
    }

    // }}}
    // {{{ createSequence()

    function createSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("CREATE TABLE ${seqname} ".
                            '(id bigint NOT NULL,'.
                            ' PRIMARY KEY(id))');
    }

    // }}}
    // {{{ dropSequence()

    function dropSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("DROP TABLE ${seqname}");
    }

    // }}}
    // {{{ autoCommit()

    function autoCommit($onoff = false)
    {
        if (!@odbc_autocommit($this->connection, $onoff)) {
            return $this->odbcRaiseError();
        }
        return DB_OK;
    }

    // }}}
    // {{{ commit()

    function commit()
    {
        if (!@odbc_commit($this->connection)) {
            return $this->odbcRaiseError();
        }
        return DB_OK;
    }

    // }}}
    // {{{ rollback()

    function rollback()
    {
        if (!@odbc_rollback($this->connection)) {
            return $this->odbcRaiseError();
        }
        return DB_OK;
    }

    // }}}
    // {{{ odbcRaiseError()

    function odbcRaiseError($errno = null)
    {
        if ($errno === null) {
            $errno = $this->errorCode(odbc_error($this->connection));
        }
        return $this->raiseError($errno, null, null, null,
                        $this->errorNative());
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
            default:
                return null;
        }
        return $sql;
    }

    // }}}

}

// Local variables:
// tab-width: 4
// c-basic-offset: 4
// End:
?>
