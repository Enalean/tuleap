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

require_once 'common/layout/ColorHelper.class.php';

abstract class Tracker_FormElement_Field_List_Bind implements Tracker_FormElement_Field_Shareable {

    /**
     * @var Tracker_FormElement_Field_List_Bind_DefaultvalueDao
     */
    protected $default_value_dao;

    protected $default_values;
    protected $decorators;
    protected $field;
    
    public function __construct($field, $default_values, $decorators) {
        $this->field          = $field;
        $this->default_values = $default_values;
        $this->decorators     = $decorators;
    }
    
    /**
     * Get the default values definition of the bind
     *
     * @return array (123 => 1, 234 => 1, 345 => 1)
     */
    public function getDefaultValues() {
        return $this->default_values;
    }
    
    public function getDecorators() {
        return $this->decorators;
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    public abstract function getAllValues();

    /**
     * Get available values of this field for SOAP usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    public function getSoapAvailableValues() {
        $soap_values = array();
        foreach($this->getAllValues() as $value) {
            $soap_values[] = $this->getSoapBindValue($value);
        }
        return $soap_values;
    }

    private function getSoapBindValue($value) {
        return array(
            'bind_value_id'    => $value->getId(),
            'bind_value_label' => $value->getSoapValue()
        );
    }

    public function getSoapBindingProperties() {
        $bind_factory = new Tracker_FormElement_Field_List_BindFactory();
        $bind_type = $bind_factory->getType($this);
        return array(
            'bind_type' => $bind_type,
            'bind_list' => $this->getSoapBindingList()
        );
    }

    /**
     *
     * @return array
     */
    protected abstract function getSoapBindingList();
    
    /**
     * Get the field data for artifact submission
     *
     * @param string $soap_value  of soap field value
     * @param bool   $is_multiple if the soap value is multiple or not
     *
     * @return mixed the field data corresponding to the soap_value for artifact submision
     */
    public abstract function getFieldData($soap_value, $is_multiple);
    /**
     * @return array
     */
    public abstract function getValue($value_id);
    /**
     * @return array
     */
    public abstract function getChangesetValues($changeset_id);
    
    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public abstract function fetchRawValue($value);
    
    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public abstract function fetchRawValueFromChangeset($changeset);
    
    /**
     * @return string
     */
    public abstract function formatCriteriaValue($value_id);

    /**
     * @return string
     */
    public abstract function formatMailCriteriaValue($value_id);

    /**
     * @return string
     */
    public abstract function formatChangesetValue($value);

    /**
     * @return string
     */
    public function formatCardValue($value, Tracker_CardDisplayPreferences $display_preferences) {
        return $this->formatChangesetValue($value);
    }
    
    /**
     * @return string
     */
    public abstract function formatChangesetValueForCSV($value);
    
    /**
     * @return string
     */
    public function formatArtifactValue($value_id) {
        if ($value_id != 100) {
            return $this->formatCriteriaValue($value_id);
        } else {
            return '-';
        }
    }

    /**
     * @return string
     */
    public function formatMailArtifactvalue ($value_id) {
        return $this->formatMailCriteriaValue($value_id);
    }
    
    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve 
     * the last changeset of all artifacts.
     * @param array $criteria_value array of criteria_value (which are array)
     * @return string
     */
    public abstract function getCriteriaFrom($criteria_value);
    
    /**
     * Get the "where" statement to allow search with this field
     * @param array $criteria_value array of id => criteria_value (which are array)
     * @return string
     * @see getCriteriaFrom
     */
    public abstract function getCriteriaWhere($criteria);

    /**
     * Get the "select" statement to retrieve field values
     * @return string
     * @see getQueryFrom
     */
    public abstract function getQuerySelect();
    
    /**
     * Get the "select" statement to retrieve field values with their decorator if they exist
     * @return string
     * @see getQuerySelect
     */
    public function getQuerySelectWithDecorator() {
        return $this->getQuerySelect();
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
    public abstract function getQueryFrom($changesetvalue_table = 'tracker_changeset_value_list');
    
	/**
     * Get the "from" statement to retrieve field values with their decorator if they exist
     * @return string
     * @see getQueryFrom
     */
    public function getQueryFromWithDecorator($changesetvalue_table = 'tracker_changeset_value_list') {
        return $this->getQueryFrom($changesetvalue_table);
    }
    
    /**
     * Get the field
     *
     * @return Tracker_FormElement_Field_List
     */
    public function getField() {
        return $this->field;
    }
    
    /**
     * Get a bindvalue by its row
     *
     * @param array $row The row identifying the bindvalue
     *
     * @return Tracker_FormElement_Field_List_BindValue
     */
    public abstract function getValueFromRow($row);
    
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
    public abstract function getBindtableSqlFragment();
    
    /**
     * Get the "order by" statement to retrieve field values
     */
    public abstract function getQueryOrderby();
    
    /**
     * Get the "group by" statement to retrieve field values
     */
    public abstract function getQueryGroupby();
    
    public function fetchDecoratorsAsJavascript() {
        $html = '';
        if (is_array($this->decorators) && count($this->decorators)) {
            $html .= '<script type="text/javascript">'.PHP_EOL;
            $html .= 'codendi.tracker.decorator.decorators['. $this->field->id .'] = [];'. PHP_EOL;
            foreach($this->decorators as $d) {
                $html .= 'codendi.tracker.decorator.decorators['. $this->field->id .']['. $d->value_id .'] = '. $d->toJSON() .';'. PHP_EOL;
            }
            $html .= '</script>';
        }
        return $html;
    }
    
    public function getSelectOptionInlineStyle($value_id) {
        if (count($this->decorators)) {
            if (isset($this->decorators[$value_id])) {
                return $this->decorators[$value_id]->decorateSelectOption();
            } else {
                return 'padding-left: 16px;';
            }
        } else {
            return '';
        }
    }
    
    public abstract function getDao();
    public abstract function getValueDao();
    
    /**
     * Fetch the form to edit the formElement
     *
     * @return string html
     */
    public abstract function fetchAdminEditForm();
    
    /**
     * Process the request
     *
     * @param array $params the request parameters
     * @param bool  $no_redirect true if we do not have to redirect the user
     *
     * @return bool true if we want to redirect
     */
    public function process($params, $no_redirect = false, $redirect = false) {
        if (isset($params['decorator'])) {
            foreach ($params['decorator'] as $value_id => $hexacolor) {
                if ($hexacolor) {
                    Tracker_FormElement_Field_List_BindDecorator::save($this->field->getId(), $value_id, $hexacolor);
                } else {
                    Tracker_FormElement_Field_List_BindDecorator::delete($this->field->getId(), $value_id);
                }
            }
            $redirect = true;
        }
        
        $default = array();
        if (isset($params['default'])) {
            $default = $params['default'];
        }
        $this->getDefaultValueDao()->save($this->field->getId(), $default);
        $redirect = true;
        
        if (!$no_redirect && $redirect) {
            $GLOBALS['Response']->redirect('?'. http_build_query(array(
                    'tracker'            => $this->field->getTracker()->id,
                    'func'               => 'admin-formElements',
            )));
        }
        return $redirect;
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_DefaultvalueDao
     */
    protected function getDefaultValueDao() {
        if (!$this->default_value_dao) {
            $this->default_value_dao = new Tracker_FormElement_Field_List_Bind_DefaultvalueDao();
        }
        return $this->default_value_dao;
    }

    public function setDefaultValueDao(Tracker_FormElement_Field_List_Bind_DefaultvalueDao $dao) {
        $this->default_value_dao = $dao;
    }

    /**
     * Allow the user to define the bind
     *
     * @param Field $field
     *
     * @return string html
     */
    public static abstract function fetchAdminCreateForm($field);
    
    /**
     * Transforms Bind into a SimpleXMLElement
     * 
     * @param SimpleXMLElement $root        the node to which the Bind is attached (passed by reference)
     * @param array            &$xmlMapping the correspondance between real ids and XML IDs
     * @param string           $fieldID     XML ID of the binded field
     */
    public abstract function exportToXml(SimpleXMLElement $root, &$xmlMapping, $fieldID);
    
    /**
     * Give an extract of the bindvalues defined. The extract is based on $bindvalue_ids. 
     * If the $bindvalue_ids is null then return all values.
     *
     * @param array $bindvalue_ids The ids of BindValue to retrieve
     *
     * @Return array the BindValue(s)
     */
    public abstract function getBindValues($bindvalue_ids = null);
    
    /**
     * Get the html to select a default value
     *
     * @return string html
     */
    protected function getSelectDefaultValues() {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        
        //Select default values
        $html .= '<p>';
        $html .= '<strong>'. $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','select_default_value'). '</strong><br />';
        $html .= '<select name="bind[default][]" size="7" multiple="multiple">';
        foreach ($this->getAllValues() as $v) {
            $selected = isset($this->default_values[$v->getId()]) ? 'selected="selected"' : '';
            $html .= '<option value="'. $v->getId() .'" '. $selected .'>'. $hp->purify($v->getLabel(), CODENDI_PURIFIER_CONVERT_HTML)  .'</option>';
        }
        $html .= '</select>';
        $html .= '</p>';
        
        return $html;
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
    public abstract function getQuerySelectAggregate($functions);
    
    /**
     * Saves a bind in the database
     *
     * @return void
     */
    public function saveObject() {
        if (is_array($this->default_values)) {
            $t = array();
            foreach ($this->default_values as $value) {
                $t[$value->getId()] = $value;
            }
            $this->default_values = $t;
            
            if (count($this->default_values)) {
                $this->getDefaultValueDao()->save($this->field->getId(), array_keys($this->default_values));
            }
        }
        
        if (is_array($this->decorators) && !empty($this->decorators)) {
            $values = $this->getBindValues();
            foreach ( $this->decorators as $decorator) {
                $hexacolor = ColorHelper::RGBtoHexa($decorator->r, $decorator->g, $decorator->b);
                Tracker_FormElement_Field_List_BindDecorator::save($this->field->getId(), $values[$decorator->value_id]->getId(), $hexacolor);
            }
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
        return array();
    }
    
    /**
     * Say if this fields suport notifications
     *
     * @return bool
     */
    public function isNotificationsSupported() {
        return false;
    }
    
    /**
     * Retrieve all values which match the keyword
     *
     * @param string $keyword The keyword to search
     * @param int    $limit   The max number of values to return. Default is 10
     *
     * @return array
     */
    public function getValuesByKeyword($keyword, $limit = 10) {
        $values = array();
        //pretty slow, but we do not have a better way to filter a value function
        foreach($this->getAllValues($keyword) as $v) {
            if (false !== stristr($v->getLabel(), $keyword)) {
                $values[] = $v;
                if ( --$limit === 0 ) {
                    break;
                }
            }
        }
        return $values;
    }
}
?>
