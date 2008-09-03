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

class DataBuilder {

    var $field_X;
    var $field_Y;
    var $artifacts;
    var $atid;
    var $data;
    var $x_values;
    var $y_values;

    /**
	* class constructor
	*  @param field_X: base_field (field_name)on which will data will be based
	*  @param field_Y: group_field (field_name)on which will data will be grouped
	*  @param atid: the artifact type id
	*  @param artifacts: the array of artifacts to be used for data generation
	*  @return null
    */

    function DataBuilder($field_X,$field_Y,$atid,$artifacts) {
        $this->field_X = $field_X;
        $this->field_Y = $field_Y;
        $this->atid = $atid;
        $this->artifacts = $artifacts;
        $this->data = array();
        $this->x_values = array();
        $this->y_values = array();
    }

    /**
	* function to generate data array based on base_field (and eventually group_field if group_field != null)
	*  @return array : data array
    */

    function generateData() {
        require_once('common/tracker/ArtifactField.class.php');
        $af_x = new ArtifactField();
        $af_x->fetchData($this->atid,$this->field_X);
        // is_null($this->field_Y))
        // $af_x->isStandardField())
        // $af_x->isUsername()

        $result['field1']=array();
        $result['field2']=array();
        $result['c']=array();

        if (!is_null($this->field_Y)) {
            $af_y = new ArtifactField();
            $af_y->fetchData($this->atid,$this->field_Y);
        }
        
        if ($af_x->isUsed() && (!isset($af_y) || $af_y->isUsed())) {
            $select   = "SELECT ";
            $from     = "FROM ";
            $where    = "WHERE ";
            $group_by = "GROUP BY ";
            $order_by = "ORDER BY ";
            if ($af_x->isStandardField() && (!$af_x->isUsername())) {
                $select   .= "afvl.value AS field1 ";
                $from     .= "artifact a ";
                $from     .= "JOIN artifact_field_value_list afvl ";
                $where    .= "a.artifact_id IN (".implode($this->artifacts,',').") ";
                $where    .= "AND a.group_artifact_id=afvl.group_artifact_id ";
                $where    .= "AND a.".db_es($this->field_X)."=afvl.value_id ";
                $where    .= "AND afvl.field_id = ".db_ei($af_x->getId())." ";
                $group_by .= "afvl.value_id ";
                $order_by .= "afvl.value_id ASC";
            } else if ($af_x->isStandardField() && ($af_x->isUsername())) {
                $select   .= "u.realName AS field1, u.user_id AS id1 ";
                $from     .= "artifact a ";
                $from     .= "JOIN user u ";
                $where    .= "a.artifact_id IN (".implode($this->artifacts,',').") ";
                $where    .= "AND u.user_id=a.".db_es($this->field_X)." ";
                $group_by .= "u.user_id ";
                $order_by .= "u.user_id ASC";
            } else if (!$af_x->isStandardField() && (!$af_x->isUsername())) {
                $select   .= "afvl.value AS field1 ";
                $from     .= "artifact_field_value afv ";
                $from     .= "JOIN artifact_field_value_list afvl ";
                $where    .= "afv.artifact_id IN (".implode($this->artifacts,',').") ";
                $where    .= "AND afv.valueInt=afvl.value_id ";
                $where    .= "AND afvl.field_id = ".db_ei($af_x->getId())." ";
                $where    .= "AND afv.field_id = ".db_ei($af_x->getId())." ";
                $where    .= "AND afvl.group_artifact_id = ".db_ei($this->atid)." ";
                $where    .= "AND afvl.field_id = afv.field_id ";
                $group_by .= "afvl.value_id ";
                $order_by .= "afvl.order_id ASC";
            } else { //if (!$af_x->isStandardField() && ($af_x->isUsername()))
                $select   .= "u.realName AS field1, u.user_id AS id1 ";
                $from     .= "artifact_field_value afv ";
                $from     .= "JOIN user u ";
                $where    .= "afv.artifact_id IN (".implode($this->artifacts,',').") ";
                $where    .= "AND afv.field_id=".db_ei($af_x->getId())." ";
                $where    .= "AND u.user_id=afv.valueInt ";
                $group_by .= "u.user_id ";
                $order_by .= "u.user_id ASC";
            }
    
            // now if the second field exist
            if (!is_null($this->field_Y)) {
                $af_y = new ArtifactField();
                $af_y->fetchData($this->atid,$this->field_Y);
                if ($af_y->isStandardField() && (!$af_y->isUsername())) {
                    $select .= ",afvl1.value AS field2 ";
                    if ($af_x->isStandardField()) {
                        $where .= "AND a.".db_es($this->field_Y)."=afvl1.value_id ";
                    } else if (!$af_x->isStandardField()) {
                        $from  .= "JOIN artifact a ";
                        $where .= "AND a.artifact_id=afv.artifact_id ";
                    }
                    if (!$af_x->isUsername()) {
                        $where .= "AND afvl.group_artifact_id=afvl1.group_artifact_id ";
                    }
                    $from  .= "JOIN artifact_field_value_list afvl1 ";
                    $where .= "AND afvl1.field_id = ".db_ei($af_y->getId())." ";
                    $where .= "AND a.group_artifact_id=afvl1.group_artifact_id ";
                    $where .= "AND a.".db_es($af_y->getName())."=afvl1.value_id ";
    
                    $group_by .= ",afvl1.value_id ";
                    $order_by .= ",afvl1.value_id ASC";
                } else if ($af_y->isStandardField() && ($af_y->isUsername())) {
                    $select   .= ",u1.realName AS field2, u1.user_id AS id2 ";
                    if (!$af_x->isStandardField()) {
                        $from     .= "JOIN artifact a  ";
                        $where    .= "AND a.artifact_id=afv.artifact_id ";
                    }
                    if (!$af_x->isUsername()) {
                        $where    .= "AND a.group_artifact_id=afvl.group_artifact_id ";
                    }
                    $from     .= "JOIN user u1 ";
                    $where    .= "AND u1.user_id=a.".db_es($this->field_Y)." ";
                    $group_by .= ",u1.user_id ";
                    $order_by .= ",u1.user_id ASC";
               } else if (!$af_y->isStandardField() && (!$af_y->isUsername())) {
                   $select   .= ",afvl1.value AS field2 ";
                   $from     .= "JOIN artifact_field_value afv1 ";
                   $from     .= "JOIN artifact_field_value_list afvl1 ";
                   if (!$af_x->isStandardField()) {
                       $from  .= "JOIN artifact a ";
                       $where .= "AND afv.artifact_id=a.artifact_id ";
                       if (!$af_x->isUsername()) {
                           $where .= "AND a.group_artifact_id=afvl.group_artifact_id ";
                       }
                   }
                   $where    .= "AND a.artifact_id = afv1.artifact_id ";
                   $where    .= "AND a.group_artifact_id=afvl1.group_artifact_id ";
                   $where    .= "AND afv1.artifact_id IN (".implode($this->artifacts,',').") ";
                   $where    .= "AND afv1.valueInt=afvl1.value_id ";
                   $where    .= "AND afvl1.field_id = ".db_ei($af_y->getId())." ";
                   $where    .= "AND afvl1.field_id = afv1.field_id ";
                   $group_by .= ",afvl1.value_id ";
                   $order_by .= ",afvl1.value_id ASC";
                } else { //if (!$af_y->isStandardField() && ($af_y->isUsername()))
                    $select .= ",u1.realName AS field2, u1.user_id AS id2 ";
                    $from   .= "JOIN artifact_field_value afv1 ";
                    $from   .= "JOIN user u1 ";
                    $where  .= "AND afv1.artifact_id IN (".implode($this->artifacts,',').") ";
                    $where  .= "AND afv1.field_id=".db_ei($af_y->getId())." ";
                    if (!$af_x->isStandardField()) {
                        $from  .= "JOIN artifact a  ";
                        $where .= "AND a.artifact_id=afv.artifact_id ";
                        $where .= "AND afv.artifact_id=afv1.artifact_id ";
                        if (!$af_x->isUsername()) {
                            $where .= "AND a.group_artifact_id=afvl.group_artifact_id ";
                        }
                    } else {
                        $where .= "AND a.artifact_id=afv1.artifact_id ";
                    }
    
                    $where    .= "AND u1.user_id=afv1.valueInt ";
                    $group_by .= ",u1.user_id ";
                    $order_by .= ",u1.user_id ASC ";
                }
            }
            $select .= ",COUNT(0) AS c ";
            $sql ="$select $from $where $group_by $order_by";
            //echo $sql;
            $res = db_query($sql);
            for($i=0;$i<db_numrows($res);$i++) {
                $r[$i] = db_fetch_array($res);
                $result['field1'][$i] = $r[$i]['field1'];
          
                if ($af_x->isUsername() && $r[$i]['id1']==100){
                    $result['field1'][$i]=$GLOBALS['Language']->getText('global','none');
                    
                 }
                if (!is_null($this->field_Y)) {
                    $result['field2'][$i] = $r[$i]['field2'];
                    if ($af_y->isUsername() && $r[$i]['id2']==100){
                        $result['field2'][$i]=$GLOBALS['Language']->getText('global','none');
                    
                    }  
                }
                $result['c'][$i] = $r[$i]['c'];
            }
        }

        for ($i=0;$i<count($result['field1']);$i++) {
            $x = array_search($result['field1'][$i],$this->x_values);
            if ($x === false) {
                $this->x_values[count($this->x_values)] = $result['field1'][$i];
            }
        }


        if (!is_null($this->field_Y)) {
            for ($i=0;$i<count($result['field2']);$i++) {
                $y = array_search($result['field2'][$i],$this->y_values);
                    if ($y === false) {
                        $this->y_values[count($this->y_values)] = $result['field2'][$i];
                    }
            }
        }

        // data initialisation
        for ($i=0;$i<count($this->x_values);$i++) {
            if (!is_null($this->field_Y)) {
                for ($j=0;$j<count($this->y_values);$j++) {
                    $this->data[$i][$j] = 0;
                }
            } else {
                $this->data[$i] = 0;
            }
        }


        for ($i=0;$i<count($result['c']);$i++) {
            $x = array_search($result['field1'][$i],$this->x_values);
            if (!is_null($this->field_Y)) {
                $y = array_search($result['field2'][$i],$this->y_values);
                if ($x !== false && $y !== false) {
                    $this->data[$x][$y] = $result['c'][$i];
                }
            } else {
                if ($x !== false) {
                    $this->data[$x] = $result['c'][$i];
                }
            }
        }
    }

}
?>
