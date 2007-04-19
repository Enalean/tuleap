<?php
/**
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2005
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */


/**
 * This class is an implementation of Iterator pattern to easily browse an LDAP
 * result set.
 *
 * @see LDAPResult
 */
class LDAPResultIterator /*implements Iterator*/ {
    var $list;
    var $key;
    var $valid;

    /**
     * Constructor
     */
    function LDAPResultIterator(&$info) {
        $this->list  =& $info;
        $this->key   = 0;
        $this->valid = ($this->count() > 0);
    }

    
    /**
     * Return the number of entries in a result set.
     *
     * @return int
     */
    function count() {
        if($this->list && array_key_exists('count', $this->list)){
            return $this->list['count'];
        }
        else {
            return 0;
        }
    }


    /**
     * Return true if there is no entries in the result set.
     *
     * @return int
     */
    function isEmpty() {
        return empty($this->list);
    }

    
    /**
     * Move key to the position given in parameter.
     *
     * @param $pos int
     */
    function seek($pos) {
        $this->key = $pos;
        $this->valid = true;
        if($this->key >= $this->count()) {
            $this->valid = false;
        }
        if($this->key < 0) {
            $this->valid = false;
        }
    }


    /**
     * Move key to the position given in parameter.
     *
     * @param $pos int
     */
    function &get($pos) {
        $this->seek($pos);
        if($this->valid) {
            return $this->current();
        }
        else {
            return false;
        }
    }

    /**
     * Return current entry in the result set and move key forward
     *
     * @return LDAPResult or false
     */
    function &iterate() {
        if($this->valid()) {
            $res =& $this->current();
            $this->next();
            return $res;
        }
        else {
            return false;
        }
    }


    /**
     * Return true if result set is not empty.
     *
     * @return boolean
     */
    function exist() {
        return !$this->isEmpty();
    }

    
    /**
     * Display the result set
     */
    function raw() {
        print "<pre>\n";
        print_r($this->list);
        print "</pre>\n";
    }


    /**
     * Return the current element.
     *
     * Standard function implemented from Iterator interface
     * 
     * @return LDAPResult
     */
    function &current() {
        return new LDAPResult($this->list[$this->key]);
    }


    /**
     * Return the key of the current element.
     *
     * Standard function implemented from Iterator interface
     * 
     * @return int
     */
    function key() {
        return $this->key;
    }


    /**
     * Move forward to next element.
     *
     * Standard function implemented from Iterator interface
     */
    function next() {
        $this->valid = (++$this->key < $this->count());        
    }


    /**
     * Rewind the Iterator to the first element.
     *
     * Standard function implemented from Iterator interface
     */
    function rewind() {
        $this->valid = true;
        $this->key   = 0;
    }


    /**
     * Check if there is a current element after calls to rewind() or next().
     *
     * Standard function implemented from Iterator interface
     * 
     * @return boolean
     */
    function valid() {
        return $this->valid;
    }
}



/**
 * This class is wrapper to access to an LDAP entry
 *
 * @see LDAPResultIterator
 */
class LDAPResult {
    var $info;

    function LDAPResult($info) {
        $this->info = $info;
    }

    function getEmail() {
        if(array_key_exists('sys_ldap_mail', $GLOBALS))
            return $this->get($GLOBALS['sys_ldap_mail']);
        return null;
    }

    function getCommonName() {
        if(array_key_exists('sys_ldap_cn', $GLOBALS))
            return $this->get($GLOBALS['sys_ldap_cn']);
        return null;
    }

    function getLogin() {
        if(array_key_exists('sys_ldap_login', $GLOBALS))
            return $this->get($GLOBALS['sys_ldap_login']);
        return null;
    }

    function getEdUid() {
        if(array_key_exists('sys_ldap_eduid', $GLOBALS))
            return $this->get($GLOBALS['sys_ldap_eduid']);
        return null;
    }
  
    function getDn() {
        return $this->info['dn'];
    }

    function get($arg) {
        return $this->info[$arg][0];
    }

    function isEmpty() {
        return empty($this->info);
    }

    function exist() {
        return !$this->isEmpty();
    }

}

?>