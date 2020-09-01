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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_SqlFilter extends \Docman_MetadataSqlQueryChunk
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
        $tables = [];
        if ($this->isRealMetadata) {
            if ($this->filter->getValue() !== \null && $this->filter->getValue() != '') {
                $tables[] = $this->_getMdvJoin();
            }
        }
        return $tables;
    }
    public function _getSpecificSearchChunk()
    {
        $stmt = [];
        if ($this->filter->getValue() !== \null && $this->filter->getValue() != '') {
            $data_access = \CodendiDataAccess::instance();
            $stmt[] = $this->field . ' = ' . $data_access->quoteSmart($this->filter->getValue());
        }
        return $stmt;
    }
    public function getWhere()
    {
        $where = '';
        $whereArray = $this->_getSpecificSearchChunk();
        $where = \implode(' AND ', $whereArray);
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
        $res = [];
        if (\preg_match('/^\*(.+)$/', $qv)) {
            $matches = [];
            $data_access = \CodendiDataAccess::instance();
            if (\preg_match('/^\*(.+)\*$/', $qv, $matches)) {
                $pattern = $data_access->quoteLikeValueSurround($matches[1]);
            } else {
                $qv_without_star = \substr($qv, 1);
                $pattern = $data_access->quoteLikeValuePrefix($qv_without_star);
            }
            $res['like'] = \true;
            $res['pattern'] = $pattern;
        } else {
            $res['like'] = \false;
        }
        return $res;
    }
}
