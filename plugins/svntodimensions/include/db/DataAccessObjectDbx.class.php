<?php
/**
 *  Base class for data access objects
 */
class DataAccessObjectDbx {
    /**
    * Private
    * $da stores data access object
    */
    var $da;

    //! A constructor
    /**
    * Constructs the Dao
    * @param $da instance of the DataAccessDbx class
    */
    function DataAccessObjectDbx( & $da ) {
        $this->da=$da;
    }

    //! An accessor
    /**
    * For SELECT queries
    * @param $sql the query string
    * @return mixed either false if error or object DataAccessResultDbx
    */
    function &retrieve($sql) {
        $result =& $this->da->fetch($sql);
        if ($error = $result->isError()) {
            trigger_error($error .' ==> '. $sql);
            $result = false;
            return $result;
        } else {
            return $result;
        }
    }

    //! An accessor
    /**
    * For INSERT, UPDATE and DELETE queries
    * @param $sql the query string
    * @return boolean true if success
    */
    function update($sql) {
        $result = $this->da->fetch($sql);
        if ($error = $result->isError()) {
            trigger_error($error .' ==> '. $sql);
            return false;
        } else {
            return true;
        }
    }
}
?>
