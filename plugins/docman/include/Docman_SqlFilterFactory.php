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
class Docman_SqlFilterFactory
{
    public function __construct()
    {
    }
    public function getFromFilter($filter)
    {
        $f = \null;
        if (\is_a($filter, 'Docman_FilterDateAdvanced')) {
            $f = new \Docman_SqlFilterDateAdvanced($filter);
        } elseif (\is_a($filter, 'Docman_FilterDate')) {
            $f = new \Docman_SqlFilterDate($filter);
        } elseif (\is_a($filter, 'Docman_FilterGlobalText')) {
            $f = new \Docman_SqlFilterGlobalText($filter);
        } elseif (\is_a($filter, 'Docman_FilterOwner')) {
            $f = new \Docman_SqlFilterOwner($filter);
        } elseif (\is_a($filter, 'Docman_FilterText')) {
            $f = new \Docman_SqlFilterText($filter);
        } elseif (\is_a($filter, 'Docman_FilterListAdvanced')) {
            if (! \in_array(0, $filter->getValue())) {
                $f = new \Docman_SqlFilterListAdvanced($filter);
            }
        } elseif (\is_a($filter, 'Docman_FilterList')) {
            // A value equals to 0 means that we selected "All" in the list
            // so we don't want to use this filter
            if ($filter->getValue() != 0) {
                $f = new \Docman_SqlFilter($filter);
            }
        }
        return $f;
    }
}
