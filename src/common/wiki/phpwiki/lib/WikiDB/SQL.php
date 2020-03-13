<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

require_once('lib/WikiDB.php');
//require_once('lib/WikiDB/backend/PearDB.php');
//require_once('DB.php'); // Always favor use our local pear copy

class WikiDB_SQL extends WikiDB
{
    public function __construct()
    {
        include_once __DIR__ . '/backend/PearDB_mysql.php';
        $backend = new WikiDB_backend_PearDB_mysql();
        parent::__construct($backend);
    }


    /**
     * Determine whether page exists (in non-default form).
     * @see WikiDB::isWikiPage for the slow generic version
     */
    public function isWikiPage($pagename)
    {
        $pagename = (string) $pagename;
        if ($pagename === '') {
            return false;
        }
        //if (empty($this->_iwpcache)) {  $this->_iwpcache = array();  }
        if (empty($this->_cache->id_cache[$pagename])) {
            $this->_cache->_id_cache[$pagename] = $this->_backend->is_wiki_page($pagename);
        }
        return $this->_cache->_id_cache[$pagename];
    }

    // adds surrounding quotes
    public function quote($s)
    {
        return $this->_backend->_dbh->quoteSmart($s);
    }
    // no surrounding quotes because we know it's a string
    public function qstr($s)
    {
        return $this->_backend->_dbh->escapeSimple($s);
    }

    public function isOpen()
    {
        global $request;
        if (!$request->_dbi) {
            return false;
        }
        return is_resource($this->_backend->connection());
    }

    // SQL result: for simple select or create/update queries
    // returns the database specific resource type
    public function genericSqlQuery($sql, $args = false)
    {
        if ($args) {
            $result = $this->_backend->_dbh->query($sql, $args);
        } else {
            $result = $this->_backend->_dbh->query($sql);
        }
        if (DB::isError($result)) {
            $msg = $result->getMessage();
            trigger_error("SQL Error: " . DB::errorMessage($result), E_USER_WARNING);
            return false;
        } else {
            return $result;
        }
    }

    // SQL iter: for simple select or create/update queries
    // returns the generic iterator object (count,next)
    public function genericSqlIter($sql, $field_list = null)
    {
        $result = $this->genericSqlQuery($sql);
        return new WikiDB_backend_PearDB_generic_iter($this->_backend, $result);
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
