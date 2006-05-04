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
// $Id: ibase.php 1422 2005-04-12 13:33:49Z guerin $
//
// Database independent query interface definition for PHP's Interbase
// extension.
//
// Based on DB 1.3 from the pear.php.net repository. 
// The only modifications made have been modification of the include paths. 
//
rcs_id('$Id: ibase.php 1422 2005-04-12 13:33:49Z guerin $');
rcs_id('From Pear CVS: Id: ibase.php,v 1.3 2002/05/09 12:29:53 ssb Exp');

require_once 'DB/common.php';

class DB_ibase extends DB_common
{
    var $connection;
    var $phptype, $dbsyntax;
    var $autocommit = 1;
    var $manip_query = array();

    function DB_ibase()
    {
        $this->DB_common();
        $this->phptype = 'ibase';
        $this->dbsyntax = 'ibase';
        $this->features = array(
            'prepare' => true,
            'pconnect' => true,
            'transactions' => true,
            'limit' => false
        );
        // just a few of the tons of Interbase error codes listed in the
        // Language Reference section of the Interbase manual
        $this->errorcode_map = array(
            -104 => DB_ERROR_SYNTAX,
            -150 => DB_ERROR_ACCESS_VIOLATION,
            -151 => DB_ERROR_ACCESS_VIOLATION,
            -155 => DB_ERROR_NOSUCHTABLE,
            -157 => DB_ERROR_NOSUCHFIELD,
            -158 => DB_ERROR_VALUE_COUNT_ON_ROW,
            -170 => DB_ERROR_MISMATCH,
            -171 => DB_ERROR_MISMATCH,
            -172 => DB_ERROR_INVALID,
            -204 => DB_ERROR_INVALID,
            -205 => DB_ERROR_NOSUCHFIELD,
            -206 => DB_ERROR_NOSUCHFIELD,
            -208 => DB_ERROR_INVALID,
            -219 => DB_ERROR_NOSUCHTABLE,
            -297 => DB_ERROR_CONSTRAINT,
            -530 => DB_ERROR_CONSTRAINT,
            -803 => DB_ERROR_CONSTRAINT,
            -551 => DB_ERROR_ACCESS_VIOLATION,
            -552 => DB_ERROR_ACCESS_VIOLATION,
            -922 => DB_ERROR_NOSUCHDB,
            -923 => DB_ERROR_CONNECT_FAILED,
            -924 => DB_ERROR_CONNECT_FAILED
        );
    }

    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('interbase')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }
        $this->dsn = $dsninfo;
        $user = $dsninfo['username'];
        $pw   = $dsninfo['password'];
        $dbhost = $dsninfo['hostspec'] ?
                  ($dsninfo['hostspec'] . ':/' . $dsninfo['database']) :
                  $dsninfo['database'];

        $connect_function = $persistent ? 'ibase_pconnect' : 'ibase_connect';

        $params = array();
        $params[] = $dbhost;
        $params[] = !empty($user) ? $user : null;
        $params[] = !empty($pw) ? $pw : null;
        $params[] = isset($dsninfo['charset']) ? $dsninfo['charset'] : null;
        $params[] = isset($dsninfo['buffers']) ? $dsninfo['buffers'] : null;
        $params[] = isset($dsninfo['dialect']) ? $dsninfo['dialect'] : null;
        $params[] = isset($dsninfo['role'])    ? $dsninfo['role'] : null;

        /*
        if ($dbhost && $user && $pw) {
            $conn = $connect_function($dbhost, $user, $pw);
        } elseif ($dbhost && $user) {
            $conn = $connect_function($dbhost, $user);
        } elseif ($dbhost) {
            $conn = $connect_function($dbhost);
        } else {
            return $this->raiseError("no host, user or password");
        }
        */
        $conn = @call_user_func_array($connect_function, $params);
        if (!$conn) {
            return $this->ibaseRaiseError(DB_ERROR_CONNECT_FAILED);
        }
        $this->connection = $conn;
        return DB_OK;
    }

    function disconnect()
    {
        $ret = @ibase_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    function simpleQuery($query)
    {
        $ismanip = DB::isManip($query);
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $result = @ibase_query($this->connection, $query);
        if (!$result) {
            return $this->ibaseRaiseError();
        }
        if ($this->autocommit && $ismanip) {
            ibase_commit($this->connection);
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        return DB::isManip($query) ? DB_OK : $result;
    }

    // {{{ modifyLimitQuery()

    /**
    * This method is used by backends to alter limited queries
    * Uses the new FIRST n SKIP n Firebird 1.0 syntax, so it is
    * only compatible with Firebird 1.x
    *
    * @param string  $query query to modify
    * @param integer $from  the row to start to fetching
    * @param integer $count the numbers of rows to fetch
    *
    * @return the new (modified) query
    * @author Ludovico Magnocavallo <ludo@sumatrasolutions.com>
    * @access private
    */

    function modifyLimitQuery($query, $from, $count)
    {
        if ($this->dsn['dbsyntax'] == 'firebird') {
            $from++; // SKIP starts from 1, ie SKIP 1 starts from the first record
            $query = preg_replace('/^\s*select\s(.*)$/is',
                                  "SELECT FIRST $count SKIP $from $1", $query);
        }
        return $query;
    }

    // }}}


    // {{{ nextResult()

    /**
     * Move the internal ibase result pointer to the next available result
     *
     * @param a valid fbsql result resource
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

    function fetchInto($result, &$ar, $fetchmode, $rownum = null)
    {
        if ($rownum !== NULL) {
            return $this->ibaseRaiseError(DB_ERROR_NOT_CAPABLE);
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $ar = get_object_vars(ibase_fetch_object($result));
            if ($ar && $this->options['optimize'] == 'portability') {
                $ar = array_change_key_case($ar, CASE_LOWER);
            }
        } else {
            $ar = ibase_fetch_row($result);
        }
        if (!$ar) {
            if ($errmsg = ibase_errmsg()) {
                return $this->ibaseRaiseError(null, $errmsg);
            } else {
                return null;
            }
        }
        return DB_OK;
    }

    function freeResult()
    {
        if (is_resource($result)) {
            return ibase_free_result($result);
        }
        if (!isset($this->prepare_tokens[(int)$result])) {
            return false;
        }
        unset($this->prepare_tokens[(int)$result]);
        unset($this->prepare_types[(int)$result]);
        return true;
    }

    function freeQuery($query)
    {
        ibase_free_query($query);
        return true;
    }

    function numCols($result)
    {
        $cols = ibase_num_fields($result);
        if (!$cols) {
            return $this->ibaseRaiseError();
        }
        return $cols;
    }

    function prepare($query)
    {
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $stmt = ibase_prepare($query);
        $this->manip_query[(int)$stmt] = DB::isManip($query);
        return $stmt;
    }

    function execute($stmt, $data = false)
    {
        $result = ibase_execute($stmt, $data);
        if (!$result) {
            return $this->ibaseRaiseError();
        }
        if ($this->autocommit) {
            ibase_commit($this->connection);
        }
        return DB::isManip($this->manip_query[(int)$stmt]) ? DB_OK : new DB_result($this, $result);
    }

    function autoCommit($onoff = false)
    {
        $this->autocommit = $onoff ? 1 : 0;
        return DB_OK;
    }

    function commit()
    {
        return ibase_commit($this->connection);
    }

    function rollback($trans_number)
    {
        return ibase_rollback($this->connection, $trans_number);
    }

    function transactionInit($trans_args = 0)
    {
        return $trans_args ? ibase_trans($trans_args, $this->connection) : ibase_trans();
    }

    // {{{ nextId()
    /**
     * Get the next value in a sequence.
     *
     * If the sequence does not exist, it will be created,
     * unless $ondemand is false.
     *
     * @access public
     * @param string $seq_name the name of the sequence
     * @param bool $ondemand whether to create the sequence on demand
     * @return a sequence integer, or a DB error
     */
    function nextId($seq_name, $ondemand = true)
    {
        $sqn = strtoupper(preg_replace('/[^a-z0-9_]/i', '_', $seq_name));
        $repeat = 0;
        do {
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result = $this->query("SELECT GEN_ID(${sqn}_SEQ, 1) FROM RDB\$GENERATORS"
                                   ." WHERE RDB\$GENERATOR_NAME='${sqn}_SEQ'");
            $this->popErrorHandling();
            if ($ondemand && DB::isError($result)) {
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
        $arr = $result->fetchRow(DB_FETCHMODE_ORDERED);
        $result->free();
        return $arr[0];
    }

    // }}}
    // {{{ createSequence()

    /**
     * Create the sequence
     *
     * @param string $seq_name the name of the sequence
     * @return mixed DB_OK on success or DB error on error
     * @access public
     */
    function createSequence($seq_name)
    {
        $sqn = strtoupper(preg_replace('/[^a-z0-9_]/i', '_', $seq_name));
        $this->pushErrorHandling(PEAR_ERROR_RETURN);
        $result = $this->query("CREATE GENERATOR ${sqn}_SEQ");
        $this->popErrorHandling();

        return $result;
    }

    // }}}
    // {{{ dropSequence()

    /**
     * Drop a sequence
     *
     * @param string $seq_name the name of the sequence
     * @return mixed DB_OK on success or DB error on error
     * @access public
     */
    function dropSequence($seq_name)
    {
        $sqn = strtoupper(preg_replace('/[^a-z0-9_]/i', '_', $seq_name));
        return $this->query("DELETE FROM RDB\$GENERATORS WHERE RDB\$GENERATOR_NAME='${sqn}_SEQ'");
    }

    // }}}
    // {{{ _ibaseFieldFlags()

     /**
      * get the Flags of a Field
      *
      * @param string $field_name the name of the field
      * @param string $table_name the name of the table
      *
      * @return string The flags of the field ("primary_key", "unique_key", "not_null"
      *                "default", "computed" and "blob" are supported)
      * @access private
      */
     function _ibaseFieldFlags($field_name, $table_name)
     {

         $sql = 'SELECT  R.RDB$CONSTRAINT_TYPE CTYPE'
                .' FROM  RDB$INDEX_SEGMENTS I'
                .' JOIN  RDB$RELATION_CONSTRAINTS R ON I.RDB$INDEX_NAME=R.RDB$INDEX_NAME'
               .' WHERE  I.RDB$FIELD_NAME=\''.$field_name.'\''
                  .' AND R.RDB$RELATION_NAME=\''.$table_name.'\'';
         $result = ibase_query($this->connection, $sql);
         if (empty($result)) {
             return $this->ibaseRaiseError();
         }
         if ($obj = @ibase_fetch_object($result)) {
             ibase_free_result($result);
             if (isset($obj->CTYPE)  && trim($obj->CTYPE) == 'PRIMARY KEY') {
                 $flags = 'primary_key ';
             }
             if (isset($obj->CTYPE)  && trim($obj->CTYPE) == 'UNIQUE') {
                 $flags .= 'unique_key ';
             }
         }

         $sql = 'SELECT  R.RDB$NULL_FLAG AS NFLAG,'
                      .' R.RDB$DEFAULT_SOURCE AS DSOURCE,'
                      .' F.RDB$FIELD_TYPE AS FTYPE,'
                      .' F.RDB$COMPUTED_SOURCE AS CSOURCE'
                .' FROM  RDB$RELATION_FIELDS R '
                .' JOIN  RDB$FIELDS F ON R.RDB$FIELD_SOURCE=F.RDB$FIELD_NAME'
               .' WHERE  R.RDB$RELATION_NAME=\''.$table_name.'\''
                 .' AND  R.RDB$FIELD_NAME=\''.$field_name.'\'';
         $result = ibase_query($this->connection, $sql);
         if (empty($result)) {
             return $this->ibaseRaiseError();
         }
         if ($obj = @ibase_fetch_object($result)) {
             ibase_free_result($result);
             if (isset($obj->NFLAG)) {
                 $flags .= 'not_null ';
             }
             if (isset($obj->DSOURCE)) {
                 $flags .= 'default ';
             }
             if (isset($obj->CSOURCE)) {
                 $flags .= 'computed ';
             }
             if (isset($obj->FTYPE)  && $obj->FTYPE == 261) {
                 $flags .= 'blob ';
             }
         }

         return trim($flags);
     }

     // }}}
     // {{{ tableInfo()

     /**
      * Returns information about a table or a result set
      *
      * NOTE: doesn't support 'flags'and 'table' if called from a db_result
      *
      * @param  mixed $resource Interbase result identifier or table name
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
             $id = ibase_query($this->connection,"SELECT * FROM $result");
             if (empty($id)) {
                 return $this->ibaseRaiseError();
             }
         } else { // else we want information about a resultset
             $id = $result;
             if (empty($id)) {
                 return $this->ibaseRaiseError();
             }
         }

         $count = @ibase_num_fields($id);

         // made this IF due to performance (one if is faster than $count if's)
         if (empty($mode)) {

             for ($i=0; $i<$count; $i++) {
                 $info = @ibase_field_info($id, $i);
                 $res[$i]['table'] = (is_string($result)) ? $result : '';
                 $res[$i]['name']  = $info['name'];
                 $res[$i]['type']  = $info['type'];
                 $res[$i]['len']   = $info['length'];
                 $res[$i]['flags'] = (is_string($result)) ? $this->_ibaseFieldFlags($info['name'], $result) : '';
             }

         } else { // full
             $res["num_fields"]= $count;

             for ($i=0; $i<$count; $i++) {
                 $info = @ibase_field_info($id, $i);
                 $res[$i]['table'] = (is_string($result)) ? $result : '';
                 $res[$i]['name']  = $info['name'];
                 $res[$i]['type']  = $info['type'];
                 $res[$i]['len']   = $info['length'];
                 $res[$i]['flags'] = (is_string($result)) ? $this->_ibaseFieldFlags($info['name'], $result) : '';
                 if ($mode & DB_TABLEINFO_ORDER) {
                     $res['order'][$res[$i]['name']] = $i;
                 }
                 if ($mode & DB_TABLEINFO_ORDERTABLE) {
                     $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                 }
             }
         }

         // free the result only if we were called on a table
         if (is_resource($id)) {
             ibase_free_result($id);
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
            default:
                return null;
        }
        return $sql;
    }

    // }}}
    // {{{ ibaseRaiseError()

    function ibaseRaiseError($errno = null, $errmsg = null)
    {
        if ($errmsg === null)
            $errmsg = ibase_errmsg();
        // memo for the interbase php module hackers: we need something similar
        // to mysql_errno() to retrieve error codes instead of this ugly hack
        if (preg_match('/^([^0-9\-]+)([0-9\-]+)\s+(.*)$/', $errmsg, $m)) {
            if ($errno === null) {
                $ibase_errno = (int)$m[2];
                // try to interpret Interbase error code (that's why we need ibase_errno()
                // in the interbase module to return the real error code)
                switch ($ibase_errno) {
                    case -204:
                        if (is_int(strpos($m[3], 'Table unknown'))) {
                            $errno = DB_ERROR_NOSUCHTABLE;
                        }
                    break;
                    default:
                        $errno = $this->errorCode($ibase_errno);
                }
            }
            $errmsg = $m[2] . ' ' . $m[3];
        }
        
        return $this->raiseError($errno, null, null, $errmsg,
                        $this->last_query);
    }

    // }}}

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */

?>