<?php

require_once('DataAccessResultDbx.class.php');

/**
 *  A simple class for querying database (oracle, mysql...)
 */
class DataAccessDbx {
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
    function DataAccessDbx($module, $host, $db, $user,$pass) {
        $this->db = dbx_connect($module, $host, $db, $user , $pass);
        if ($this->db == 0){
            $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_connexion'));
        }
    }
    
    /**
    * Fetches a query resources and stores it in a local member
    * @param $sql string the database query to run
    * @return object DataAccessResultDbx
    */
    function &fetch($sql) {
        $dar = new DataAccessResultDbx($this,dbx_query($this->db, $sql, DBX_RESULT_ASSOC));
        return $dar;
    }

    /**
    * Returns any database errors
    * @return string a database error
    */
    function isError() {
        if ($this->db) {
            return ocierror($this->db);
        } else {
            return ocierror();
        }
    }
    
    /**
    * Quote variable to make safe
    * @see http://php.net/mysql-real-escape-string
    * @static
    */
    function quoteSmart($value, $params = array()) {
        // Quote if not integer
        $value = dbx_escape_string($this->db, $value);
        if (!is_numeric($value) || (isset($params['force_string']) && $params['force_string'])) {
            $value = "'" . $value . "'";
        }
        return $value;
    }
}
?>
