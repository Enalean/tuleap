<?php

require_once('DataAccessResult.class.php');

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
    * Constucts a new DataAccess object
    * @param $host string hostname for dbserver
    * @param $user string dbserver user
    * @param $pass string dbserver user password
    * @param $db string database name
    */
    function DataAccess($host,$user,$pass,$db) {
        $this->store = array();
        $this->db = mysql_connect($host,$user,$pass) or die('Unable to access the CodeX database. Please contact your administrator.');
        if ($this->db) {
            mysql_select_db($db,$this->db);
        }
    }
    var $store;
    
    /**
    * Fetches a query resources and stores it in a local member
    * @param $sql string the database query to run
    * @return object DataAccessResult
    */
    function &fetch($sql) {
        /*$nb = isset($this->store[md5($sql)]) ? ($this->store[md5($sql)]['nb']+1) : 1;
        $this->store[md5($sql)] = array('sql' => $sql, 'nb' => $nb);
        if ($this->store[md5($sql)]['nb'] > 1) {
            echo '<code>'. $this->store[md5($sql)]['sql'] .'</code> have been fetched for the '. $this->store[md5($sql)]['nb'] .'° times. <br>';
            $traces = debug_backtrace();
            foreach($traces as $trace) {
                echo '<code>'. $trace['file']. ' #'. $trace['line'] .' ('. $trace['class'] .'::'. $trace['function'] ."</code>\n<br />";
            }
            echo '<!-- ----------------------------------'."\n";
            var_dump(debug_backtrace());
            echo ' -->';
        }*/
        $dar = new DataAccessResult($this,mysql_query($sql,$this->db));
        return $dar;
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
        $value = mysql_real_escape_string($value);
        if (!is_numeric($value) || (isset($params['force_string']) && $params['force_string'])) {
            $value = "'" . $value . "'";
        }
        return $value;
    }
}
?>
