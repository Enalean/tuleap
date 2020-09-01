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
class Docman_SqlFilterGlobalText extends \Docman_SqlFilterText
{
    public function __construct($filter)
    {
        parent::__construct($filter);
    }
    public function getFrom()
    {
        $tables = [];
        if ($this->filter->getValue() !== \null && $this->filter->getValue() != '') {
            foreach ($this->filter->dynTextFields as $f) {
                $tables[] = $this->_getMdvJoin($f);
            }
        }
        return $tables;
    }
    public function _getSpecificSearchChunk()
    {
        $stmt = [];
        if ($this->filter->getValue() !== \null && $this->filter->getValue() != '') {
            $qv = $this->filter->getValue();
            $searchType = $this->getSearchType($qv);
            if ($searchType['like']) {
                $matches[] = ' i.title LIKE ' . $searchType['pattern'] . '  OR i.description LIKE ' . $searchType['pattern'];
                $matches[] = ' v.label LIKE ' . $searchType['pattern'] . '  OR  v.changelog LIKE ' . $searchType['pattern'] . '  OR v.filename LIKE ' . $searchType['pattern'];
                foreach ($this->filter->dynTextFields as $f) {
                    $matches[] = ' mdv_' . $f . '.valueText LIKE ' . $searchType['pattern'] . '  OR  mdv_' . $f . '.valueString LIKE ' . $searchType['pattern'];
                }
                $stmt[] = '(' . \implode(' OR ', $matches) . ')';
            } else {
                $matches[] = "MATCH (i.title, i.description) AGAINST ('" . \db_es($qv) . "' " . \Docman_SqlFilter::BOOLEAN_SEARCH_TYPE . ")";
                $matches[] = "MATCH (v.label, v.changelog, v.filename) AGAINST ('" . \db_es($qv) . "' " . \Docman_SqlFilter::BOOLEAN_SEARCH_TYPE . ")";
                foreach ($this->filter->dynTextFields as $f) {
                    $matches[] = "MATCH (mdv_" . $f . ".valueText, mdv_" . $f . ".valueString) AGAINST ('" . \db_es($qv) . "' " . \Docman_SqlFilter::BOOLEAN_SEARCH_TYPE . ")";
                }
                $stmt[] = '(' . \implode(' OR ', $matches) . ')';
            }
        }
        return $stmt;
    }
}
