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
class Docman_SqlFilterDate extends \Docman_SqlFilter
{
    public function __construct($filter)
    {
        parent::__construct($filter);
    }
    // '<'
    public function _getEndStatement($value)
    {
        $stmt = '';
        list($time, $ok) = \util_date_to_unixtime($value);
        if ($ok) {
            list($year, $month, $day) = \util_date_explode($value);
            $time_before = \mktime(23, 59, 59, $month, $day - 1, $year);
            $stmt = $this->field . " <= " . $time_before;
        }
        return $stmt;
    }
    // '=' means that day between 00:00 and 23:59
    public function _getEqualStatement($value)
    {
        $stmt = '';
        list($time, $ok) = \util_date_to_unixtime($value);
        if ($ok) {
            list($year, $month, $day) = \util_date_explode($value);
            $time_end = \mktime(23, 59, 59, $month, $day, $year);
            $stmt = $this->field . " >= " . $time . " AND " . $this->field . " <= " . $time_end;
        }
        return $stmt;
    }
    // '>'
    public function _getStartStatement($value)
    {
        $stmt = '';
        list($time, $ok) = \util_date_to_unixtime($value);
        if ($ok) {
            list($year, $month, $day) = \util_date_explode($value);
            $time_after = \mktime(0, 0, 0, $month, $day + 1, $year);
            $stmt = $this->field . " >= " . $time_after;
        }
        return $stmt;
    }
    public function _getSpecificSearchChunk()
    {
        $stmt = [];
        switch ($this->filter->getOperator()) {
            case '-1':
                // '<'
                $s = $this->_getEndStatement($this->filter->getValue());
                if ($s != '') {
                    $stmt[] = $s;
                }
                break;
            case '0':
                // '=' means that day between 00:00 and 23:59
                $s = $this->_getEqualStatement($this->filter->getValue());
                if ($s != '') {
                    $stmt[] = $s;
                }
                break;
            case '1':
            // '>'
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
