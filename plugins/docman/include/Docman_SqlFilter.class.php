<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class Docman_SqlFilterFactory
{
    public function __construct()
    {
    }

    public function getFromFilter($filter)
    {
        $f = null;

        if (is_a($filter, 'Docman_FilterDateAdvanced')) {
            $f = new Docman_SqlFilterDateAdvanced($filter);
        } elseif (is_a($filter, 'Docman_FilterDate')) {
            $f = new Docman_SqlFilterDate($filter);
        } elseif (is_a($filter, 'Docman_FilterGlobalText')) {
            $f = new Docman_SqlFilterGlobalText($filter);
        } elseif (is_a($filter, 'Docman_FilterOwner')) {
            $f = new Docman_SqlFilterOwner($filter);
        } elseif (is_a($filter, 'Docman_FilterText')) {
            $f = new Docman_SqlFilterText($filter);
        } elseif (is_a($filter, 'Docman_FilterListAdvanced')) {
            if (!in_array(0, $filter->getValue())) {
                $f = new Docman_SqlFilterListAdvanced($filter);
            }
        } elseif (is_a($filter, 'Docman_FilterList')) {
            // A value equals to 0 means that we selected "All" in the list
            // so we don't want to use this filter
            if ($filter->getValue() != 0) {
                $f = new Docman_SqlFilter($filter);
            }
        }
        return $f;
    }
}

class Docman_SqlFilter extends Docman_MetadataSqlQueryChunk
{

    /**
     * The search type is the full text one
     */
    public const BOOLEAN_SEARCH_TYPE = 'IN BOOLEAN MODE';

    public $filter;
    public $isRealMetadata;
    public $db;

    public function __construct($filter)
    {
        $this->filter = $filter;
        parent::__construct($filter->md);
    }

    public function getFrom()
    {
        $tables = array();

        if ($this->isRealMetadata) {
            if ($this->filter->getValue() !== null &&
               $this->filter->getValue() != '') {
                $tables[] = $this->_getMdvJoin();
            }
        }

        return $tables;
    }

    public function _getSpecificSearchChunk()
    {
        $stmt = array();

        if ($this->filter->getValue() !== null &&
           $this->filter->getValue() != '') {
            $data_access = CodendiDataAccess::instance();
            $stmt[] = $this->field . ' = ' . $data_access->quoteSmart($this->filter->getValue());
        }

        return $stmt;
    }

    public function getWhere()
    {
        $where = '';

        $whereArray = $this->_getSpecificSearchChunk();
        $where = implode(' AND ', $whereArray);

        return $where;
    }

    /*
     * There are 4 cases depending on the entered pattern:
     ** 1-pattern   ==>The boolean mode will be chosen for perf issue
     ** 2-pattern * ==>i.e
     ** 3-*pattern* ==>For this case, the search type corresponds to an SQL statement with LIKE
     ** 4-*pattern  ==>Will use the LIKE statement
     *
     * Return array with true in 'like' field if the pattern corresponds to (3) or (4)
     * and the corrsponding formatted string in 'pattern' field
     * else return false in 'like' field for the (1) and (2) case which corresponds to the full text search
     *
     *
     * @param String $qv
     *
     * @return Array
     */
    public function getSearchType($qv)
    {
        $res = array();
        if (preg_match('/^\*(.+)$/', $qv)) {
            $matches = array();
            $data_access = CodendiDataAccess::instance();
            if (preg_match('/^\*(.+)\*$/', $qv, $matches)) {
                $pattern = $data_access->quoteLikeValueSurround($matches[1]);
            } else {
                $qv_without_star = substr($qv, 1);
                $pattern         = $data_access->quoteLikeValuePrefix($qv_without_star);
            }
            $res['like']     = true;
            $res['pattern'] = $pattern;
        } else {
            $res['like'] = false;
        }
        return $res;
    }
}

class Docman_SqlFilterDate extends Docman_SqlFilter
{

    public function __construct($filter)
    {
        parent::__construct($filter);
    }

    // '<'
    public function _getEndStatement($value)
    {
        $stmt = '';
        list($time, $ok) = util_date_to_unixtime($value);
        if ($ok) {
            list($year,$month,$day) = util_date_explode($value);
            $time_before = mktime(23, 59, 59, $month, $day - 1, $year);
            $stmt = $this->field . " <= " . $time_before;
        }
        return $stmt;
    }

    // '=' means that day between 00:00 and 23:59
    public function _getEqualStatement($value)
    {
        $stmt = '';
        list($time, $ok) = util_date_to_unixtime($value);
        if ($ok) {
            list($year,$month,$day) = util_date_explode($value);
            $time_end = mktime(23, 59, 59, $month, $day, $year);
            $stmt = $this->field . " >= " . $time . " AND " . $this->field . " <= " . $time_end;
        }
        return $stmt;
    }

    // '>'
    public function _getStartStatement($value)
    {
        $stmt = '';
        list($time, $ok) = util_date_to_unixtime($value);
        if ($ok) {
            list($year,$month,$day) = util_date_explode($value);
            $time_after = mktime(0, 0, 0, $month, $day + 1, $year);
            $stmt = $this->field . " >= " . $time_after;
        }
        return $stmt;
    }

    public function _getSpecificSearchChunk()
    {
        $stmt = array();

        switch ($this->filter->getOperator()) {
            case '-1': // '<'
                $s = $this->_getEndStatement($this->filter->getValue());
                if ($s != '') {
                    $stmt[] = $s;
                }
                break;
            case '0': // '=' means that day between 00:00 and 23:59
                $s = $this->_getEqualStatement($this->filter->getValue());
                if ($s != '') {
                    $stmt[] = $s;
                }
                break;
            case '1': // '>'
            default:
                $s = $this->_getStartStatement($this->filter->getValue());
                if ($s != '') {
                    $stmt[] = $s;
                }
                break;
        }

        return $stmt;
    }
}

class Docman_SqlFilterDateAdvanced extends Docman_SqlFilterDate
{

    public function __construct($filter)
    {
        parent::__construct($filter);
    }

    public function _getSpecificSearchChunk()
    {
        $stmt = array();

        $startValue = $this->filter->getValueStart();
        $endValue   = $this->filter->getValueEnd();
        if ($startValue != '') {
            if ($endValue == $startValue) {
                // Equal
                $s = $this->_getEqualStatement($startValue);
                if ($s != '') {
                    $stmt[] = $s;
                }
            } else {
                // Lower end
                $s = $this->_getStartStatement($startValue);
                if ($s != '') {
                    $stmt[] = $s;
                }
            }
        }
        if ($endValue != '') {
            if ($endValue != $startValue) {
                // Higher end
                $s = $this->_getEndStatement($endValue);
                if ($s != '') {
                    $stmt[] = $s;
                }
            }
        }

        return $stmt;
    }
}

class Docman_SqlFilterOwner extends Docman_SqlFilter
{

    public function __construct($filter)
    {
        parent::__construct($filter);
        $this->field = 'user.user_name';
    }

    public function getFrom()
    {
        $tables = array();
        if ($this->filter->getValue() !== null
           && $this->filter->getValue() != '') {
            $tables[] = 'user ON (i.user_id = user.user_id)';
        }
        return $tables;
    }
}

class Docman_SqlFilterText extends Docman_SqlFilter
{

    public function __construct($filter)
    {
        parent::__construct($filter);
    }

    public function _getSpecificSearchChunk()
    {
        $stmt = array();
        if ($this->filter->getValue() !== null &&
           $this->filter->getValue() != '') {
            $qv = $this->filter->getValue();
            $searchType = $this->getSearchType($qv);
            if ($searchType['like']) {
                $stmt[] =  $this->field . ' LIKE ' . $searchType['pattern'];
            } else {
                $stmt[] = "MATCH (" . $this->field . ") AGAINST ('" . db_es($qv) . "' " . Docman_SqlFilter::BOOLEAN_SEARCH_TYPE . ")";
            }
        }
        return $stmt;
    }
}

class Docman_SqlFilterGlobalText extends Docman_SqlFilterText
{

    public function __construct($filter)
    {
        parent::__construct($filter);
    }

    public function getFrom()
    {
        $tables = array();
        if ($this->filter->getValue() !== null &&
           $this->filter->getValue() != '') {
            foreach ($this->filter->dynTextFields as $f) {
                $tables[] = $this->_getMdvJoin($f);
            }
        }
        return $tables;
    }

    public function _getSpecificSearchChunk()
    {
        $stmt = array();
        if ($this->filter->getValue() !== null &&
           $this->filter->getValue() != '') {
            $qv = $this->filter->getValue();
            $searchType = $this->getSearchType($qv);
            if ($searchType['like']) {
                $matches[] = ' i.title LIKE ' . $searchType['pattern'] . '  OR i.description LIKE ' . $searchType['pattern'];
                $matches[] = ' v.label LIKE ' . $searchType['pattern'] . '  OR  v.changelog LIKE ' . $searchType['pattern'] . '  OR v.filename LIKE ' . $searchType['pattern'];

                foreach ($this->filter->dynTextFields as $f) {
                    $matches[] = ' mdv_' . $f . '.valueText LIKE ' . $searchType['pattern'] . '  OR  mdv_' . $f . '.valueString LIKE ' . $searchType['pattern'];
                }

                $stmt[] = '(' . implode(' OR ', $matches) . ')';
            } else {
                $matches[] = "MATCH (i.title, i.description) AGAINST ('" . db_es($qv) . "' " . Docman_SqlFilter::BOOLEAN_SEARCH_TYPE . ")";
                $matches[] = "MATCH (v.label, v.changelog, v.filename) AGAINST ('" . db_es($qv) . "' " . Docman_SqlFilter::BOOLEAN_SEARCH_TYPE . ")";

                foreach ($this->filter->dynTextFields as $f) {
                    $matches[] = "MATCH (mdv_" . $f . ".valueText, mdv_" . $f . ".valueString) AGAINST ('" . db_es($qv) . "' " . Docman_SqlFilter::BOOLEAN_SEARCH_TYPE . ")";
                }

                $stmt[] = '(' . implode(' OR ', $matches) . ')';
            }
        }
        return $stmt;
    }
}

class Docman_SqlFilterListAdvanced extends Docman_SqlFilter
{

    public function __construct($filter)
    {
        parent::__construct($filter);
    }

    public function _getSpecificSearchChunk()
    {
        $stmt = array();

        $v = $this->filter->getValue();
        if ($v !== null
           && (count($v) > 0
               || (count($v) == 1 && $v[0] != '')
               )
           ) {
            $stmt[] = $this->field . ' IN (' . implode(',', $this->filter->getValue()) . ')';
        }

        return $stmt;
    }
}
