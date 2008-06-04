<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
        $engine->field_base          = $this->getField_base();
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
    
    /**
    * deprecated function to generate data from a field
    *  
    *     @return array : data array
    */  
        
    function getField_base() {
        $af = new ArtifactField();
        $af->fetchData($this->chart->getGraphicReport()->getAtid(),$this->chart->getField_base());
        if ((!$af->isStandardField()) && (!$af->isUsername())) {
            $sql = "SELECT afvl.value_id AS `id`,afvl.value AS `disp`,count(0) AS `val` 
                    FROM artifact_field_value afv 
                    JOIN artifact_field af 
                    JOIN artifact_field_value_list afvl 
                    WHERE ((afvl.group_artifact_id = ".db_ei($this->chart->getGraphicReport()->getAtid()).") 
                    AND (af.field_name = '".db_es($this->chart->getField_base())."') 
                    AND (afv.artifact_id in (".implode($this->artifacts,',').")) 
                    AND (afv.field_id = af.field_id) 
                    AND (afv.valueInt = afvl.value_id) 
                    AND (af.field_id = afvl.field_id) 
                    AND (af.group_artifact_id = ".db_ei($this->chart->getGraphicReport()->getAtid()).")) 
                    GROUP BY afvl.value 
                    ORDER BY afvl.value_id";
        } else if ((!$af->isStandardField()) && ($af->isUsername())) {
            $sql = "SELECT u.user_id AS `id`,u.realName AS `disp`,count(0) AS `val` 
                    FROM artifact_field_value afv 
                    JOIN artifact_field af 
                    JOIN user u 
                    WHERE (af.field_name = '".db_es($this->chart->getField_base())."') 
                    AND (afv.artifact_id in (".implode($this->artifacts,',').")) 
                    AND (afv.field_id = af.field_id) 
                    AND (afv.valueInt = u.user_id) 
                    AND (af.group_artifact_id = ".db_ei($this->chart->getGraphicReport()->getAtid()).") 
                    GROUP BY u.user_id 
                    ORDER BY u.user_id";
        } else if (($af->isStandardField()) && (!$af->isUsername())) {
            $sql = "SELECT afvl.value as `id`,afvl.value AS `disp`,count(0) AS `val` 
                    FROM artifact a
                    JOIN artifact_field_value_list afvl
                    JOIN artifact_field af  
                    WHERE a.status_id = afvl.value_id 
                    AND af.group_artifact_id = afvl.group_artifact_id 
                    AND af.field_id=afvl.field_id  
                    AND afvl.group_artifact_id=".db_ei($this->chart->getGraphicReport()->getAtid())."  
                    AND af.field_name='".db_es($this->chart->getField_base())."' 
                    AND a.artifact_id 
                    IN (".implode($this->artifacts,',').") 
                    GROUP BY afvl.value
                    ORDER BY afvl.value_id";
        } else if (($af->isStandardField()) && ($af->isUsername())) {
            $sql = "SELECT u.user_id as `id`,u.realName AS `disp`,count(0) AS `val` 
                    FROM artifact a
                    JOIN user u
                    WHERE a.".db_es($this->chart->getField_base())." = u.user_id 
                    AND a.artifact_id 
                    IN (".implode($this->artifacts,',').") 
                    GROUP BY u.user_id
                    ORDER BY u.user_id";
        }
        //echo $sql;
        $result = array();
        $res = db_query($sql);
        for($i=0;$i<db_numrows($res);$i++) {
             $result[$i] = db_fetch_array($res);
        }
        return $result;
    }
}
?>
