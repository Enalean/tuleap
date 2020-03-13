<?php
//  Pear DB Pager - Retrieve and return information of databases
//                  result sets
//
//  Copyright (C) 2001  Tomas Von Veschler Cox <cox@idecnet.com>
//
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA
//
//
// $Id: Pager.php,v 1.2 2004/04/26 20:44:37 rurban Exp $
///
// Based on DB_Pager 0.7 from the pear.php.net repository.
// The only modifications made have been modification of the include paths.
rcs_id('$Id: Pager.php,v 1.2 2004/04/26 20:44:37 rurban Exp $');
rcs_id('From Pear CVS: Id: Pager.php,v 1.3 2002/05/12 13:59:40 cox Exp');

require_once 'PEAR.php';
require_once 'DB.php';

/**
* This class handles all the stuff needed for displaying paginated results
* from a database query of Pear DB, in a very easy way.
* Documentation and examples of use, can be found in:
* http://vulcanonet.com/soft/pager/ (could be outdated)
*
* IMPORTANT!
* Since PEAR DB already support native row limit (more fast and avaible in
* all the drivers), there is no more need to use $pager->build() or
* the $pager->fetch*() methods.
*
* Usage example:
*
*< ?php
* require_once 'DB/Pager.php';
* $db = DB::connect('your DSN string');
* $from = 0;   // The row to start to fetch from (you might want to get this
*              // param from the $_GET array
* $limit = 10; // The number of results per page
* $maxpages = 10; // The number of pages for displaying in the pager (optional)
* $res = $db->limitQuery($sql, $from, $limit);
* $nrows = 0; // Alternative you could use $res->numRows()
* while ($row = $res->fetchrow()) {
*    // XXX code for building the page here
*     $nrows++;
* }
* $data = DB_Pager::getData($from, $limit, $nrows, $maxpages);
* // XXX code for building the pager here
* ? >
*
* @see http://vulcanonet.com/soft/pager/
*/

class DB_Pager extends PEAR
{

    /**
    * Constructor
    *
    * @param object $res  A DB_result object from Pear_DB
    * @param int    $from  The row to start fetching
    * @param int    $limit  How many results per page
    * @param int    $numrows Pager will automatically
    *    find this param if is not given. If your Pear_DB backend extension
    *    doesn't support numrows(), you can manually calculate it
    *    and supply later to the constructor
    * @deprecated
    */
    public function __construct(&$res, $from, $limit, $numrows = null)
    {
        $this->res = $res;
        $this->from = $from;
        $this->limit = $limit;
        $this->numrows = $numrows;
    }

    /**
    * Calculates all the data needed by Pager to work
    *
    * @return mixed An assoc array with all the data (see getData)
    *    or DB_Error on error
    * @see DB_Pager::getData
    * @deprecated
    */
    public function build()
    {
        // if there is no numrows given, calculate it
        if ($this->numrows === null) {
            $this->numrows = $this->res->numrows();
            if (DB::isError($this->numrows)) {
                return $this->numrows;
            }
        }
        $data = $this->getData($this->from, $this->limit, $this->numrows);
        if (DB::isError($data)) {
            return $data;
        }
        $this->current = $this->from - 1;
        $this->top = $data['to'];
        return $data;
    }

    /**
    * @deprecated
    */
    public function fetchRow($mode = DB_FETCHMODE_DEFAULT)
    {
        $this->current++;
        if ($this->current >= $this->top) {
            return null;
        }
        return $this->res->fetchRow($mode, $this->current);
    }

    /**
    * @deprecated
    */
    public function fetchInto(&$arr, $mode = DB_FETCHMODE_DEFAULT)
    {
        $this->current++;
        if ($this->current >= $this->top) {
            return null;
        }
        return $this->res->fetchInto($arr, $mode, $this->current);
    }

    /*
    * Gets all the data needed to paginate results
    * This is an associative array with the following
    * values filled in:
    *
    * array(
    *    'current' => X,    // current page you are
    *    'numrows' => X,    // total number of results
    *    'next'    => X,    // row number where next page starts
    *    'prev'    => X,    // row number where prev page starts
    *    'remain'  => X,    // number of results remaning *in next page*
    *    'numpages'=> X,    // total number of pages
    *    'from'    => X,    // the row to start fetching
    *    'to'      => X,    // the row to stop fetching
    *    'limit'   => X,    // how many results per page
    *    'maxpages'   => X, // how many pages to show (google style)
    *    'firstpage'  => X, // the row number of the first page
    *    'lastpage'   => X, // the row number where the last page starts
    *    'pages'   => array(    // assoc with page "number => start row"
    *                1 => X,
    *                2 => X,
    *                3 => X
    *                )
    *    );
    * @param int $from    The row to start fetching
    * @param int $limit   How many results per page
    * @param int $numrows Number of results from query
    *
    * @return array associative array with data or DB_error on error
    *
    */
    public function &getData($from, $limit, $numrows, $maxpages = false)
    {
        if (empty($numrows) || ($numrows < 0)) {
            return null;
        }
        $from = (empty($from)) ? 0 : $from;

        if ($limit <= 0) {
            return PEAR::raiseError(
                null,
                'wrong "limit" param',
                null,
                null,
                null,
                'DB_Error',
                true
            );
        }

        // Total number of pages
        $pages = ceil($numrows / $limit);
        $data['numpages'] = $pages;

        // first & last page
        $data['firstpage'] = 1;
        $data['lastpage']  = $pages;

        // Build pages array
        $data['pages'] = array();
        for ($i = 1; $i <= $pages; $i++) {
            $offset = $limit * ($i - 1);
            $data['pages'][$i] = $offset;
            // $from must point to one page
            if ($from == $offset) {
                // The current page we are
                $data['current'] = $i;
            }
        }
        if (!isset($data['current'])) {
            return PEAR::raiseError(
                null,
                'wrong "from" param',
                null,
                null,
                null,
                'DB_Error',
                true
            );
        }

        // Limit number of pages (goole algoritm)
        if ($maxpages) {
            $radio = floor($maxpages / 2);
            $minpage = $data['current'] - $radio;
            if ($minpage < 1) {
                $minpage = 1;
            }
            $maxpage = $data['current'] + $radio - 1;
            if ($maxpage > $data['numpages']) {
                $maxpage = $data['numpages'];
            }
            foreach (range($minpage, $maxpage) as $page) {
                $tmp[$page] = $data['pages'][$page];
            }
            $data['pages'] = $tmp;
            $data['maxpages'] = $maxpages;
        } else {
            $data['maxpages'] = null;
        }

        // Prev link
        $prev = $from - $limit;
        $data['prev'] = ($prev >= 0) ? $prev : null;

        // Next link
        $next = $from + $limit;
        $data['next'] = ($next < $numrows) ? $next : null;

        // Results remaining in next page & Last row to fetch
        if ($data['current'] == $pages) {
            $data['remain'] = 0;
            $data['to'] = $numrows;
        } else {
            if ($data['current'] == ($pages - 1)) {
                $data['remain'] = $numrows - ($limit * ($pages - 1));
            } else {
                $data['remain'] = $limit;
            }
            $data['to'] = $data['current'] * $limit;
        }
        $data['numrows'] = $numrows;
        $data['from']    = $from + 1;
        $data['limit']   = $limit;

        return $data;
    }
}
