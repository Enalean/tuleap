<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitable;
use Tuleap\Tracker\REST\FieldValueRepresentation;

/**
 * @template ListValueBinding of Tracker_FormElement_Field_List_Value
 */
abstract class Tracker_FormElement_Field_List_Bind implements
    Tracker_FormElement_Field_Shareable,
    Tracker_IProvideJsonFormatOfMyself,
    BindVisitable
{
    public const REST_ID_KEY    = 'bind_value_id';
    public const REST_LABEL_KEY = 'bind_value_label';
    public const REST_TYPE_KEY  = 'bind_type';
    public const REST_LIST_KEY  = 'bind_list';

    public const NONE_VALUE = 100;

    /**
     * @var Tracker_FormElement_Field_List_Bind_DefaultvalueDao
     */
    protected $default_value_dao;

    protected $default_values;
    /**
     * @var Tracker_FormElement_Field_List_BindDecorator[]
     */
    protected $decorators;

    /** @var Tracker_FormElement_Field */
    protected $field;

    public function __construct($field, $default_values, $decorators)
    {
        $this->field          = $field;
        $this->default_values = $default_values;
        $this->decorators     = $decorators;
    }

    /**
     * Get the default values definition of the bind
     *
     * @return array (123 => 1, 234 => 1, 345 => 1)
     */
    public function getDefaultValues()
    {
        return $this->default_values;
    }

    public function getDefaultRESTValues()
    {
        $bind_values = $this->getBindValues(array_keys($this->getDefaultValues()));

        $rest_array = array();
        foreach ($bind_values as $value) {
            $representation = new FieldValueRepresentation();
            $representation->build(array(
                Tracker_FormElement_Field_List_Bind::REST_ID_KEY    => $value->getId(),
                Tracker_FormElement_Field_List_Bind::REST_LABEL_KEY => $value->getAPIValue()
            ));
            $rest_array[] = $representation;
        }
        return $rest_array;
    }

    /**
     * @return Tracker_FormElement_Field_List_BindDecorator[]
     */
    public function getDecorators()
    {
        return $this->decorators;
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    abstract public function getAllValues();

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    abstract public function getAllValuesWithActiveUsersOnly(): array;

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    public function getAllVisibleValues()
    {
        $values = $this->getAllValues();
        foreach ($values as $key => $value) {
            if ($value->isHidden()) {
                unset($values[$key]);
            }
        }

        return $values;
    }

    /**
     * @return bool
     */
    public function isExistingValue($value_id)
    {
        return array_key_exists($value_id, $this->getAllValues());
    }

    public function fetchFormattedForJson()
    {
        $values = array();
        foreach ($this->getAllValues() as $value) {
            $values[] = $value->fetchFormattedForJson();
        }
        return $values;
    }

    public function getRESTBindingProperties()
    {
        $bind_factory = new Tracker_FormElement_Field_List_BindFactory();
        $bind_type = $bind_factory->getType($this);
        return array(
            self::REST_TYPE_KEY => $bind_type,
            self::REST_LIST_KEY => $this->getRESTBindingList()
        );
    }

    /**
     *
     * @return array
     */
    abstract protected function getRESTBindingList();

    /**
     * Get the field data for artifact submission
     *
     * @param string $submitted_value
     * @param bool   $is_multiple     if the value is multiple or not
     *
     * @return mixed the field data corresponding to the value for artifact submision
     */
    abstract public function getFieldData($submitted_value, $is_multiple);
    /**
     * @return array|Tracker_FormElement_Field_List_BindValue|null
     * @throws Tracker_FormElement_InvalidFieldValueException
     */
    abstract public function getValue($value_id);
    /**
     * @return array
     */
    abstract public function getChangesetValues($changeset_id);

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    abstract public function fetchRawValue($value);

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    abstract public function fetchRawValueFromChangeset($changeset);

    /**
     * @return string
     */
    abstract public function formatCriteriaValue($value_id);

    /**
     * @return string
     */
    abstract public function formatMailCriteriaValue($value_id);

    /**
     * @return string
     */
    abstract public function formatChangesetValue($value);

    /**
     * @return string
     */
    public function formatCardValue($value, Tracker_CardDisplayPreferences $display_preferences)
    {
        return $this->formatChangesetValue($value);
    }


    /**
     * @return string
     */
    abstract public function formatChangesetValueForCSV($value);

    /**
     * Formatted changeset are considered without link by default.
     * Classes that format with a link (i.e. userBind) must override this.
     * @return string
     */
    public function formatChangesetValueWithoutLink($value)
    {
        return $this->formatChangesetValue($value);
    }

    /**
     * @return string
     */
    public function formatArtifactValue($value_id)
    {
        if ($value_id && $value_id != self::NONE_VALUE) {
            return $this->formatCriteriaValue($value_id);
        } else {
            return '-';
        }
    }

    /**
     * @return string
     */
    public function formatMailArtifactvalue($value_id)
    {
        return $this->formatMailCriteriaValue($value_id);
    }

    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve
     * the last changeset of all artifacts.
     * @param array $criteria_value array of criteria_value (which are array)
     * @return string
     */
    public function getCriteriaFrom($criteria_value)
    {
        //Only filter query if criteria is valuated
        if ($criteria_value) {
            $a = 'A_' . $this->field->id;
            $b = 'B_' . $this->field->id;
            if ($this->isSearchingNone($criteria_value)) {
                return " LEFT JOIN (
                    tracker_changeset_value AS $a
                    INNER JOIN tracker_changeset_value_list AS $b ON (
                        $b.changeset_value_id = $a.id
                    )
                ) ON ($a.changeset_id = c.id
                    AND $a.field_id = " . $this->field->id . "
                )";
            }

            return " INNER JOIN tracker_changeset_value AS $a
                     ON ($a.changeset_id = c.id
                         AND $a.field_id = " . $this->field->id . "
                     )
                     INNER JOIN tracker_changeset_value_list AS $b ON (
                        $b.changeset_value_id = $a.id
                     ) ";
        }
        return '';
    }

    /**
     * Get the "where" statement to allow search with this field
     * @param array $criteria_value array of id => criteria_value (which are array)
     * @return string
     * @see getCriteriaFrom
     */
    public function getCriteriaWhere($criteria_value)
    {
        //Only filter query if criteria is valuated
        if ($criteria_value) {
            $a = 'A_' . $this->field->id;
            $b = 'B_' . $this->field->id;

            $data_access = CodendiDataAccess::instance();

            if ($this->isSearchingNone($criteria_value)) {
                $values_id = $data_access->escapeIntImplode(array_values($criteria_value));

                return " $b.bindvalue_id IN (" . $values_id . ") OR $b.bindvalue_id IS NULL ";
            }

            $ids_to_search = $data_access->escapeIntImplode($this->getIdsToSearch($criteria_value));

            if ($ids_to_search === '') {
                return '';
            }

            return " $b.bindvalue_id IN(" . $ids_to_search . ") ";
        }
        return '';
    }

    protected function getIdsToSearch($criteria_value)
    {
        return array_intersect(
            array_values($criteria_value),
            array_merge(
                array(100),
                array_keys($this->getAllValues())
            )
        );
    }

    /**
     * Get the "select" statement to retrieve field values
     * @return string
     * @see getQueryFrom
     */
    abstract public function getQuerySelect();

    /**
     * Get the "select" statement to retrieve field values with their decorator if they exist
     * @return string
     * @see getQuerySelect
     */
    public function getQuerySelectWithDecorator()
    {
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
    abstract public function getQueryFrom($changesetvalue_table = 'tracker_changeset_value_list');

    /**
     * Get the "from" statement to retrieve field values with their decorator if they exist
     * @return string
     * @see getQueryFrom
     */
    public function getQueryFromWithDecorator($changesetvalue_table = 'tracker_changeset_value_list')
    {
        return $this->getQueryFrom($changesetvalue_table);
    }

    /**
     * Get the field
     *
     * @return Tracker_FormElement_Field_List
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Get a bindvalue by its row
     *
     * @param array $row The row identifying the bindvalue
     *
     * @return Tracker_FormElement_Field_List_BindValue
     */
    abstract public function getValueFromRow($row);

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
    abstract public function getBindtableSqlFragment();

    /**
     * Get the "order by" statement to retrieve field values
     */
    abstract public function getQueryOrderby();

    /**
     * Get the "group by" statement to retrieve field values
     */
    abstract public function getQueryGroupby();

    public function fetchDecoratorsAsJavascript()
    {
        $html = '';
        if (is_array($this->decorators) && count($this->decorators)) {
            $html .= '<script type="text/javascript">' . PHP_EOL;
            $html .= 'codendi.tracker.decorator.decorators[' . $this->field->id . '] = [];' . PHP_EOL;
            foreach ($this->decorators as $d) {
                $html .= 'codendi.tracker.decorator.decorators[' . $this->field->id . '][' . $d->value_id . '] = ' . $d->toJSON() . ';' . PHP_EOL;
            }
            $html .= '</script>';
        }
        return $html;
    }

    public function getSelectOptionStyles($value_id)
    {
        $default_styles = ['classes' => '', 'inline-styles' => ''];

        if (count($this->decorators)) {
            if (isset($this->decorators[$value_id])) {
                return $this->decorators[$value_id]->decorateSelectOptionWithStyles();
            } else {
                $default_styles['classes'] = 'select-option-not-colored';

                return $default_styles;
            }
        } else {
            return $default_styles;
        }
    }

    abstract public function getDao();
    abstract public function getValueDao();

    /**
     * Fetch the form to edit the formElement
     *
     * @return string html
     */
    abstract public function fetchAdminEditForm();

    /**
     * Process the request
     *
     * @param array $params the request parameters
     * @param bool  $no_redirect true if we do not have to redirect the user
     *
     * @return bool true if we want to redirect
     */
    public function process($params, $no_redirect = false)
    {
        if (isset($params['decorator'])) {
            foreach ($params['decorator'] as $value_id => $hexacolor) {
                if ($hexacolor) {
                    Tracker_FormElement_Field_List_BindDecorator::update($this->field->getId(), $value_id, $hexacolor);
                } else {
                    Tracker_FormElement_Field_List_BindDecorator::delete($this->field->getId(), $value_id);
                }
            }
        }

        $default = $this->extractDefaultValues($params);

        $this->getDefaultValueDao()->save($this->field->getId(), $default);

        if (!$no_redirect) {
            $tracker = $this->field->getTracker();
            if ($tracker === null) {
                $GLOBALS['Response']->redirect('/');
                return true;
            }
            $GLOBALS['Response']->redirect('?' . http_build_query(array(
                    'tracker'            => $tracker->getId(),
                    'func'               => 'admin-formElements',
            )));
        }
        return true;
    }

    private function extractDefaultValues(array $params)
    {
        $default = array();
        if (isset($params['default'])) {
            if (isset($params['default'][0])) {
                $bind_default = str_replace('b', '', $params['default'][0]);
                $bind_default = explode(',', $bind_default);
                $bind_default = $this->filterDefaultValues($bind_default);

                return $bind_default;
            }

            $default = $params['default'];
        }

        return $default;
    }

    /**
     * @return array
     */
    protected function filterDefaultValues(array $bind_default)
    {
        return $bind_default;
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_DefaultvalueDao
     */
    protected function getDefaultValueDao()
    {
        if (!$this->default_value_dao) {
            $this->default_value_dao = new Tracker_FormElement_Field_List_Bind_DefaultvalueDao();
        }
        return $this->default_value_dao;
    }

    public function setDefaultValueDao(Tracker_FormElement_Field_List_Bind_DefaultvalueDao $dao)
    {
        $this->default_value_dao = $dao;
    }

    /**
     * Allow the user to define the bind
     *
     * @param Field $field
     *
     * @return string html
     */
    abstract public static function fetchAdminCreateForm($field);

    /**
     * Transforms Bind into a SimpleXMLElement
     */
    abstract public function exportToXml(
        SimpleXMLElement $root,
        &$xmlMapping,
        $project_export_context,
        UserXMLExporter $user_xml_exporter
    );

    /**
     * Give an extract of the bindvalues defined. The extract is based on $bindvalue_ids.
     * If the $bindvalue_ids is null then return all values.
     *
     * @param array $bindvalue_ids The ids of BindValue to retrieve
     *
     * @Return array the BindValue(s)
     */
    abstract public function getBindValues($bindvalue_ids = null);

    /**
     * Give an extract of the bindvalues defined. The extract is based on $bindvalue_ids.
     * If the $bindvalue_ids is empty then return empty array
     *
     * @param array $bindvalue_ids The ids of BindValue to retrieve
     *
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    abstract public function getBindValuesForIds(array $bindvalue_ids);

    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @param int $bindvalue_id
     * @return Tracker_FormElement_Field_List_BindValue
     */
    abstract public function getBindValueById($bindvalue_id);

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
    abstract public function getQuerySelectAggregate($functions);

    /**
     * Saves a bind in the database
     *
     * @return void
     */
    public function saveObject()
    {
        if (is_array($this->default_values)) {
            $t = [];
            foreach ($this->default_values as $value) {
                $t[$value->getId()] = $value;
            }
            $this->default_values = $t;

            if (count($this->default_values)) {
                $this->getDefaultValueDao()->save($this->field->getId(), array_keys($this->default_values));
            }
        }

        if (is_array($this->decorators) && ! empty($this->decorators)) {
            $values = $this->getBindValues();
            foreach ($this->decorators as $decorator) {
                if (! $decorator->isUsingOldPalette()) {
                    $color = $decorator->tlp_color_name;
                } else {
                    $color = ColorHelper::RGBtoHexa($decorator->r, $decorator->g, $decorator->b);
                }

                Tracker_FormElement_Field_List_BindDecorator::save(
                    $this->field->getId(),
                    $values[$decorator->value_id]->getId(),
                    $color
                );
            }
        }
    }

    /**
     * Get a recipients list for notifications. This is filled by users fields for example.
     *
     * @param Tracker_Artifact_ChangesetValue_List $changeset_value The changeset
     *
     * @return string[]
     */
    public function getRecipients(Tracker_Artifact_ChangesetValue_List $changeset_value)
    {
        return array();
    }

    /**
     * Say if this fields suport notifications
     *
     * @return bool
     */
    public function isNotificationsSupported()
    {
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
    public function getValuesByKeyword($keyword, $limit = 10)
    {
        $values = array();
        //pretty slow, but we do not have a better way to filter a value function
        foreach ($this->getAllValues($keyword) as $v) {
            if (false !== stripos($v->getLabel(), $keyword)) {
                $values[] = $v;
                if (--$limit === 0) {
                    break;
                }
            }
        }
        return $values;
    }

    /**
     * Retrieve all the numeric values of the binded values
     *
     * @return array of numeric bind values
     */
    abstract public function getNumericValues(Tracker_Artifact_ChangesetValue $changeset_value);

    /**
     * @psalm-param ListValueBinding $value
     */
    protected function getRESTBindValue(Tracker_FormElement_Field_List_Value $value)
    {
        $representation = new FieldValueRepresentation();
        $values = array(
            self::REST_ID_KEY    => $value->getId(),
            self::REST_LABEL_KEY => $value->getAPIValue()
        );
        $representation->build($values);
        return $representation;
    }

    public function getRESTAvailableValues()
    {
        $rest_values = array();
        foreach ($this->getAllValues() as $value) {
            $rest_values[] = $this->getRESTBindValue($value);
        }
        return $rest_values;
    }

    abstract public function getFullRESTValue(Tracker_FormElement_Field_List_Value $value);

    abstract public function getFieldDataFromRESTObject(array $rest_data, Tracker_FormElement_Field_List $field);

    public function getFieldDataFromRESTValue($value): int
    {
        return (int) $value;
    }

    public function addValue($new_value)
    {
        return;
    }

    protected function isSearchingNone($criteria_value)
    {
        if (empty($criteria_value)) {
            return true;
        }

        if (is_array($criteria_value)
            && in_array(Tracker_FormElement_Field_List::NONE_VALUE, $criteria_value)
        ) {
            return true;
        }

        return false;
    }
}
