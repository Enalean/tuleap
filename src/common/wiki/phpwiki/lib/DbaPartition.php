<?php rcs_id('$Id: DbaPartition.php,v 1.2 2004/11/21 11:59:14 rurban Exp $');

class DbaPartition
{
    function __construct(&$dbm, $prefix) {
        $this->_h = &$dbm;
        $this->_p = $prefix;
    }

    function open($mode = 'w') {
        $this->_h->open();
    }
    
    function close() {
        $this->_h->close();
    }
            
    function firstkey() {
        $dbh = &$this->_h;
        $prefix = &$this->_p;
        $n = strlen($prefix);
        for ($key = $dbh->firstkey(); $key !== false; $key = $dbh->nextkey()) {
            if (substr($key, 0, $n) == $prefix)
                return (string) substr($key, $n);
        }
        return false;
    }

    function nextkey() {
        $dbh = &$this->_h;
        $prefix = &$this->_p;
        $n = strlen($prefix);
        for ($key = $dbh->nextkey(); $key !== false; $key = $dbh->nextkey()) {
            if (substr($key, 0, $n) == $prefix)
                return (string) substr($key, $n);
        }
        return false;
    }

    function exists($key) {
        return $this->_h->exists($this->_p . $key);
    }
    
    function fetch($key) {
        return $this->_h->fetch($this->_p . $key);
    }

    function insert($key, $val) {
        return $this->_h->insert($this->_p . $key, $val);
    }

    function replace($key, $val) {
        return $this->_h->replace($this->_p . $key, $val);
    }

    function delete($key) {
        return $this->_h->delete($this->_p . $key);
    }

    function get($key) {
        return $this->_h->get($this->_p . $key);
    }
    
    function set($key, $val) {
        return $this->_h->set($this->_p . $key, $val);
    }

    function sync() {
        return $this->_h->sync();
    }

    function optimize() {
        return $this->_h->optimize();
    }
}


// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>