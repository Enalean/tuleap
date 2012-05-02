<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

require_once('ChartDataBuilder.class.php');

class GraphOnTrackers_Chart_PieDataBuilder extends ChartDataBuilder {
    /**
     * build bar chart properties
     *
     * @param Pie_Engine $engine object
     */
    function buildProperties($engine) {
        parent::buildProperties($engine);
        $this->buildData($engine);
    }
    
    /**
    * function to build pie chart data
    *   @param pe : pie_engine object
    *   @return array : data array
    */  
       
    function buildData(&$engine) {
        require_once('DataBuilder.class.php');
        $this->bc->field_group = null;
        $db = new DataBuilder($this->chart->getField_base(),null,$this->chart->getGraphicReport()->getAtid(),$this->artifacts);
        $db->generateData();
        $engine->data   = $db->data;
        $engine->legend = $db->x_values;
        return $engine->data;        
    }
}
?>
