<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('DataAccessResult.class.php');
require_once('DataAccessException.class.php');

$GLOBALS['DEBUG_DAO_QUERY_COUNT'] = 0;

if(!defined('CODENDI_DB_NULL')) define('CODENDI_DB_NULL', 0);
if(!defined('CODENDI_DB_NOT_NULL')) define('CODENDI_DB_NOT_NULL', 1);

/**
 *  A simple class for querying MySQL
 */
class DataAccess {
    /**
    * @access protected
    * $db stores a database resource
    */
    var $db;
    
    /**
     * store the database name used to instantiate the connection
     */
    public $db_name;
    
    /**
    * Constucts a new DataAccess object
    * @param $host string hostname for dbserver
    * @param $user string dbserver user
    * @param $pass string dbserver user password
    * @param $db string database name
    */
    function DataAccess($host,$user,$pass,$db,$opt=0) {
        $this->store = array();
        $this->db = $this->connect($host, $user, $pass, $opt);
        if ($this->db) {
            mysql_query("SET NAMES 'utf8'", $this->db);
            if (!mysql_select_db($db,$this->db)) {
                trigger_error(mysql_error(), E_USER_ERROR);
            }
            $this->db_name = $db;
        } else {
            throw new DataAccessException('Unable to access the database. Please contact your administrator.');
        }
    }
    
    protected function connect($host, $user, $pass, $opt) {
        return mysql_connect($host, $user, $pass, true, $opt);
    }
    
    var $store;
    
    /**
    * Fetches a query resources and stores it in a local member
    * @param $sql string the database query to run
    * @return object DataAccessResult
    */
    function &fetch($sql) {
        $time = microtime(1);
        $res = mysql_query($sql,$this->db);
        if (isset($GLOBALS['DEBUG_MODE']) && $GLOBALS['DEBUG_MODE']) {
            $GLOBALS['DEBUG_DAO_QUERY_COUNT']++;
            $GLOBALS['QUERIES'][]=$sql;
            if (!isset($GLOBALS['DBSTORE'][md5($sql)])) {
                $GLOBALS['DBSTORE'][md5($sql)] = array('sql' => $sql, 'nb' => 0, 'trace' => array());
            }
            $GLOBALS['DBSTORE'][md5($sql)]['trace'][$GLOBALS['DBSTORE'][md5($sql)]['nb']++] = array(debug_backtrace(), $time, microtime(1));
        }
        $dar = new DataAccessResult($this, $res);
        return $dar;
    }

    /**
     * Return ID generated from the previous INSERT operation.
     *
     * @return int, or 0 if the previous query does not generate an AUTO_INCREMENT value, or FALSE if no MySQL connection was established
     */
    function lastInsertId() {
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
    function affectedRows() {
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
    function isError() {
        if ($this->db) {
            return mysql_error($this->db);
        } else {
            return mysql_error();
        }
    }
    
    /**
    * Quote variable to make safe
    * @see http://php.net/mysql-real-escape-string
    * @static
    */
    function quoteSmart($value, $params = array()) {
        // Quote if not integer
        if ($this->db) {
            $value = mysql_real_escape_string($value, $this->db);
        } else {
            $value = mysql_escape_string($value);
        }
        if (!is_numeric($value) || (isset($params['force_string']) && $params['force_string'])) {
            $value = "'" . $value . "'";
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
    

    function escapeInt($v, $null = CODENDI_DB_NOT_NULL) {
        $m = array();
        if($null === CODENDI_DB_NULL && $v === '') {
            return 'NULL';
        }
        if(preg_match('/^([+-]?[1-9][0-9]*|[+-]?0)$/', $v, $m)) {
            return $m[1];
        }
        return '0';
    }

}
?>
