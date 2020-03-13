<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

// This class inherits from ArtifactField
class ArtifactReportField extends ArtifactField
{

    // Show this field for the query
    public $show_on_query;

    // Show this for the result
    public $show_on_result;

    // The place order for the query
    public $place_query;

    // The place order for the result
    public $place_result;

    // The column width
    public $col_width;

    /**
     *
     *
     *    @param
     *    @return bool success.
     */
    public function __construct()
    {
        parent::__construct();
        return true;
    }

    /**
     *  Set the different field attributes, specific to a report field
     *
     *    @param field_array: the array of these attributes
     *
     */
    public function setReportFieldsFromArray($field_array)
    {
     //echo "setReportFieldsFromArray<br>";
        $this->show_on_query = $field_array['show_on_query'];
        $this->show_on_result = $field_array['show_on_result'];
        $this->place_query = $field_array['place_query'];
        $this->place_result = $field_array['place_result'];
        $this->col_width = $field_array['col_width'];
    }

    /**
     *  Get the attribute show_on_query
     *
     *    @return string
     *
     */
    public function getShowOnQuery()
    {
        return $this->show_on_query;
    }

    /**
     *  Get the attribute show_on_result
     *
     *    @return string
     *
     */
    public function getShowOnResult()
    {
        return $this->show_on_result;
    }

    /**
     *  Return if the show_on_query attribute is equal to 1
     *
     *    @return bool
     *
     */
    public function isShowOnQuery()
    {
        return ( $this->show_on_query == 1 );
    }

    /**
     *  Return if the show_on_result attribute is equal to 1
     *
     *    @return string
     *
     */
    public function isShowOnResult()
    {
        return ( $this->show_on_result == 1 );
    }

    /**
     *  Get the attribute place_query
     *
     *    @return string
     *
     */
    public function getPlaceQuery()
    {
        return $this->place_query;
    }

    /**
     *  Get the attribute place_result
     *
     *    @return string
     *
     */
    public function getPlaceResult()
    {
        return $this->place_result;
    }

    /**
     *  Get the attribute col_width
     *
     *    @return int
     *
     */
    public function getColWidth()
    {
        return $this->col_width;
    }

    /**
     *  Dump the object
     *
     */
    public function dump()
    {
        return "show_on_query=" . $this->show_on_query .
         " - show_on_result=" . $this->show_on_result .
         " - place_query=" . $this->place_query .
         " - place_result=" . $this->place_result .
         " - col_width=" . $this->col_width;
    }
}
