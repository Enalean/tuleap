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
require_once('Tracker_FormElement_Field_List_Bind_UsersValue.class.php');

class Tracker_FormElement_Field_List_Bind_Users extends Tracker_FormElement_Field_List_Bind {

    protected $userManager;
    protected $value_function;
    protected $values;

    public function __construct($field, $value_function, $default_values, $decorators) {
        parent::__construct($field, $default_values, $decorators);
        $this->value_function = array();
        if ( !empty($value_function) ) {
            $this->value_function = explode(',', $value_function);
        }
        $this->userManager = UserManager::instance();
    }

    /**
     * @return array of value_functions
     */
    public function getValueFunction() {
        return $this->value_function;
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
        if ($value->getId() == 100) {
            return '';
        } else {
            return $value->fetchFormatted();
        }
    }

    /**
     * @return string
     */
    public function formatCardValue($value) {
        return $value->fetchCard();
    }
    
    /**
     * @return string
     */
    public function formatChangesetValueForCSV($value) {
        if ($value->getId() == 100) {
            return '';  // NULL value for CSV
        } else {
            return $value->getUsername();
        }
    }
    
    /**
     * @return array
     */
    public function getChangesetValues($changeset_id) {
        $uh = UserHelper::instance();
        $values = array();
        foreach($this->getDao()->searchChangesetValues($changeset_id, $this->field->id, $uh->getDisplayNameSQLQuery(), $uh->getDisplayNameSQLOrder()) as $row) {
            $values[] =  new Tracker_FormElement_Field_List_Bind_UsersValue($row['id'], $row['user_name'], $row['full_name']);
        }
        return $values;
    }
    
    /**
     * @return array
     */
    public function getValue($value_id) {
        if ($value_id == 100) {
            $v = new Tracker_FormElement_Field_List_Bind_UsersValue(0);
        } else {
            $vs = $this->getAllValues();
            $v = null;
            if (isset($vs[$value_id])) {
                $v = $vs[$value_id];
            } else {
                // User not found in the binded ugroup. Look for users that are either:
                //  1. not anymore active
                //  2. not member of the binded ugroup
                $v = $this->getAdditionnalValue($value_id);
            }
        }
        return $v;
    }
    
    /**
     * @param string $keyword
     *
     * @return array
     */
    public function getAllValues($keyword = null) {
        $sql = array();
        $da  = CodendiDataAccess::instance();
        if (!$this->values) {
            $this->values = array();
            if ( count($this->value_function) > 0 ) {
                $sql = array();
                $uh = UserHelper::instance();
                $tracker = $this->field->getTracker();
                foreach($this->value_function as $function) {
                    if ($function) {
                        switch ($function) {
                            case 'group_members':
                                $sql[] = ugroup_db_get_dynamic_members($GLOBALS['UGROUP_PROJECT_MEMBERS'], $tracker->id, $tracker->group_id, true, $keyword);
                                break;
                            case 'group_admins':
                                $sql[] = ugroup_db_get_dynamic_members($GLOBALS['UGROUP_PROJECT_ADMIN'], $tracker->id, $tracker->group_id, true, $keyword);
                                break;
                            case 'artifact_submitters':
                                $da = CodendiDataAccess::instance();
                                $field_id   = $da->escapeInt($this->field->id);
                                $tracker_id = $da->escapeInt($tracker->id);
                                if ($keyword) {
                                    $keyword = $da->quoteSmart('%'. $keyword .'%');
                                }
                                $sql[] = "(SELECT DISTINCT user.user_id, user.user_name, ". $uh->getDisplayNameSQLQuery() ."
                                          FROM tracker_artifact AS a
                                               INNER JOIN user
                                               ON ( user.user_id = a.submitted_by AND a.tracker_id = $tracker->id )
                                          ". ($keyword ? "HAVING full_name LIKE $keyword" : "") ."
                                          ORDER BY ". $uh->getDisplayNameSQLOrder() ."
                                          )";
                                break;
                            default:
                                if (preg_match('/ugroup_([0-9]+)/', $function, $matches)) {
                                    if (strlen($matches[1]) > 2) {
                                        $sql[] = ugroup_db_get_members($matches[1], true, $keyword);
                                    } else {
                                        $sql[] = ugroup_db_get_dynamic_members($matches[1], $tracker->id, $tracker->group_id, true, $keyword);
                                    }
                                }
                                break;
                        }
                    }
                }
                if ($sql) {
                    $dao = new DataAccessObject();
                    $this->values = array();
                    foreach($dao->retrieve(implode(' UNION ', $sql)) as $row) {
                        $this->values[$row['user_id']] = new Tracker_FormElement_Field_List_Bind_UsersValue($row['user_id'], $row['user_name'], $row['full_name']);
                    }
                }
            }
        }
        return $this->values;
    }
    
    /**
     * @var array of additionnal values (typically users that are not active or removed from the value_function)
     */
    protected $additionnal_values = array();
    
    /**
     * Return the addtionnal value
     *
     * @return Tracker_FormElement_Field_List_Bind_UsersValue
     */
    protected function getAdditionnalValue($value_id) {
        if (!isset($this->additionnal_values[$value_id])) {
            $this->additionnal_values[$value_id] = null;
            if ($user = $this->userManager->getUserById($value_id)) {
                $this->additionnal_values[$value_id] = new Tracker_FormElement_Field_List_Bind_UsersValue($user->getId());
            }
        }
        return $this->additionnal_values[$value_id];
    }
    
    /**
     * Get a bindvalue by its row
     *
     * @param array $row The row identifying the bindvalue
     *
     * @return Tracker_FormElement_Field_List_BindValue
     */
    public function getValueFromRow($row) {
        return new Tracker_FormElement_Field_List_Bind_UsersValue($row['id'], $row['user_name'], $row['full_name']);
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
            'select'     => "user.user_name, 
                             user.realname, 
                             CONCAT(user.realname,' (',user.user_name,')') AS full_name", //TODO: use UserHelper to respect user preferences
            'select_nb'  => 3,
            'from'       => 'user',
            'join_on_id' => 'user.user_id',
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
        $soap_values[] = array(
                        'field_id' => $this->field->getId(),
                        'bind_value_id' => 0,
                        'bind_value_label' => implode(",", $this->getValueFunction()),
                    );
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
                if (in_array($value->getUsername(), $soap_values)) {
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
                if ($value->getUsername() == $soap_value) {
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
        return "$R2.user_id AS `". $this->field->name ."`";
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
        return "LEFT JOIN ( tracker_changeset_value AS $R1 
                    INNER JOIN $changesetvalue_table AS $R3 ON ($R3.changeset_value_id = $R1.id)
                    LEFT JOIN user AS $R2 ON ($R2.user_id = $R3.bindvalue_id )
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = ". $this->field->id ." )";
    }
    
    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby() {
        $uh = UserHelper::instance();
        $R1 = 'R1_'. $this->field->id;
        $R2 = 'R2_'. $this->field->id;
        return $R2 .".". str_replace('user.', '', $uh->getDisplayNameSQLOrder());
    }
    
    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() {
        $R1 = 'R1_'. $this->field->id;
        $R2 = 'R2_'. $this->field->id;
        return "$R2.user_id";
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
        $same     = array();
        $separate = array();
        foreach ($functions as $f) {
            if (in_array($f, $this->field->getAggregateFunctions())) {
                if (substr($f, -5) === '_GRBY') {
                    $separate[] = array(
                        'function' => $f,
                        'select'   => "$R2.user_name AS label, count(*) AS value",
                        'group_by' => "$R2.user_name",
                    );
                } else {
                    $select = "$f($R2.user_name) AS `". $this->field->name ."_$f`";
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
        return new Tracker_FormElement_Field_List_Bind_UsersDao();
    }
    public function getValueDao() {
        return new UserDao();
    }
    
    /**
     * Allow the user to define the bind
     *
     * @param Field $field
     *
     * @return string html
     */
    public static function fetchAdminCreateForm($field) {
        return self::fetchSelectUsers('formElement_data[bind][value_function][]', $field, array());
    }
    
    /**
     * Fetch the form to edit the formElement
     *
     * @return string html
     */
    public function fetchAdminEditForm() {
        $html = '';
        $html .= '<h3>'. 'Bind to users' .'</h3>';
        $html .= self::fetchSelectUsers('bind[value_function][]', $this->field, $this->value_function);
        
        //Select default values
        $html .= $this->getSelectDefaultValues();
        
        return $html;
    }
    
    protected static function fetchSelectUsers($select_name, $field, $value_function) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $html .= '<input type="hidden" name="'. $select_name .'" value="" />';
        $html .= '<select multiple="multiple" name="'. $select_name .'">
                  <option value="">'.$GLOBALS['Language']->getText('global','none').'</option>';
        $selected = "";
	    if (in_array("artifact_submitters", $value_function)) {
	        $selected = 'selected="selected"';
	    }
	    $html .= '<option value="artifact_submitters" '.$selected.'>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','submitters').'</option>';
	    
	    $selected = "";
	    $ugroup_res = ugroup_db_get_ugroup($GLOBALS['UGROUP_PROJECT_MEMBERS']);
	    $name = util_translate_name_ugroup(db_result($ugroup_res, 0, 'name'));
	    if (in_array("group_members", $value_function)) {
	        $selected = 'selected="selected"';
	    }
	    $html .= '<option value="group_members" '.$selected.'>'. $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) .'</option>';
	    
	    $selected = "";
	    $ugroup_res = ugroup_db_get_ugroup($GLOBALS['UGROUP_PROJECT_ADMIN']);
	    $name = util_translate_name_ugroup(db_result($ugroup_res, 0, 'name'));
	    if (in_array("group_admins", $value_function)) {
	        $selected = 'selected="selected"';
	    }
	    $html .= '<option value="group_admins" '.$selected.'>'. $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) .'</option>';
	    
	    $ugroup_res = ugroup_db_get_existing_ugroups(100);
	    $rows = db_numrows($ugroup_res);
	    for ( $i = 0 ; $i < $rows ; $i++) {
            $ug = db_result($ugroup_res, $i,'ugroup_id');
            $selected = "";
            if (($ug == $GLOBALS['UGROUP_NONE']) || 
                ($ug == $GLOBALS['UGROUP_ANONYMOUS']) || 
                ($ug == $GLOBALS['UGROUP_PROJECT_MEMBERS']) || 
                ($ug == $GLOBALS['UGROUP_PROJECT_ADMIN']) || 
                ($ug == $GLOBALS['UGROUP_TRACKER_ADMIN']) 
            ) { 
                continue;
            }
            
            $ugr  ="ugroup_". $ug;
            if (in_array($ugr, $value_function)) {
              $selected = 'selected="selected"';
            }
            $html .= '<option value="'.$ugr.'" '.$selected.'>'. $hp->purify(util_translate_name_ugroup(db_result($ugroup_res, $i, 'name')), CODENDI_PURIFIER_CONVERT_HTML) .'</option>';	      
	    }
	    
        $group_id = $field->getTracker()->getGroupId();
	    if ($group_id != 100) {
            $ugroup_res = ugroup_db_get_existing_ugroups($group_id);
            $rows = db_numrows($ugroup_res);
            for ($i = 0 ; $i < $rows ; $i++) {
                $selected = "";
                $ug  = db_result($ugroup_res, $i,'ugroup_id');
                $ugr = "ugroup_". $ug;
                if (in_array($ugr, $value_function)) {
                    $selected = 'selected="selected"';
                }
                $html .= '<option value="'.$ugr.'" '.$selected.'>'. $hp->purify(util_translate_name_ugroup(db_result($ugroup_res, $i, 'name')), CODENDI_PURIFIER_CONVERT_HTML) .'</option>';
            }
	    }
	    $html .= '</select>';
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
                case 'value_function':
                    if (is_array($value) && $this->value_function != $value) {
                        if ($this->getDao()->save($this->field->getId(), $value)) {
                            $this->value_function = $value;
                            if (!$no_redirect) {
                                $redirect = true;
                                $GLOBALS['Response']->addFeedback('info', 'Values updated');
                            }
                        }
                    }
                    break;
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
        if ($this->value_function) {
            $child = $root->addChild('items');
            foreach ($this->value_function as $vf) {
                if ($vf) {
                    $child->addChild('item')->addAttribute('label', $vf);
                }
            }
        }
    }
    
    /**
     * Verifies the consistency of the imported Tracker
     * 
     * @return true if Tracler is ok 
     */
    public function testImport() {
        if(parent::testImport()){
            if (get_class($this) == 'Tracker_FormElement_Field_Text') {
                if (!(isset($this->default_properties['rows']) && isset($this->default_properties['cols']))) {
                    var_dump($this, 'Properties must be "rows" and "cols"');
                    return false;  
                }
            } elseif (get_class($this) == 'Tracker_FormElement_Field_String') {
                if (!(isset($this->default_properties['maxchars']) && isset($this->default_properties['size']))) {
                    var_dump($this, 'Properties must be "maxchars" and "size"');
                    return false;  
                }
            }
        }
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
                } else {
                    // User not found in the binded ugroup. Look for users that are either:
                    //  1. not anymore active
                    //  2. not member of the binded ugroup
                    if ($v = $this->getAdditionnalValue($i)) {
                        $bv[$i] = $v;
                    }
                }
            }
            return $bv;
        }
    }
    
    /**
     * Saves a bind in the database
     *
     * @return void
     */
    public function saveObject() {
        $dao = new Tracker_FormElement_Field_List_Bind_UsersDao();
        $dao->save($this->field->getId(), $this->getValueFunction());
        parent::saveObject();
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
        foreach ($changeset_value->getListValues() as $user_value) {
            $recipients[] = $user_value->getUsername();
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
        if ($value) {
            $values = explode(',', $value);
            foreach ($values as $v) {
                if (stripos($v, '!') !== false) {
                    //we check the string is an email
                    $rule = new Rule_Email();
                    if(!$rule->isValid($v)) {
                        //we check the string correspond to a username
                        if (!$this->userManager->getUserByIdentifier(substr($v, 1))) {
                            return false;
                        }
                    }
                }
            }
        }
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
