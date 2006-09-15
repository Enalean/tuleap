<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Urs Gehrig <urs@circle.ch>                                  |
// |          Mika Tuupola <tuupola@appelsiini.net>                       |
// +----------------------------------------------------------------------+
//
// Based on DB 1.5.0RC2 from the pear.php.net repository. 
// The only modification is the include path.
//
rcs_id('$Id$');
rcs_id('From Pear CVS: $Id: sqlite.php,v 1.14 2003/09/11 12:55:23 tuupola Exp');
// 
//
// Database independent query interface definition for the PECL's SQLite
// extension.
//
// SQLite function set:
//   sqlite_open, sqlite_popen, sqlite_close, sqlite_query
//   sqlite_libversion, sqlite_libencoding, sqlite_changes
//   sqlite_num_rows, sqlite_num_fields, sqlite_field_name, sqlite_seek
//   sqlite_escape_string, sqlite_busy_timeout, sqlite_last_error
//   sqlite_error_string, sqlite_unbuffered_query, sqlite_create_aggregate
//   sqlite_create_function, sqlite_last_insert_rowid, sqlite_fetch_array
//
// Formatting: 
//   # astyle --style=kr < sqlite.php > out.php
//
// Status:
//   EXPERIMENTAL

/**
* Example:  
*
<?php
    
    if (!extension_loaded('sqlite')) {
        if (!dl(stristr(PHP_OS, "WIN") ? "php_sqlite.dll" : "sqlite.so"))
            exit("Could not load the SQLite extension.\n");
    }
    
    require_once 'DB.php';
    require_once 'DB/sqlite.php';
    
    // Define a DSN
    // TODO: mode should be passed id options array, fix example.
    $dsn = array (
        'phptype'   => "sqlite",
        'database'  => getcwd() . "/test1.db",
        'mode'      => 0644
    );
    $db = &new DB_sqlite();
    $db->connect($dsn, array('persistent'=> true) );
    
    // Give a new table name
    $table = "tbl_" .  md5(uniqid(rand()));
    $table = substr($table, 0, 10);
    
    // Create a new table
    $result = $db->query("CREATE TABLE $table (comment varchar(50), 
      datetime varchar(50));");
    $result = $db->query("INSERT INTO $table VALUES ('Date and Time', '" . 
      date('Y-m-j H:i:s') . "');");
    
    // Get results
    printf("affectedRows:\t\t%s\n", $db->affectedRows() );
    $result = $db->query("SELECT FROM $table;" );
    $arr = $db->fetchRow($result );
    print_r($arr );
    $db->disconnect();
?>
*
*/

//require_once 'DB.php';
require_once 'DB/common.php';

// {{{ constants
// {{{ fetch modes
/**
 * This is a special constant that tells DB the user hasn't specified
 * any particular get mode, so the default should be used.
 */

define('DB_FETCHMODE_BOTH', SQLITE_ASSOC | SQLITE_NUM );

/* for compatibility */

define('DB_GETMODE_BOTH', DB_FETCHMODE_BOTH);

// }}}
// }}}

// {{{ class DB_sqlite

class DB_sqlite extends DB_common {
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $prepare_tokens = array();
    var $prepare_types = array();
    
    var $_lasterror = '';

    // }}}
    // {{{ constructor

    /**
    * Constructor for this class; Error codes according to sqlite_exec
    * Error Codes specification (see online manual, http://sqlite.org/c_interface.html.
    * This errorhandling based on sqlite_exec is not yet implemented.
    *
    * @access public
    */
    function DB_sqlite() {

        $this->DB_common();
        $this->phptype = 'sqlite';
        $this->dbsyntax = 'sqlite';
        $this->features = array (
                              'prepare' => false,
                              'pconnect' => true,
                              'transactions' => false,
                              'limit' => 'alter'
                          );

        // SQLite data types, http://www.sqlite.org/datatypes.html
        $this->keywords = array (
                              "BLOB"      => "",
                              "BOOLEAN"   => "",
                              "CHARACTER" => "",
                              "CLOB"      => "",
                              "FLOAT"     => "",
                              "INTEGER"   => "",
                              "KEY"       => "",
                              "NATIONAL"  => "",
                              "NUMERIC"   => "",
                              "NVARCHAR"  => "",
                              "PRIMARY"   => "",
                              "TEXT"      => "",
                              "TIMESTAMP" => "",
                              "UNIQUE"    => "",
                              "VARCHAR"   => "",
                              "VARYING"   => ""
                          );
        $this->errorcode_map = array(
                                   1  => DB_ERROR_SYNTAX,
                                   19 => DB_ERROR_CONSTRAINT,
                                   20 => DB_ERROR_MISMATCH,
                                   23 => DB_ERROR_ACCESS_VIOLATION
                               );
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to a database represented by a file.
     *
     * @param $dsn the data source name; the file is taken as 
     *        database; "sqlite://root:@host/test.db"
     * @param $persistent (optional) whether the connection should
     *        be persistent
     * @access public
     * @return int DB_OK on success, a DB error on failure
     */
    function connect($dsninfo, $persistent = false) {
        $ret = DB_OK;
        $file = $dsninfo['database'];

        if (!DB::assertExtension('sqlite')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        if (isset($file)) {
            if (!file_exists($file)) {
                touch($file );
                chmod($file, (is_numeric($dsninfo['mode']) ? $dsninfo['mode'] : 0644));
                if (!file_exists($file)) {
                    return $this->sqliteRaiseError(DB_ERROR_NOT_FOUND);
                }
            }
            if (!is_file($file)) {
                return $this->sqliteRaiseError(DB_ERROR_INVALID);
            }
            if (!is_readable($file)) {
                return $this->sqliteRaiseError(DB_ERROR_ACCESS_VIOLATION);
            }
        } else {
            return $this->sqliteRaiseError(DB_ERROR_ACCESS_VIOLATION);
        }

        $connect_function = $persistent ? 'sqlite_open' : 'sqlite_popen';
        if (!($conn = @$connect_function($dsninfo['database']) )) {
            return $this->sqliteRaiseError(DB_ERROR_NODBSELECTED);
        }
        $this->connection = $conn;
        $this->dsn = $dsninfo;

        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @access public
     * @return bool TRUE on success, FALSE if not connected.
     * @todo fix return values
     */
    function disconnect() {
        $ret = @sqlite_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to SQLite and returns the results as a SQLite resource
     * identifier.
     *
     * @param the SQL query
     * @access public
     * @return mixed returns a valid SQLite result for successful SELECT
     * queries, DB_OK for other successful queries. A DB error is
     * returned on failure.
     */
    function simpleQuery($query) {
        $ismanip = DB::isManip($query);
        $this->last_query = $query;
        $query = $this->_modifyQuery($query);
        ini_set('track_errors', true);
        $result = @sqlite_query($query, $this->connection);
        ini_restore('track_errors');
        $this->_lasterror = isset($php_errormsg) ? $php_errormsg : '';
        $this->result = $result;
        if (!$this->result ) {
            return $this->sqliteRaiseError(null);
        }
        
        /* sqlite_query() seems to allways return a resource */
        /* so cant use that. Using $ismanip instead          */
        if (!$ismanip) {
            $numRows = $this->numRows($result);

            /* if numRows() returned PEAR_Error */
            if (is_object($numRows )) {
                return $numRows;
            }
            return $result;
        }
        return DB_OK;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal sqlite result pointer to the next available result
     *
     * @param a valid sqlite result resource
     * @access public
     * @return true if a result is available otherwise return false
     */
    function nextResult($result) {
        return false;
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch and return a row of data (it uses fetchInto for that)
     *
     * @param   $result     SQLite result identifier
     * @param   $fetchmode  format of fetched row array
     * @param   $rownum     the absolute row number to fetch
     * @return  array       a row of data, or false on error
     */
    function fetchRow($result, $fetchmode=DB_FETCHMODE_DEFAULT, $rownum=null) {
        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        $res = $this->fetchInto($this->result, $arr, $fetchmode, $rownum );
        if (!$res) {
            $errno = sqlite_last_error($this->connection);
            if (!$errno) {
                return null;
            }
            return $this->raiseError($errno);
        }
        if ($res !== DB_OK) {
            return $res;
        }
        return $arr;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array. Availabe modes
     * are SQLITE_ASSOC, SQLITE_NUM and SQLITE_BOTH. An object type is not 
     * available. 
     *
     * @param $result    SQLite result identifier
     * @param $arr       (reference) array where data from the row is stored
     * @param $fetchmode how the array data should be indexed
     * @param $rownum    the row number to fetch
     * @access public
     *
     * @return int DB_OK on success, a DB error on failure
     */
    function fetchInto($result, &$arr, $fetchmode, $rownum=null) {
        if ($rownum !== null) {
            if (!@sqlite_seek($this->result, $rownum)) {
                return null;
            }
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC ) {
            $arr = sqlite_fetch_array($result, SQLITE_ASSOC);
        } else {
            $arr = sqlite_fetch_array($result, SQLITE_NUM);
        }
        if (!$arr) {
            /* See: http://bugs.php.net/bug.php?id=22328 */
            return null;
        }
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result SQLite result identifier or DB statement identifier
     * @access public
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freeResult($result) {
        if(is_resource($result)) {
            unset($result);
            return true;
        }
        // $result is a prepared query handle
        $result = (int)$result;
        if (!isset($this->prepare_tokens[$result])) {
            return false;
        }
        $this->prepare_types = array();
        $this->prepare_tokens = array();
        return true;
    }

    // }}}
    // {{{ numCols()

    /**
     * Gets the number of columns in a result set.
     *
     * @return number of columns in a result set
     */
    function numCols($result) {
        $cols = @sqlite_num_fields($result);
        if (!$cols) {
            return $this->sqliteRaiseError();
        }
        return $cols;
    }

    // }}}
    // {{{ numRows()

    /**
     * Gets the number of rows affected by a query.
     *
     * @return number of rows affected by the last query
     */
    function numRows($result) {
        $rows = @sqlite_num_rows($result);
        if (!is_integer($rows)) {
            return $this->raiseError();
        }
        return $rows;
    }

    // }}}
    // {{{ affected()

    /**
     * Gets the number of rows affected by a query.
     *
     * @return number of rows affected by the last query
     */
    function affectedRows() {
        return sqlite_changes($this->connection );
    }

    // }}}



    /**
     * Get the native error string of the last error (if any) that
     * occured on the current connection. This is used to retrieve
     * more meaningfull error messages DB_pgsql way since
     * sqlite_last_error() does not provide adequate info.
     *
     * @return string native SQLite error message
     */
    function errorNative()
    {
        return($this->_lasterror);
    }

    function errorCode($errormsg) 
    {
        static $error_regexps;
        if (empty($error_regexps)) {
            $error_regexps = array(
                '/^no such table:/' => DB_ERROR_NOSUCHTABLE,
                '/^table .* already exists$/' => DB_ERROR_ALREADY_EXISTS,
                '/^no such column:/' => DB_ERROR_NOSUCHFIELD,
                '/^near ".*": syntax error$/' => DB_ERROR_SYNTAX
             );
        }
        foreach ($error_regexps as $regexp => $code) {
            if (preg_match($regexp, $errormsg)) {
                return $code;
            }
        }
        // Fall back to DB_ERROR if there was no mapping.
        return DB_ERROR;
    }



    function dropSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("DROP TABLE $seqname");
    } 
     
    function createSequence($seq_name) 
    {
        $seqname = $this->getSequenceName($seq_name);
        $query   = 'CREATE TABLE ' . $seqname .
                   ' (id INTEGER UNSIGNED PRIMARY KEY) ';
        $result  = $this->query($query);
        if (DB::isError($result)) {
            return($result);
        } 
        $query   = "CREATE TRIGGER ${seqname}_cleanup AFTER INSERT ON $seqname 
                    BEGIN         
                        DELETE FROM $seqname WHERE id<LAST_INSERT_ROWID();
                    END ";
        $result  = $this->query($query);
        if (DB::isError($result)) {
            return($result);
        } 
        
    }

    /**
     * Get the next value in a sequence.  We emulate sequences
     * for SQLite.  Will create the sequence if it does not exist.
     *
     * @access public
     *
     * @param string $seq_name the name of the sequence
     *
     * @param bool $ondemand whether to create the sequence table on demand
     * (default is true)
     *
     * @return mixed a sequence integer, or a DB error
     */

    function nextId($seq_name, $ondemand = true)
    { 
        $seqname = $this->getSequenceName($seq_name);
 
        do {
            $repeat = 0;
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result = $this->query("INSERT INTO $seqname VALUES (NULL)");
            $this->popErrorHandling();
            if ($result == DB_OK) {
                $id = sqlite_last_insert_rowid($this->connection);
                if ($id != 0) {
                    return $id;
                }
            } elseif ($ondemand && DB::isError($result) &&
            $result->getCode() == DB_ERROR_NOSUCHTABLE) {
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                } else {
                    $repeat = 1;
                }        
            }
        } while ($repeat);

        return $this->raiseError($result);
    }


    // }}}
    // {{{ getSpecialQuery()

    /**
    * Returns the query needed to get some backend info. Refer to
    * the online manual at http://sqlite.org/sqlite.html.
    *
    * @param string $type What kind of info you want to retrieve
    * @return string The SQL query string
    */
    function getSpecialQuery($type, $args=array()) {
        $query = "";
        if(!is_array($args))
            return $this->raiseError('no key specified', null, null, null,
                                     'Argument has to be an array.');
        switch (strtolower($type)) {
        case 'master':
            $query .= "SELECT * FROM sqlite_master;";
            break;
        case 'tables':
            $query .= "SELECT name FROM sqlite_master WHERE type='table' ";
            $query .= "UNION ALL SELECT name FROM sqlite_temp_master ";
            $query .= "WHERE type='table' ORDER BY name;";
            break;
        case 'schema':
            $query .= "SELECT sql FROM (SELECT * FROM sqlite_master UNION ALL ";
            $query .= "SELECT * FROM sqlite_temp_master) ";
            $query .= "WHERE type!='meta' ORDER BY tbl_name, type DESC, name;";
            break;
        case 'schemax':
        case 'schema_x':
            /**
            * Use like:
            * $res = $db->query( $db->getSpecialQuery("schema_x", array("table" => "table3" )) );
            */
            $query .= "SELECT sql FROM (SELECT * FROM sqlite_master UNION ALL ";
            $query .= "SELECT * FROM sqlite_temp_master) ";
            $query .= sprintf("WHERE tbl_name LIKE '%s' AND type!='meta' ", $args['table'] );
            $query .= "ORDER BY type DESC, name;";
            break;
        case 'alter':
            /**
            * SQLite does not support ALTER TABLE; this is a helper query to handle this. 'table'
            * represents the table name, 'rows' the news rows to create, 'save' the row(s) to keep
            * _with_ the data.
            *
            * Use like:
            * $args = array(
            *     'table' => $table, 
            *     'rows'  => "id INTEGER PRIMARY KEY, firstname TEXT, surname TEXT, datetime TEXT",
            *     'save'  => "NULL, titel, content, datetime"
            * );
            * );
            * $res = $db->query( $db->getSpecialQuery("alter", $args ) );
            */
            $rows = strtr($args['rows'], $this->keywords );

            $query .= "BEGIN TRANSACTION;";
            $query .= "CREATE TEMPORARY TABLE {$args['table']}_backup ({$args['rows']});";
            $query .= "INSERT INTO {$args['table']}_backup SELECT {$args['save']} FROM {$args['table']};";
            $query .= "DROP TABLE {$args['table']};";
            $query .= "CREATE TABLE {$args['table']} ({$args['rows']});";
            $query .= "INSERT INTO {$args['table']} SELECT {$rows} FROM {$args['table']}_backup;";
            $query .= "DROP TABLE {$args['table']}_backup;";
            $query .= "COMMIT;";

            // This is a dirty hack, since the above query will no get executed with a single
            // query call; so here the query method will be called directly and return a select instead.
            $q = explode(";", $query );
            for($i=0; $i<8; $i++)
                $result = $this->query( $q[$i] );
            $query = "SELECT * FROM {$args['table']};";
            break;
        default:
            return null;
        }
        return $query;
    }

    // }}}
    // {{{ getDbFileStats()

    /**
    * Get the file stats for the current database. Possible arguments are
    * dev, ino, mode, nlink, uid, gid, rdev, size, atime, mtime, ctime, blksize, blocks
    * or a numeric key between 0 and 12.
    *
    * @param string $arg Array key for stats()
    * @return mixed array on an unspecified key, integer on a passed arg and
    * FALSE at a stats error.
    */
    function getDbFileStats($arg="" ) {
        $stats = stat($this->dsn['database'] );
        if ($stats == false )
            return false;
        if (is_array($stats)) {
            if(is_numeric($arg) ) {
                if(((int)$arg <= 12) & ((int)$arg >= 0))
                    return false;
                return $stats[$arg ];
            }
            if (array_key_exists(trim($arg), $stats)) {
                return $stats[$arg ];
            }
        }
        return $stats;
    }

    // }}}
    // {{{ modifyQuery()

    function modifyLimitQuery($query, $from, $count)
    {
        $query = $query . " LIMIT $count OFFSET $from";
        return $query;
    }

    /**
    * "DELETE FROM table" gives 0 affected rows in SQLite. This little hack 
    * lets you know how many rows were deleted.
    *
    * @param string $query The SQL query string
    * @return string The SQL query string
    */
    function _modifyQuery($query ) {
        if ($this->options['optimize'] == 'portability') {
            if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $query)) {
                $query = preg_replace('/^\s*DELETE\s+FROM\s+(\S+)\s*$/',
                                      'DELETE FROM \1 WHERE 1=1', $query);
            }
        }
        return $query;
    }

    // }}}
    // {{{ sqliteRaiseError()

    /**
    * Handling PEAR Errors
    *
    * @param int $errno  a PEAR error code
    * @return object  a PEAR error object
    */
    function sqliteRaiseError($errno = null) {

        if ($errno === null) {
            $native = $this->errorNative();
            $errno = $this->errorCode($native);
        }
        return $this->raiseError($errno, null, null, null,
                                 @sqlite_last_error($this->connection) . " ** " .
                                 @sqlite_error_string($this->connection));
    }

    // }}}
}

// }}}

?>
