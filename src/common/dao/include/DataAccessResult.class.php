<?php
/**
 *  Fetches MySQL database rows as objects
 */
require_once('common/collection/Iterator.class.php');
class DataAccessResult extends Iterator {
    /**
    * @access protected
    * $da stores data access object
    */
    var $da;
    /**
    * @access protected
    * $query stores a query resource
    */
    var $query;

    var $_current;
    var $_row;
    
    function DataAccessResult(& $da,$query) {
        $this->da       =& $da;
        $this->query    = $query;
        if (!is_bool($query)) {
            $this->_current = -1;
            $this->_row     = false;
            $this->rewind();
        }
    }

    /**
    * Returns an array from query row or false if no more rows
    * @return mixed
    */
    function &getRow() {
        $row = $this->current();
        $this->next();
        return $row;
    }

    /**
    * Returns the number of rows affected
    * @return int
    */
    function rowCount() {
        return mysql_num_rows($this->query);
    }

    /**
    * Returns false if no errors or returns a MySQL error message
    * @return mixed
    */
    function isError() {
        $error=$this->da->isError();
        if (!empty($error))
            return $error;
        else
            return false;
    }
    
    
    // {{{ Iterator
    function &current() {
        return $this->_row;
    }
    
    function next() {
        $this->_current++;
        $this->_row = mysql_fetch_array($this->query,MYSQL_ASSOC);
    }
    
    function valid() {
        return $this->_row !== false;
    }
    
    function rewind() {
        if ($this->rowCount() > 0) {
            mysql_data_seek($this->query, 0);
            $this->next();
            $this->_current = 0;
        }
    }
    
    function key() {
        return $this->_current;
    }
    // }}}
}
?>
