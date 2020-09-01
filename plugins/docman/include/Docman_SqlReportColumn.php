<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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
class Docman_SqlReportColumn extends \Docman_MetadataSqlQueryChunk
{
    public $column;
    public function __construct($column)
    {
        $this->column = $column;
        parent::__construct($column->md);
    }
    public function getOrderBy()
    {
        $sql = '';
        $sort = $this->column->getSort();
        if ($sort !== \null) {
            if ($sort == \PLUGIN_DOCMAN_SORT_ASC) {
                $sql = $this->field . ' ASC';
            } else {
                $sql = $this->field . ' DESC';
            }
        }
        return $sql;
    }
}
