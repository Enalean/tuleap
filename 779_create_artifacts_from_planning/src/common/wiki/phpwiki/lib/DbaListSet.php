<?php rcs_id('$Id: DbaListSet.php,v 1.3 2004/11/21 11:59:14 rurban Exp $');

class DbaListSet
{
    function DbaListSet(&$dbh) {
        $this->_dbh = &$dbh;
    }

    function create_sequence($seq) {
        $dbh = &$this->_dbh;

        if (!$dbh->exists('max_key')) {
            // echo "initializing DbaListSet";
            // FIXME: check to see if it's really empty?
            $dbh->insert('max_key', 0);
        }

        $key = "s" . urlencode($seq);
        assert(intval($key) == 0 && !strstr($key, ':'));
        if (!$dbh->exists($key))
            $dbh->insert($key, "$key:$key:");
    }

    function delete_sequence($seq) {
        $key = "s" . urlencode($seq);
        for ($i = $this->firstkey($seq); $i; $i = $next) {
            $next = $this->next($i);
            $this->delete($i);
        }
        $this->_dbh->delete($key);
    }

    function firstkey($seq) {
        $key = "s" . urlencode($seq);
        list(, $next) =  explode(':', $this->_dbh->fetch($key), 3);
        return intval($next);
    }

    function lastkey($seq) {
        $key = "s" . urlencode($seq);
        list($prev) =  explode(':', $this->_dbh->fetch($key), 3);
        return intval($prev);
    }


    function next($i) {
        list( , $next, ) = explode(':', $this->_dbh->fetch(intval($i)), 3);
        return intval($next);
    }

    function prev(&$i) {
        list( $prev , , ) = explode(':', $this->_dbh->fetch(intval($i)), 3);
        return intval($prev);
    }
    
    function exists($i) {
        $i = intval($i);
        return $i && $this->_dbh->exists($i);
    }

    function fetch($i) {
        list(, , $data) = explode(':', $this->_dbh->fetch(intval($i)), 3);
        return $data;
    }

    function replace($i, $data) {
        $dbh = &$this->_dbh;
        list($prev, $next,) = explode(':', $dbh->fetch(intval($i)), 3);
        $dbh->replace($i, "$prev:$next:$data");
    }
    
    function insert_before($i, $data) {
        assert(intval($i));
        return $this->_insert_before_nc($i, $data);
    }

    function insert_after($i, $data) {
        assert(intval($i));
        return $this->_insert_after_nc($i, $data);
    }
    
    function append($seq, $data) {
        $key = "s" . urlencode($seq);
        $this->_insert_before_nc($key, $data);
    }

    function prepend($seq, $data) {
        $key = "s" . urlencode($seq);
        $this->_insert_after_nc($key, $data);
    }
    
    function _insert_before_nc($i, &$data) {
        $newkey = $this->_new_key();
        $old_prev = $this->_setprev($i, $newkey);
        $this->_setnext($old_prev, $newkey);
        $this->_dbh->insert($newkey, "$old_prev:$i:$data");
        return $newkey;
    }

    function _insert_after_nc($i, &$data) {
        $newkey = $this->_new_key();
        $old_next = $this->_setnext($i, $newkey);
        $this->_setprev($old_next, $newkey);
        $this->_dbh->insert($newkey, "$i:$old_next:$data");
        return $newkey;
    }

    function delete($i) {
        $dbh = &$this->_dbh;
        list($prev, $next) = explode(':', $dbh->fetch(intval($i)), 3);
        $this->_setnext($prev, $next);
        $this->_setprev($next, $prev);
        $dbh->delete(intval($i));
    }

    function _new_key() {
        $dbh = &$this->_dbh;
        $new_key = $dbh->fetch('max_key') + 1;
        $dbh->replace('max_key', $new_key);
        return $new_key;
    }

    function _setprev($i, $new_prev) {
        $dbh = &$this->_dbh;
        list($old_prev, $next, $data) = explode(':', $dbh->fetch($i), 3);
        $dbh->replace($i, "$new_prev:$next:$data");
        return $old_prev;
    }

    function _setnext($i, $new_next) {
        $dbh = &$this->_dbh;
        list($prev, $old_next, $data) = explode(':', $dbh->fetch($i), 3);
        $dbh->replace($i, "$prev:$new_next:$data");
        return $old_next;
    }
}


// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>