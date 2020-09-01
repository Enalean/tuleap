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
class Docman_SqlFilterText extends \Docman_SqlFilter
{
    public function __construct($filter)
    {
        parent::__construct($filter);
    }
    public function _getSpecificSearchChunk()
    {
        $stmt = [];
        if ($this->filter->getValue() !== \null && $this->filter->getValue() != '') {
            $qv = $this->filter->getValue();
            $searchType = $this->getSearchType($qv);
            if ($searchType['like']) {
                $stmt[] = $this->field . ' LIKE ' . $searchType['pattern'];
            } else {
                $stmt[] = "MATCH (" . $this->field . ") AGAINST ('" . \db_es($qv) . "' " . \Docman_SqlFilter::BOOLEAN_SEARCH_TYPE . ")";
            }
        }
        return $stmt;
    }
}
