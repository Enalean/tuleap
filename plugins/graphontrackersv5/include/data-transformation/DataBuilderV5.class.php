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

class DataBuilderV5
{

    public $field_X;
    public $field_Y;
    public $artifacts;
    public $atid;
    public $data;
    public $x_values;
    public $y_values;

    /**
    *
    *  @param field_X: base_field (field_name)on which will data will be based
    *  @param field_Y: group_field (field_name)on which will data will be grouped
    *  @param atid: the artifact type id
    *  @param artifacts: the array of artifacts to be used for data generation
    *  @return null
    */

    public function __construct($field_X, $field_Y, $atid, $artifacts)
    {
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

    public function generateData()
    {
        $ff = Tracker_FormElementFactory::instance();
        $af_x = $ff->getFormElementById($this->field_X);
        // is_null($this->field_Y))
        // $af_x->isStandardField())
        // $af_x->isUsername()

        $result['field1'] = array();
        $result['field2'] = array();
        $result['c'] = array();

        if (!is_null($this->field_Y)) {
            $ff = Tracker_FormElementFactory::instance();
            $af_y = $ff->getFormElementById($this->field_Y);
        }

        if ($af_x->isUsed() && (!isset($af_y) || $af_y->isUsed())) {
            $select   = "SELECT STRAIGHT_JOIN ";
            $from     = "FROM ";
            $where    = "WHERE ";
            $group_by = "GROUP BY ";
            $order_by = "ORDER BY ";

            // We always join on artifact: it helps to restrict the data set and
            // with the current table schema it helps joins and mysql optimiser to
            // find the right indexes without FORCE INDEX statement. This was crucial
            // on type 3 query because mysql was not able to use index on
            // (artifact_id, field_id, value_int) so perfs was horrible.
            $from     .= "artifact a ";
            $where    .= "a.artifact_id IN (" . implode(',', $this->artifacts) . ") ";

            if ($af_x->isStandardField() && (!$af_x->isUsername())) {
                //echo "1";
                $field     = "afvl.value";
                $select   .= "afvl.value AS field1 ";
                $from     .= "INNER JOIN artifact_field_value_list afvl";
                $from     .= " ON (afvl.group_artifact_id = a.group_artifact_id AND afvl.field_id=" . db_ei($af_x->getId()) . " AND afvl.value_id = a." . db_es($this->field_X) . ") ";
            } elseif ($af_x->isStandardField() && ($af_x->isUsername())) {
                //echo "2";
                $field     = "u.user_id";
                $select   .= "u.realName AS field1, u.user_id AS id1 ";
                $from     .= "INNER JOIN user u";
                $from     .= " ON (u.user_id=a." . db_es($this->field_X) . ") ";
            } elseif (!$af_x->isStandardField() && (!$af_x->isUsername())) {
                //echo "3";
                $field     = "afvl.value_id";
                $select   .= "afvl.value AS field1 ";
                $from     .= "INNER JOIN artifact_field_value afv";
                $from     .= " ON (afv.artifact_id = a.artifact_id AND afv.field_id = " . db_ei($af_x->getId()) . ") ";
                $from     .= "INNER JOIN artifact_field_value_list afvl";
                $from     .= " ON (afvl.group_artifact_id = a.group_artifact_id AND afvl.field_id = afv.field_id AND afvl.value_id = afv.valueInt) ";
            } else { //if (!$af_x->isStandardField() && ($af_x->isUsername()))
                //echo "4";
                $field     = "u.user_id";
                $select   .= "u.realName AS field1, u.user_id AS id1 ";
                $from     .= "INNER JOIN artifact_field_value afv";
                $from     .= " ON (afv.artifact_id = a.artifact_id AND afv.field_id=" . db_ei($af_x->getId()) . ") ";
                $from     .= "INNER JOIN user u";
                $from     .= " ON (u.user_id=afv.valueInt) ";
            }
            $group_by .= $field . " ";
            $order_by .= $field . " ASC";

            // now if the second field exist
            if (!is_null($this->field_Y)) {
                $af_y = new Tracker_Field();
                $af_y->fetchData($this->atid, $this->field_Y);
                if ($af_y->isStandardField() && (!$af_y->isUsername())) {
                    //echo " : 1<br>";
                    $field     = "afvl1.value_id";
                    $select   .= ",afvl1.value AS field2 ";
                    $from     .= "INNER JOIN artifact_field_value_list afvl1";
                    $from     .= " ON (afvl1.group_artifact_id = a.group_artifact_id AND afvl1.field_id = " . db_ei($af_y->getId()) . " AND afvl1.value_id = a." . db_es($af_y->getName()) . ") ";
                } elseif ($af_y->isStandardField() && ($af_y->isUsername())) {
                    //echo " : 2<br>";
                    $field     = "u1.user_id";
                    $select   .= ",u1.realName AS field2, u1.user_id AS id2 ";
                    $from     .= "INNER JOIN user u1";
                    $from     .= " ON (u1.user_id=a." . db_es($this->field_Y) . ") ";
                } elseif (!$af_y->isStandardField() && (!$af_y->isUsername())) {
                    //echo " : 3<br>";
                    $field     = "afvl1.value_id";
                    $select   .= ",afvl1.value AS field2 ";
                    $from     .= "INNER JOIN artifact_field_value afv1";
                    $from     .= " ON (afv1.artifact_id = a.artifact_id AND afv1.field_id = " . db_ei($af_y->getId()) . ") ";
                    $from     .= "INNER JOIN artifact_field_value_list afvl1";
                    $from     .= " ON (afvl1.group_artifact_id = a.group_artifact_id AND afvl1.field_id = afv1.field_id AND afvl1.value_id = afv1.valueInt) ";
                } else { //if (!$af_y->isStandardField() && ($af_y->isUsername()))
                    //echo " : 4<br>";
                    $field   = "u1.user_id";
                    $select .= ",u1.realName AS field2, u1.user_id AS id2 ";
                    $from   .= "INNER JOIN artifact_field_value afv1";
                    $from   .= " ON (afv1.artifact_id = a.artifact_id AND afv1.field_id = " . db_ei($af_y->getId()) . ") ";
                    $from   .= "INNER JOIN user u1";
                    $from   .= " ON (u1.user_id=afv1.valueInt)";
                }
                $group_by .= "," . $field . " ";
                $order_by .= "," . $field . " ASC";
            }
            $select .= ",COUNT(0) AS c ";

            //artifact permissions
            $sql_group_id = "SELECT group_id FROM artifact_group_list WHERE group_artifact_id=" . db_ei($this->atid);
            $result_group_id = db_query($sql_group_id);
            $group_id        = null;
            if (db_numrows($result_group_id) > 0) {
                $row = db_fetch_array($result_group_id);
                $group_id = $row['group_id'];
            }
            $user  = UserManager::instance()->getCurrentUser();
            if ($group_id !== null) {
                $ugroups = $user->getUgroups($group_id, array('artifact_type' => $this->atid));
            } else {
                $ugroups = ['NULL'];
            }

            $from  .= " LEFT JOIN permissions
                         ON (permissions.object_id = CONVERT(a.artifact_id USING utf8)
                             AND
                             permissions.permission_type = 'TRACKER_ARTIFACT_ACCESS') ";
            $where .= " AND (a.use_artifact_permissions = 0
                             OR
                             permissions.ugroup_id IN (" . implode(',', $ugroups) . ")
                            ) ";

            $sql = "$select $from $where $group_by $order_by";
            //echo "$sql<br>\n";
            $res = db_query($sql);
            $r   = [];
            for ($i = 0; $i < db_numrows($res); $i++) {
                $r[$i] = db_fetch_array($res);
                $result['field1'][$i] = $r[$i]['field1'];

                if ($r[$i]['id1'] == 100) {
                    $result['field1'][$i] = $GLOBALS['Language']->getText('global', 'none');
                }
                if (!is_null($this->field_Y)) {
                    $result['field2'][$i] = $r[$i]['field2'];
                    if ($r[$i]['id2'] == 100) {
                        $result['field2'][$i] = $GLOBALS['Language']->getText('global', 'none');
                    }
                }
                $result['c'][$i] = $r[$i]['c'];
            }
        }

        for ($i = 0; $i < count($result['field1']); $i++) {
            $x = array_search($result['field1'][$i], $this->x_values);
            if ($x === false) {
                $this->x_values[count($this->x_values)] = $result['field1'][$i];
            }
        }

        if (!is_null($this->field_Y)) {
            for ($i = 0; $i < count($result['field2']); $i++) {
                $y = array_search($result['field2'][$i], $this->y_values);
                if ($y === false) {
                    $this->y_values[count($this->y_values)] = $result['field2'][$i];
                }
            }
        }

        // data initialisation
        for ($i = 0; $i < count($this->x_values); $i++) {
            if (!is_null($this->field_Y)) {
                for ($j = 0; $j < count($this->y_values); $j++) {
                    $this->data[$i][$j] = 0;
                }
            } else {
                $this->data[$i] = 0;
            }
        }

        for ($i = 0; $i < count($result['c']); $i++) {
            $x = array_search($result['field1'][$i], $this->x_values);
            if (!is_null($this->field_Y)) {
                $y = array_search($result['field2'][$i], $this->y_values);
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
