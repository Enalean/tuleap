<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class DbaListSet
{
    public function __construct(&$dbh)
    {
        $this->_dbh = &$dbh;
    }

    public function create_sequence($seq)
    {
        $dbh = &$this->_dbh;

        if (!$dbh->exists('max_key')) {
            // echo "initializing DbaListSet";
            // FIXME: check to see if it's really empty?
            $dbh->insert('max_key', 0);
        }

        $key = "s" . urlencode($seq);
        assert(intval($key) == 0 && !strstr($key, ':'));
        if (!$dbh->exists($key)) {
            $dbh->insert($key, "$key:$key:");
        }
    }

    public function delete_sequence($seq)
    {
        $key = "s" . urlencode($seq);
        for ($i = $this->firstkey($seq); $i; $i = $next) {
            $next = $this->next($i);
            $this->delete($i);
        }
        $this->_dbh->delete($key);
    }

    public function firstkey($seq)
    {
        $key = "s" . urlencode($seq);
        list(, $next) =  explode(':', $this->_dbh->fetch($key), 3);
        return intval($next);
    }

    public function lastkey($seq)
    {
        $key = "s" . urlencode($seq);
        list($prev) =  explode(':', $this->_dbh->fetch($key), 3);
        return intval($prev);
    }


    public function next($i)
    {
        list( , $next, ) = explode(':', $this->_dbh->fetch(intval($i)), 3);
        return intval($next);
    }

    public function prev(&$i)
    {
        list( $prev , , ) = explode(':', $this->_dbh->fetch(intval($i)), 3);
        return intval($prev);
    }

    public function exists($i)
    {
        $i = intval($i);
        return $i && $this->_dbh->exists($i);
    }

    public function fetch($i)
    {
        list(, , $data) = explode(':', $this->_dbh->fetch(intval($i)), 3);
        return $data;
    }

    public function replace($i, $data)
    {
        $dbh = &$this->_dbh;
        list($prev, $next,) = explode(':', $dbh->fetch(intval($i)), 3);
        $dbh->replace($i, "$prev:$next:$data");
    }

    public function insert_before($i, $data)
    {
        assert(intval($i));
        return $this->_insert_before_nc($i, $data);
    }

    public function insert_after($i, $data)
    {
        assert(intval($i));
        return $this->_insert_after_nc($i, $data);
    }

    public function append($seq, $data)
    {
        $key = "s" . urlencode($seq);
        $this->_insert_before_nc($key, $data);
    }

    public function prepend($seq, $data)
    {
        $key = "s" . urlencode($seq);
        $this->_insert_after_nc($key, $data);
    }

    public function _insert_before_nc($i, &$data)
    {
        $newkey = $this->_new_key();
        $old_prev = $this->_setprev($i, $newkey);
        $this->_setnext($old_prev, $newkey);
        $this->_dbh->insert($newkey, "$old_prev:$i:$data");
        return $newkey;
    }

    public function _insert_after_nc($i, &$data)
    {
        $newkey = $this->_new_key();
        $old_next = $this->_setnext($i, $newkey);
        $this->_setprev($old_next, $newkey);
        $this->_dbh->insert($newkey, "$i:$old_next:$data");
        return $newkey;
    }

    public function delete($i)
    {
        $dbh = &$this->_dbh;
        list($prev, $next) = explode(':', $dbh->fetch(intval($i)), 3);
        $this->_setnext($prev, $next);
        $this->_setprev($next, $prev);
        $dbh->delete(intval($i));
    }

    public function _new_key()
    {
        $dbh = &$this->_dbh;
        $new_key = $dbh->fetch('max_key') + 1;
        $dbh->replace('max_key', $new_key);
        return $new_key;
    }

    public function _setprev($i, $new_prev)
    {
        $dbh = &$this->_dbh;
        list($old_prev, $next, $data) = explode(':', $dbh->fetch($i), 3);
        $dbh->replace($i, "$new_prev:$next:$data");
        return $old_prev;
    }

    public function _setnext($i, $new_next)
    {
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
