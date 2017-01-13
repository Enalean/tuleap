<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('DataAccessResult.class.php');
require_once('DataAccessException.class.php');
require_once('DataAccessCredentials.class.php');

$GLOBALS['DEBUG_DAO_QUERY_COUNT'] = 0;

if(!defined('CODENDI_DB_NULL')) define('CODENDI_DB_NULL', 0);
if(!defined('CODENDI_DB_NOT_NULL')) define('CODENDI_DB_NOT_NULL', 1);

/**
 *  A simple class for querying MySQL
 */
class DataAccess {
    /**
     * Max number of reconnect attempt when client loose connexion to server
     */
    const MAX_RECO = 5;

    /**
     * @access protected
     * $db stores a database resource
     */
    var $db;

    /**
     * store the database name used to instantiate the connection
     */
    public $db_name;

    protected $opt;

    /**
     * Number of reconnect attempt when client loose connexion to server
     * @var Integer
     */
    protected $nbReco = 0;

    /** @var DataAccessCredentials */
    private $credentials;

    /**
     * Constucts a new DataAccess object
     *
     * @param DataAccessCredentials $credentials
     */
    function DataAccess(DataAccessCredentials $credentials, $opt=0) {
        $this->credentials = $credentials;
        $this->db_name     = $credentials->getDatabaseName();
        $this->opt         = $opt;
        $this->store       = array();
        $this->reconnect();
    }

    /**
     * Connect to Mysql server
     *
     * @throws DataAccessException
     */
    protected function reconnect() {
        $this->db = $this->connect();
        if ($this->db) {
            $this->nbReco = 0;
            if (!$this->set_charset('utf8')) {
                throw new DataAccessException('Unable to set the character set of the MySQL client.');
            }
            if (!mysql_select_db($this->db_name, $this->db)) {
                throw new DataAccessException('Unable to select the database ('. mysql_error($this->db) .' - '. mysql_errno() .'). Please contact your administrator.');
            }
        } else {
            throw new DataAccessException('Unable to access the database ('. mysql_error($this->db) .' - '. mysql_errno() .'). Please contact your administrator.');
        }
    }

    private function set_charset($charset) {
        if (function_exists('mysql_set_charset')) {
            return mysql_set_charset($charset, $this->db);
        } else {
            return mysql_query("SET NAMES '$charset'", $this->db);
        }
    }

    /**
     * Open a *new* connection to a RDBMS
     *
     * @return resource Returns a link identifier on success, or FALSE on failure.
     */
    protected function connect() {
        $host = $this->credentials->getHost();
        $user = $this->credentials->getUser();
        $pass = $this->credentials->getPassword();
        return mysql_connect($host, $user, $pass, true, $this->opt);
    }

    var $store;
    
    /**
     * Fetches a query resources and stores it in a local member
     * @param $sql string the database query to run
     * @return object DataAccessResult
     */
    public function query($sql, $params = array()) {
        $time = microtime(1);
        $res  = $this->mysql_query_params($sql,$params);

        // If connexion was lost during last query, re-execute it
        // 2006 correspond to "MySQL server has gone away"
        if (mysql_errno($this->db) == 2006) {
            if ($this->nbReco < self::MAX_RECO) {
                $this->nbReco++;
                $this->reconnect();
                return $this->query($sql, $params);
            } else {
                throw new DataAccessException('Unable to access the database ('. mysql_error($this->db) .' - '. mysql_errno() .'). Please contact your administrator.');
            }
        }

        if (ForgeConfig::get('DEBUG_MODE')) {
            $GLOBALS['DEBUG_DAO_QUERY_COUNT']++;
            $GLOBALS['QUERIES'][]=$sql;
            if (!isset($GLOBALS['DBSTORE'][md5($sql)])) {
                $GLOBALS['DBSTORE'][md5($sql)] = array('sql' => $sql, 'nb' => 0, 'trace' => array());
            }
            $GLOBALS['DBSTORE'][md5($sql)]['trace'][$GLOBALS['DBSTORE'][md5($sql)]['nb']++] = array(debug_backtrace(), $time, microtime(1));
        }
        return new DataAccessResult($this, $res);
    }

    /**
     * Parameterised query implementation for MySQL (similar PostgreSQL's PHP function pg_query_params)
     * Example: mysql_query_params( "SELECT * FROM my_table WHERE col1=$1 AND col2=$2", array( 42, "It's ok" ) );
     */
    function mysql_query_params($sql, $params) {
	if(!empty($params)) {
		for ($i=1 ; $i <= count($params) ; $i++) {
	   		$args[] = "$". $i;	
		}
		return mysql_query(str_replace($args, $params, $sql), $this->db);
	} else {
		return mysql_query($sql, $this->db);
	}
    }


    /**
     * Return ID generated from the previous INSERT operation.
     *
     * @return int, or 0 if the previous query does not generate an AUTO_INCREMENT value, or FALSE if no MySQL connection was established
     */
    public function lastInsertId() {
        if($this->db) {
            return mysql_insert_id($this->db);
        } else {
            return mysql_insert_id();
        }
    }

    /**
     * Return number of rows affected by the last INSERT, UPDATE or DELETE.
     *
     * @return int
     */
    public function affectedRows() {
        if($this->db) {
            return mysql_affected_rows($this->db);
        } else {
            return mysql_affected_rows();
        }
    }

    /**
     * Returns any MySQL errors
     * @return string a MySQL error
     */
    public function isError() {
        $error = '';
        if ($this->db) {
            $error = mysql_error($this->db);
        } else {
            $error = mysql_error();
        }
        // Display real error only in Debug mode (prevent DB info leaking)
        if ($error && !ForgeConfig::get('DEBUG_MODE')) {
            $error = 'DB error';
        }
        return $error;
    }

    public function getErrorMessage() {
        return mysql_error($this->db);
    }

    /**
     * Quote variable to make safe
     * @see http://php.net/mysql-real-escape-string
     * 
     * @return string
     */
    public function quoteSmart($value, $params = array()) {
        // Quote if not integer
        if ($this->db) {
            $value = mysql_real_escape_string($value, $this->db);
        } else {
            $value = mysql_escape_string($value);
        }
        return "'$value'";
    }

    /**
     * Quote schema name to make safe
     * @see http://php.net/mysql-real-escape-string
     *
     * @return string
     */
    public function quoteSmartSchema($value, $params = array()) {
        // Quote if not integer
        if ($this->db) {
            $value = mysql_real_escape_string($value, $this->db);
        } else {
            $value = mysql_escape_string($value);
        }
        if (!is_numeric($value) || (isset($params['force_string']) && $params['force_string'])) {
            $value = "`" . $value . "`";
        }
        return $value;
    }

    /**
     * Safe implode function to use with SQL queries
     * @static
     */
    function quoteSmartImplode($glue, $pieces, $params = array()) {
        $lem = array_keys($pieces);
        $str='';
        $after_first=false;
        foreach ($pieces as $piece) {
            if ($after_first) {
                $str.=$glue;
            }
            $str.=$this->quoteSmart($piece,$params);
            $after_first=true;
        }            
        return $str;
    }
    
    /**
     * cast to int
     *
     * @return int
     */
    public function escapeInt($v, $null = CODENDI_DB_NOT_NULL) {
        $m = array();
        if($null === CODENDI_DB_NULL && $v === '') {
            return 'NULL';
        }
        if(preg_match('/^([+-]?[1-9][0-9]*|[+-]?0)$/', $v, $m)) {
            return $m[1];
        }
        return '0';
    }

    public function escapeFloat($value) {
        if ($value === "") {
            return "NULL";
        }

        return floatval($value);
    }

    /**
     * Escape the ints, and implode them.
     * 
     * @param array $ints
     * 
     * $return string
     */
    public function escapeIntImplode(array $ints) {
        return implode(',', array_map(array($this, 'escapeInt'), $ints));
    }

    /**
     * Escape a value that will be used in a LIKE condition
     *
     * WARNING: This must be use only before quoteSmart otherwise you are still at risk of SQL injections
     *
     * Example escape chain:
     * $this->getDa()->quoteSmart($this->getDa()->escapeLikeValue($value));
     *
     * @return string
     */
    public function escapeLikeValue($value)
    {
        return addcslashes($value, '_%\\');
    }

    /**
     * @return string
     */
    public function quoteLikeValueSurround($value)
    {
        return $this->quoteSmart('%' . $this->escapeLikeValue($value) . '%');
    }

    /**
     * @return string
     */
    public function quoteLikeValueSuffix($value)
    {
        return $this->quoteSmart($this->escapeLikeValue($value) . '%');
    }

    /**
     * @return string
     */
    public function quoteLikeValuePrefix($value)
    {
        return $this->quoteSmart('%'. $this->escapeLikeValue($value));
    }
    
    /**
     * Retrieves the number of rows from a result set.
     *
     * @param resource $result The result resource that is being evaluated. This result comes from a call to query().
     *
     * @return int The number of rows in a result set on success, or FALSE on failure. 
     */
    public function numRows($result) {
        return mysql_num_rows($result);
    }
    
    /**
     * Fetch a result row as an associative array
     *
     * @param resource $result The result resource that is being evaluated. This result comes from a call to query().
     *
     * @return array Returns an associative array of strings that corresponds to the fetched row, or FALSE if there are no more rows. 
     */
    public function fetch($result) {
        return mysql_fetch_assoc($result);
    }

    /**
     * Backward compatibility with database.php
     *
     * @deprecated since version 4.0
     * @param type $result
     *
     * @return type
     */
    public function fetchArray($result) {
        return mysql_fetch_array($result);
    }

    /**
     * Move internal result pointer
     *
     * @param resource $result     The result resource that is being evaluated. This result comes from a call to query().
     * @param int      $row_number The desired row number of the new result pointer. 
     *
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function dataSeek($result, $row_number) {
        return mysql_data_seek($result, $row_number);
    }

    /**
     * Start a sql transaction
     */
    public function startTransaction() {
        return $this->query('START TRANSACTION');
    }

    /**
     * Rollback a sql transaction
     */
    public function rollback() {
        return $this->query('ROLLBACK');
    }

    /**
     * Commit a sql transaction
     */
    public function commit() {
        return $this->query('COMMIT');
    }
}
?>
