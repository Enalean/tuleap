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

require_once('Tracker_FormElement_Field_List_Bind.class.php');
require_once('Tracker_FormElement_Field_List_Bind_UgroupsValue.class.php');

class Tracker_FormElement_Field_List_Bind_Ugroups extends Tracker_FormElement_Field_List_Bind {

    protected $values;

    public function __construct($field, $values, $default_values, $decorators) {
        parent::__construct($field, $default_values, $decorators);
        $this->values = $values;
    }

    /**
     * @return string
     */
    protected function format($value) {
        return $value->getLabel();
    }
    /**
     * @return string
     */
    public function formatCriteriaValue($value_id) {
        $hp = Codendi_HTMLPurifier::instance();
        return  $hp->purify($this->format($this->getValue($value_id)), CODENDI_PURIFIER_CONVERT_HTML);
    }

    /**
     * @return string
     */
    public function formatMailCriteriaValue($value_id) {
        return $this->format($this->getValue($value_id));
    }

    /**
     * @param Tracker_FormElement_Field_List_Bind_UsersValue $value the value of the field
     *
     * @return string
     */
    public function formatChangesetValue($value) {
        return $value->fetchFormatted();
    }

    /**
     * @return string
     */
    public function formatChangesetValueForCSV($value) {
        return $value->fetchFormatted();
    }

    /**
     * @return array
     */
    public function getChangesetValues($changeset_id) {
        $values = array();
        foreach($this->getValueDao()->searchChangesetValues($changeset_id, $this->field->id) as $row) {
            $values[] = $this->getValueFromRow($row);
        }
        return $values;
    }

    /**
     * @return array
     */
    public function getValue($value_id) {
        $vs = $this->getAllValues();
        $v = null;
        if (isset($vs[$value_id])) {
            $v = $vs[$value_id];
        }
        return $v;
    }

    /**
     * @param string $keyword
     *
     * @return array
     */
    public function getAllValues() {
        return $this->values;
    }

    /**
     * Get a bindvalue by its row
     *
     * @param array $row The row identifying the bindvalue
     *
     * @return Tracker_FormElement_Field_List_BindValue
     */
    public function getValueFromRow($row) {
        return new Tracker_FormElement_Field_List_Bind_UsersValue($row['id'], $this->field->getTracker()->getProject(), $row['ugroup_id']);
    }

    /**
     * Get the sql fragment used to retrieve value for a changeset to display the bindvalue in table rows for example.
     * Used by OpenList.
     *
     * @return array {
     *                  'select'     => "user.user_name, user.realname, CONCAT(user.realname,' (',user.user_name,')') AS full_name",
     *                  'select_nb'  => 3,
     *                  'from'       => 'user',
     *                  'join_on_id' => 'user.user_id',
     *              }
     */
    public function getBindtableSqlFragment() {
        return array(
            'select'     => "tracker_field_list_bind_ugroups_value.id,
                             tracker_field_list_bind_ugroups_value.ugroup_id",
            'select_nb'  => 3,
            'from'       => 'tracker_field_list_bind_ugroups_value',
            'join_on_id' => 'tracker_field_list_bind_ugroups_value.id',
        );
    }

    /**
     * Get available values of this field for SOAP usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    public function getSoapAvailableValues() {
        $soap_values = array();
        $values = $this->getAllValues();
        foreach ($values as $id => $value) {
            $soap_values[] = array(
                'field_id' => $this->field->getId(),
                'bind_value_id' => $id,
                'bind_value_label' => $value->getLabel(),
            );
        }
        return $soap_values;
    }

    /**
     * Get the field data for artifact submission
     *
     * @param string  $soap_value  the soap field value (username(s))
     * @param boolean $is_multiple if the soap value is multiple or not
     *
     * @return mixed the field data corresponding to the soap_value for artifact submision (user_id)
     */
    public function getFieldData($soap_value, $is_multiple) {
        $values = $this->getAllValues();
        if ($is_multiple) {
            $return = array();
            $soap_values = explode(',', $soap_value);
            foreach ($values as $id => $value) {
                if (in_array($value->getLabel(), $soap_values)) {
                    $return[] = $id;
                }
            }
            if (count($soap_values) == count($return)) {
                return $return;
            } else {
                // if one value was not found, return null
                return null;
            }
        } else {
            foreach ($values as $id => $value) {
                if ($value->getLabel() == $soap_value) {
                    return $id;
                }
            }
            // if not found, return null
            return null;
        }
    }

    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve
     * the last changeset of all artifacts.
     * @param array $criteria_value array of criteria_value (which are array)
     * @return string
     */
    public function getCriteriaFrom($criteria_value) {
         //Only filter query if criteria is valuated
        if ($criteria_value) {
            $a = 'A_'. $this->field->id;
            $b = 'B_'. $this->field->id;
            return " INNER JOIN tracker_changeset_value AS $a
                     ON ($a.changeset_id = c.id
                         AND $a.field_id = ". $this->field->id ."
                     )
                     INNER JOIN tracker_changeset_value_list AS $b ON ($b.changeset_value_id = $a.id)
                     ";
        }
        return '';
    }

    /**
     * Get the "where" statement to allow search with this field
     * @param array $criteria_value array of id => criteria_value (which are array)
     * @return string
     * @see getCriteriaFrom
     */
    public function getCriteriaWhere($criteria_value) {
         //Only filter query if criteria is valuated
        if ($criteria_value) {
            $a = 'A_'. $this->field->id;
            $b = 'B_'. $this->field->id;
            $ids_to_search = array_intersect(
                               array_values($criteria_value),
                               array_merge(
                                   array(100),
                                   array_keys($this->getAllValues())
                               ));
            if (count($ids_to_search) > 1) {
                return " $b.bindvalue_id IN(". implode(',', $ids_to_search) .") ";
            } else if (count($ids_to_search)) {
                return " $b.bindvalue_id = ". implode('', $ids_to_search) ." ";
            }
        }
        return '';
    }

    /**
     * Get the "select" statement to retrieve field values
     * @return string
     * @see getQueryFrom
     */
    public function getQuerySelect() {
        $R1 = 'R1_'. $this->field->id;
        $R2 = 'R2_'. $this->field->id;
        $R3 = 'R3_'. $this->field->id;
        return "$R2.id AS `". $this->field->name ."`";
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     *
     * @param string $changesetvalue_table The changeset value table to use
     *
     * @return string
     */
    public function getQueryFrom($changesetvalue_table = 'tracker_changeset_value_list') {
        $R1 = 'R1_'. $this->field->id;
        $R2 = 'R2_'. $this->field->id;
        $R3 = 'R3_'. $this->field->id;
        $R4 = 'R4_'. $this->field->id;
        return "LEFT JOIN ( tracker_changeset_value AS $R1
                    INNER JOIN $changesetvalue_table AS $R3 ON ($R3.changeset_value_id = $R1.id)
                    LEFT JOIN tracker_field_list_bind_ugroups_value AS $R2 ON ($R2.id = $R3.bindvalue_id AND $R2.field_id = ". $this->field->id ." )
                    INNER JOIN ugroup AS $R4 ON ($R4.ugroup_id = $R2.ugroup_id AND (
                        ($R4.ugroup_id > 100 AND $R4.group_id = ". $this->field->getTracker()->getProject()->getID() ." )
                        OR
                        ($R4.ugroup_id <= 100 AND $R4.group_id = 100))
                    )
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = ". $this->field->id ." )";
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby() {
        $uh = UserHelper::instance();
        $R1 = 'R1_'. $this->field->id;
        $R2 = 'R2_'. $this->field->id;
        return "$R2.ugroup_id";
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() {
        $R1 = 'R1_'. $this->field->id;
        $R2 = 'R2_'. $this->field->id;
        return "$R2.id";
    }

    /**
     * Fetch sql snippets needed to compute aggregate functions on this field.
     *
     * @param array $functions The needed function. @see getAggregateFunctions
     *
     * @return array of the form array('same_query' => string(sql snippets), 'separate' => array(sql snippets))
     *               example:
     *               array(
     *                   'same_query'       => "AVG(R2_1234.value) AS velocity_AVG, STD(R2_1234.value) AS velocity_AVG",
     *                   'separate_queries' => array(
     *                       array(
     *                           'function' => 'COUNT_GRBY',
     *                           'select'   => "R2_1234.value AS label, count(*) AS value",
     *                           'group_by' => "R2_1234.value",
     *                       ),
     *                       //...
     *                   )
     *              )
     *
     *              Same query handle all queries that can be run concurrently in one query. Example:
     *               - numeric: avg, count, min, max, std, sum
     *               - selectbox: count
     *              Separate queries handle all queries that must be run spearately on their own. Example:
     *               - numeric: count group by
     *               - selectbox: count group by
     *               - multiselectbox: all (else it breaks other computations)
     */
    public function getQuerySelectAggregate($functions) {
        $R1  = 'R1_'. $this->field->id;
        $R2  = 'R2_'. $this->field->id;
        $R3  = 'R3_'. $this->field->id;
        $R4  = 'R4_'. $this->field->id;
        $same     = array();
        $separate = array();
        foreach ($functions as $f) {
            if (in_array($f, $this->field->getAggregateFunctions())) {
                if (substr($f, -5) === '_GRBY') {
                    $separate[] = array(
                        'function' => $f,
                        'select'   => "$R4.name AS label, count(*) AS value",
                        'group_by' => "$R2.name",
                    );
                } else {
                    $select = "$f($R4.name) AS `". $this->field->name ."_$f`";
                    if ($this->field->isMultiple()) {
                        $separate[] = array(
                            'function' => $f,
                            'select'   => $select,
                            'group_by' => null,
                        );
                    } else {
                        $same[] = $select;
                    }
                }
            }
        }
        return array(
            'same_query'       => implode(', ', $same),
            'separate_queries' => $separate,
        );
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value) {
        return $this->format($this->getValue($value));
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset) {
        $value = '';
        $values_array = array();
        if ($v = $changeset->getValue($this->field)) {
            $values = $v->getListValues();
            foreach($values as $val) {
                $values_array[] = $val->getLabel();
            }
        }
        return implode(",", $values_array);
    }

    public function getDao() {
        //return new Tracker_FormElement_Field_List_Bind_UsersDao();
    }
    public function getValueDao() {
        return new Tracker_FormElement_Field_List_Bind_Ugroups_ValueDao();
    }

    /**
     * Allow the user to define the bind
     *
     * @param Field $field
     *
     * @return string html
     */
    public static function fetchAdminCreateForm($field) {
    }

    /**
     * Fetch the form to edit the formElement
     *
     * @return string html
     */
    public function fetchAdminEditForm() {
        $html = '';
        $html .= '<h3>'. 'Bind to ugroups' .'</h3>';

        //Select default values
        $html .= $this->getSelectDefaultValues();

        return $html;
    }

    /**
     * Process the request
     *
     * @param array $params the request parameters
     * @param bool  $no_redirect true if we do not have to redirect the user
     *
     * @return void
     */
    public function process($params, $no_redirect = false, $redirect = false) {
        foreach ($params as $key => $value) {
            switch ($key) {
                default:
                    break;
            }
        }
        return parent::process($params, $no_redirect, $redirect);
    }

    /**
     * Transforms Bind into a SimpleXMLElement
     *
     * @param SimpleXMLElement $root        the node to which the Bind is attached (passed by reference)
     * @param array            &$xmlMapping the array of mapping XML ID => real IDs
     * @param string           $fieldID     XML ID of the binded field
     */
    public function exportToXML($root, &$xmlMapping, $fieldID) {
        //if ($this->value_function) {
        //    $child = $root->addChild('items');
        //    foreach ($this->value_function as $vf) {
        //        if ($vf) {
        //            $child->addChild('item')->addAttribute('label', $vf);
        //        }
        //    }
        //}
    }

    /**
     * Verifies the consistency of the imported Tracker
     *
     * @return true if Tracler is ok
     */
    public function testImport() {
        //if(parent::testImport()){
        //    if (get_class($this) == 'Tracker_FormElement_Field_Text') {
        //        if (!(isset($this->default_properties['rows']) && isset($this->default_properties['cols']))) {
        //            var_dump($this, 'Properties must be "rows" and "cols"');
        //            return false;
        //        }
        //    } elseif (get_class($this) == 'Tracker_FormElement_Field_String') {
        //        if (!(isset($this->default_properties['maxchars']) && isset($this->default_properties['size']))) {
        //            var_dump($this, 'Properties must be "maxchars" and "size"');
        //            return false;
        //        }
        //    }
        //}
        return true;
    }

    /**
     * Give an extract of the bindvalues defined. The extract is based on $bindvalue_ids.
     * If the $bindvalue_ids is null then return all values.
     *
     * @param array $bindvalue_ids The ids of BindValue to retrieve
     *
     * @Return array the BindValue(s)
     */
    public function getBindValues($bindvalue_ids = null) {
        $values = $this->getAllValues();
        if ($bindvalue_ids === null) {
            return $values;
        } else {
            $bv = array();
            foreach($bindvalue_ids as $i) {
                if (isset($values[$i])) {
                    $bv[$i] = $values[$i];
                }
            }
            return $bv;
        }
    }

    /**
     * Get a recipients list for notifications. This is filled by users fields for example.
     *
     * @param Tracker_Artifact_ChangesetValue_List $changeset_value The changeset
     *
     * @return array
     */
    public function getRecipients(Tracker_Artifact_ChangesetValue_List $changeset_value) {
        $recipients = array();
        foreach ($changeset_value->getListValues() as $ugroups_value) {
            $recipients = array_merge($recipients, $ugroups_value->getMembers());
        }
        return $recipients;
    }

    /**
     * Say if this fields suport notifications
     *
     * @return bool
     */
    public function isNotificationsSupported() {
        return true;
    }

    public function isValid($value) {
        return true;
    }

    /**
     * @see Tracker_FormElement_Field_Shareable
     */
    public function fixOriginalValueIds(array $value_mapping) {
        // Nothing to do: user value ids stay the same accross projects.
    }
}

?>
