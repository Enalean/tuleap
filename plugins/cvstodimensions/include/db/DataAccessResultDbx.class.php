<?php
/**
 *  Fetches MySQL database rows as objects
 */
require_once('common/collection/Iterator.class.php');
class DataAccessResultDbx extends Iterator {
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
    
    function DataAccessResultDbx(& $da,$query) {
        $this->da       =& $da;
        $this->query    = $query;
        if (!is_bool($query)) {
            $this->_current = -1;
            $this->_row     = null;
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
        return $this->query->rows;
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
        $this->_row = $this->query->data[$this->_current];
    }
    
    function valid() {       
        return $this->_row !== null;
    }
    
    function rewind() {
        if ($this->rowCount() > 0) {
            $this->_current = -1;
            $this->next();
        }
    }
    
    function key() {
        return $this->_current;
    }
    // }}}
}
?>
