<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2005
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


/**
 * This class is an implementation of Iterator pattern to easily browse an LDAP
 * result set.
 *
 * @see LDAPResult
 */
class LDAPResultIterator implements SeekableIterator, Countable
{

    var $list;
    var $key;
    var $valid;
    protected $ldapParams;

    /**
     * Constructor
     */
    function __construct($info, $ldapParams)
    {
        $this->list  = $info;
        $this->key   = 0;
        $this->valid = ($this->count() > 0);
        $this->ldapParams = $ldapParams;
    }


    /**
     * Return the number of entries in a result set.
     *
     * @return int
     */
    function count()
    {
        if ($this->list && array_key_exists('count', $this->list)) {
            return $this->list['count'];
        } else {
            return 0;
        }
    }


    /**
     * Return true if there is no entries in the result set.
     *
     * @return int
     */
    function isEmpty()
    {
        return empty($this->list);
    }


    /**
     * Move key to the position given in parameter.
     *
     * @param $pos int
     */
    function seek($pos)
    {
        $this->key = $pos;
        $this->valid = true;
        if ($this->key >= $this->count()) {
            $this->valid = false;
        }
        if ($this->key < 0) {
            $this->valid = false;
        }
    }


    /**
     * Move key to the position given in parameter.
     *
     * @param $pos int
     */
    function get($pos)
    {
        $this->seek($pos);
        if ($this->valid) {
            return $this->current();
        } else {
            return false;
        }
    }


    /**
     * Return true if result set is not empty.
     *
     * @return bool
     */
    function exist()
    {
        return !$this->isEmpty();
    }


    /**
     * Return the current element.
     *
     * Standard function implemented from Iterator interface
     *
     * @return LDAPResult
     */
    function current()
    {
        return new LDAPResult($this->list[$this->key], $this->ldapParams);
    }


    /**
     * Return the key of the current element.
     *
     * Standard function implemented from Iterator interface
     *
     * @return int
     */
    function key()
    {
        return $this->key;
    }


    /**
     * Move forward to next element.
     *
     * Standard function implemented from Iterator interface
     */
    function next()
    {
        $this->valid = (++$this->key < $this->count());
    }


    /**
     * Rewind the Iterator to the first element.
     *
     * Standard function implemented from Iterator interface
     */
    function rewind()
    {
        $this->valid = true;
        $this->key   = 0;
    }


    /**
     * Check if there is a current element after calls to rewind() or next().
     *
     * Standard function implemented from Iterator interface
     *
     * @return bool
     */
    function valid()
    {
        return $this->valid;
    }
}



/**
 * This class is wrapper to access to an LDAP entry
 *
 * Used as an iterator, it allows to iterate on all the fields in the LDAP
 * result set:
 * <pre>
 * foreach($lr as $field) {
 *     echo "$field: ".$lr->get($field);
 * }
 * </pre>
 *
 * @see LDAPResultIterator
 */
class LDAPResult implements Iterator, Countable
{
    protected $ldapParams;
    protected $info;
    protected $index;

    function __construct($info, $ldapParams)
    {
        $this->ldapParams = $ldapParams;
        $this->info  = $info;
        $this->index = 0;
    }

    function getEmail()
    {
        return $this->get($this->ldapParams['mail']);
    }

    function getCommonName()
    {
        return $this->get($this->ldapParams['cn']);
    }

    public function getGroupCommonName()
    {
        if (isset($this->ldapParams['server_type']) && $this->ldapParams['server_type'] === LDAP::SERVER_TYPE_ACTIVE_DIRECTORY) {
            $group_common_name_with_uid = $this->get($this->ldapParams['grp_uid']);
            if ($group_common_name_with_uid) {
                return $group_common_name_with_uid;
            }
        }

        $group_common_name_with_cn = $this->get($this->ldapParams['grp_cn']);
        if ($group_common_name_with_cn) {
            return $group_common_name_with_cn;
        }

        return $this->getCommonName();
    }

    /**
     * @return string
     * */
    public function getGroupDisplayName()
    {
        if (isset($this->ldapParams['grp_display_name'])) {
            $display_name = $this->get($this->ldapParams['grp_display_name']);
            if ($display_name) {
                return $display_name;
            }
        }

        return $this->getGroupCommonName();
    }

    function getLogin()
    {
        return $this->get($this->ldapParams['uid']);
    }

    function getEdUid()
    {
        return $this->get($this->ldapParams['eduid']);
    }

    function getDn()
    {
        return $this->info['dn'];
    }

    function getGroupMembers()
    {
        $memberAttr = strtolower($this->ldapParams['grp_member']);
        if (isset($this->info[$memberAttr])) {
            $members = $this->info[$memberAttr];
            // Remove count from the info to be able to iterate on result
            unset($members['count']);
            return $members;
        } else {
            return array();
        }
    }

    /**
     * Returns the first entry for a given field
     *
     * An LDAP Directory can store several values for each field (for instance
     * server common names gives $this->info['cn'][0], $this->info['cn'][1], ...
     * This method only returns the first entry.
     *
     * @param String $arg Entry to get
     *
     * @return String
     */
    function get($arg)
    {
        $v = $this->getAll($arg);
        if ($v) {
            return $v[0];
        }
        return $v;
    }

    /**
     * Returns all entries for a given field
     *
     * @param String $arg Entry to get
     *
     * @return Array
     */
    function getAll($arg)
    {
        $arg = strtolower($arg);
        if (isset($this->info[$arg])) {
            return $this->info[$arg];
        } else {
            return null;
        }
    }

    function isEmpty()
    {
        return empty($this->info);
    }

    function exist()
    {
        return !$this->isEmpty();
    }

    function count()
    {
        return $this->info['count'];
    }

    function valid()
    {
        return $this->index < $this->info['count'];
    }

    function next()
    {
        $this->index++;
    }

    function rewind()
    {
        $this->index = 0;
    }

    function current()
    {
        return $this->info[$this->index];
    }

    function key()
    {
        return $this->index;
    }
}
