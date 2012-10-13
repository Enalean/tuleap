<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 * 
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'Tracker_FormElement_Field_List_Bind.class.php';

class Tracker_FormElement_Field_List_Bind_Null extends Tracker_FormElement_Field_List_Bind {
    
    public function __construct($field) {
        parent::__construct($field, array(), array());
    }

    /**
     * @return array all values of the field
     */
     public function getAllValues() { return array(); }
    /**
     * Get available values of this field for SOAP usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    public function getSoapAvailableValues() { return array(); }
    /**
     * Get the field data for artifact submission
     *
     * @param string $soap_value  of soap field value
     * @param bool   $is_multiple if the soap value is multiple or not
     *
     * @return mixed the field data corresponding to the soap_value for artifact submision
     */
    public function getFieldData($soap_value, $is_multiple) { return array(); }
    /**
     * @return array
     */
    public function getValue($value_id) { return array(); }
    /**
     * @return array
     */
    public function getChangesetValues($changeset_id) { return array(); }
    
    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value)  { return ''; }
    
    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset)  { return ''; }
    
    /**
     * @return string
     */
    public function formatCriteriaValue($value_id) { return ''; }

    /**
     * @return string
     */
    public function formatMailCriteriaValue($value_id) { return ''; }

    /**
     * @return string
     */
    public function formatChangesetValue($value) { return ''; }
    
    /**
     * @return string
     */
    public function formatChangesetValueForCSV($value) { return ''; }
    
    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve 
     * the last changeset of all artifacts.
     * @param array $criteria_value array of criteria_value (which are array)
     * @return string
     */
    public function getCriteriaFrom($criteria_value) { return ''; }
    
    /**
     * Get the "where" statement to allow search with this field
     * @param array $criteria_value array of id => criteria_value (which are array)
     * @return string
     * @see getCriteriaFrom
     */
    public function getCriteriaWhere($criteria)  { return ''; }

    /**
     * Get the "select" statement to retrieve field values
     * @return string
     * @see getQueryFrom
     */
    public function getQuerySelect() { return ''; }
    
    
    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c 
     * which tables used to retrieve the last changeset of matching artifacts.
     *
     * @param string $changesetvalue_table The changeset value table to use
     *
     * @return string
     */
    public function getQueryFrom($changesetvalue_table = 'tracker_changeset_value_list') { return ''; }

    
    /**
     * Get a bindvalue by its row
     *
     * @param array $row The row identifying the bindvalue
     *
     * @return Tracker_FormElement_Field_List_BindValue
     */
    public function getValueFromRow($row) { return null; }
    
    /**
     * Get the sql fragment used to retrieve value for a changeset to display the bindvalue in table rows for example.
     * Used by OpenList.
     *
     * @return array {
     *                  'bindtable_select'     => "user.user_name, user.realname, CONCAT(user.realname,' (',user.user_name,')') AS full_name",
     *                  'bindtable_select_nb'  => 3,
     *                  'bindtable_from'       => 'user',
     *                  'bindtable_join_on_id' => 'user.user_id',
     *              }
     */
    public function getBindtableSqlFragment() { return array(); }
    
    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby() { return ''; }
    
    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() { return ''; }
    
    
    public function getDao() { return null; }
    public function getValueDao() { return null; }
    
    /**
     * Fetch the form to edit the formElement
     *
     * @return string html
     */
    public function fetchAdminEditForm() { return ''; }
    
    
    /**
     * Allow the user to define the bind
     *
     * @param Field $field
     *
     * @return string html
     */
    public static function fetchAdminCreateForm($field) { return ''; }
    
    /**
     * Transforms Bind into a SimpleXMLElement
     * 
     * @param SimpleXMLElement $root        the node to which the Bind is attached (passed by reference)
     * @param array            &$xmlMapping the correspondance between real ids and XML IDs
     * @param string           $fieldID     XML ID of the binded field
     */
    public function exportToXML($root, &$xmlMapping, $fieldID) { return ''; }
    
    /**
     * Give an extract of the bindvalues defined. The extract is based on $bindvalue_ids. 
     * If the $bindvalue_ids is null then return all values.
     *
     * @param array $bindvalue_ids The ids of BindValue to retrieve
     *
     * @Return array the BindValue(s)
     */
    public function getBindValues($bindvalue_ids = null) { return array(); }
    
    /**
     * Fixes original value ids after field duplication.
     * 
     * @param array $value_mapping An array associating old value ids to new value ids.
     */
    public function fixOriginalValueIds(array $value_mapping) { return array(); }
    
    public function getQuerySelectAggregate($functions) { return array(); }
}
?>
