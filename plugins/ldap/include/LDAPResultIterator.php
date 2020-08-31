<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
class LDAPResultIterator implements SeekableIterator, Countable // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    public $list;
    public $key;
    public $valid;
    protected $ldapParams;

    /**
     * Constructor
     */
    public function __construct($info, $ldapParams)
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
    public function count()
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
    public function isEmpty()
    {
        return empty($this->list);
    }


    /**
     * Move key to the position given in parameter.
     *
     * @param $pos int
     */
    public function seek($pos)
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
    public function get($pos)
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
    public function exist()
    {
        return ! $this->isEmpty();
    }


    /**
     * Return the current element.
     *
     * Standard function implemented from Iterator interface
     *
     * @return LDAPResult
     */
    public function current()
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
    public function key()
    {
        return $this->key;
    }


    /**
     * Move forward to next element.
     *
     * Standard function implemented from Iterator interface
     */
    public function next()
    {
        $this->valid = (++$this->key < $this->count());
    }


    /**
     * Rewind the Iterator to the first element.
     *
     * Standard function implemented from Iterator interface
     */
    public function rewind()
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
    public function valid()
    {
        return $this->valid;
    }
}
