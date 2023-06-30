<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Project\REST\MinimalUserGroupRepresentation;
use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\Project\UGroupRetriever;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUgroupsValueDao;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\REST\FieldListBindUGroupValueRepresentation;

/**
 * @template-extends Tracker_FormElement_Field_List_Bind<Tracker_FormElement_Field_List_Bind_UgroupsValue>
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_List_Bind_Ugroups extends Tracker_FormElement_Field_List_Bind
{
    public const TYPE = 'ugroups';

    /**
     * @var UGroupRetriever
     */
    private $ugroup_retriever;

    /**
     * @var Tracker_FormElement_Field_List_Bind_UgroupsValue[]
     */
    private $values;

    /**
     * @var Tracker_FormElement_Field_List_Bind_UgroupsValue[]
     */
    private $values_indexed_by_ugroup_id;

    /**
     * @var BindUgroupsValueDao
     */
    protected $value_dao;

    public function __construct($field, $values, $default_values, $decorators, UGroupRetriever $ugroup_manager, BindUgroupsValueDao $value_dao)
    {
        parent::__construct($field, $default_values, $decorators);
        $this->values           = $values;
        $this->ugroup_retriever = $ugroup_manager;
        $this->value_dao        = $value_dao;

        $this->values_indexed_by_ugroup_id = [];
        foreach ($values as $value) {
            $this->values_indexed_by_ugroup_id[$value->getUGroupId()] = $value;
        }
    }

    /**
     * @return string
     */
    protected function format($value)
    {
        return $value->getLabel();
    }

    /**
     * @return string
     */
    public function formatCriteriaValue($value_id)
    {
        $hp = Codendi_HTMLPurifier::instance();
        return $hp->purify($this->format($this->getValue($value_id)), CODENDI_PURIFIER_CONVERT_HTML);
    }

    /**
     * @return string
     */
    public function formatMailCriteriaValue($value_id)
    {
        return $this->format($this->getValue($value_id));
    }

    /**
     * @param Tracker_FormElement_Field_List_Bind_UsersValue $value the value of the field
     *
     * @return string
     */
    public function formatChangesetValue($value)
    {
        return $value->fetchFormatted();
    }

    /**
     *
     * @param Tracker_FormElement_Field_List_Bind_UgroupsValue $value
     *
     * @return string
     */
    public function formatChangesetValueForCSV($value)
    {
        return $value->getUGroupName();
    }

    /**
     * @return array
     */
    public function getChangesetValues($changeset_id)
    {
        $values = [];
        foreach ($this->getValueDao()->searchChangesetValues($changeset_id, $this->field->id) as $row) {
            $values[] = $this->getValueFromRow($row);
        }
        return $values;
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_UgroupsValue|null
     */
    public function getValue($value_id)
    {
        $vs = $this->getAllValues();
        $v  = null;
        if (isset($vs[$value_id])) {
            $v = $vs[$value_id];
        }
        return $v;
    }

    /**
     * @return array
     */
    public function getAllValues()
    {
        return $this->values;
    }

    /**
     * Get a bindvalue by its row
     *
     * Duplicate of BindFactory::getUgroupsValueInstance
     *
     * @param array $row The row identifying the bindvalue
     *
     * @return Tracker_FormElement_Field_List_BindValue
     */
    public function getValueFromRow($row)
    {
        $ugroup = $this->ugroup_retriever->getUGroup($this->field->getTracker()->getProject(), $row['ugroup_id']);
        if ($ugroup) {
            $is_hidden = isset($row['is_hidden']) ? $row['is_hidden'] : false;

            return new Tracker_FormElement_Field_List_Bind_UgroupsValue($row['id'], $ugroup, $is_hidden);
        }
        return new Tracker_FormElement_Field_List_Bind_UgroupsValue(-1, new ProjectUGroup(['ugroup_id' => 0, 'name' => ""]), true);
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
    public function getBindtableSqlFragment()
    {
        return [
            'select'     => "tracker_field_list_bind_ugroups_value.id,
                             tracker_field_list_bind_ugroups_value.ugroup_id",
            'select_nb'  => 2,
            'from'       => 'tracker_field_list_bind_ugroups_value',
            'join_on_id' => 'tracker_field_list_bind_ugroups_value.id',
        ];
    }

    /**
     * Get the field data for artifact submission
     *
     * @param string  $submitted_value the field value (username(s))
     * @param bool $is_multiple if the value is multiple or not
     *
     * @return mixed the field data corresponding to the value for artifact submision (user_id)
     */
    public function getFieldData($submitted_value, $is_multiple)
    {
        $values = $this->getAllValues();
        if ($is_multiple) {
            $return           = [];
            $submitted_values = explode(',', $submitted_value);
            foreach ($values as $id => $value) {
                if (in_array($value->getUGroupName(), $submitted_values)) {
                    $return[] = $id;
                }
            }
            if (count($submitted_values) == count($return)) {
                return $return;
            } else {
                // if one value was not found, return null
                return null;
            }
        } else {
            foreach ($values as $id => $value) {
                if ($value->getUGroupName() == $submitted_value) {
                    return $id;
                }
            }
            // if not found, return null
            return null;
        }
    }

    /**
     * Get the "select" statement to retrieve field values
     * @see getQueryFrom
     */
    public function getQuerySelect(): string
    {
        $R2 = 'R2_' . $this->field->id;
        return "$R2.id AS " . $this->field->getQuerySelectName();
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
    public function getQueryFrom($changesetvalue_table = 'tracker_changeset_value_list')
    {
        $R1 = 'R1_' . $this->field->id;
        $R2 = 'R2_' . $this->field->id;
        $R3 = 'R3_' . $this->field->id;
        $R4 = 'R4_' . $this->field->id;
        return "LEFT JOIN ( tracker_changeset_value AS $R1
                    INNER JOIN $changesetvalue_table AS $R3 ON ($R3.changeset_value_id = $R1.id)
                    LEFT JOIN tracker_field_list_bind_ugroups_value AS $R2 ON ($R2.id = $R3.bindvalue_id AND $R2.field_id = " . $this->field->id . " )
                    INNER JOIN ugroup AS $R4 ON ($R4.ugroup_id = $R2.ugroup_id AND (
                        ($R4.ugroup_id > 100 AND $R4.group_id = " . $this->field->getTracker()->getProject()->getID() . " )
                        OR
                        ($R4.ugroup_id <= 100 AND $R4.group_id = 100))
                    )
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = " . $this->field->id . " )";
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby(): string
    {
        if (! $this->getField()->isUsed()) {
            return '';
        }
        $R2 = 'R2_' . $this->field->id;
        return "$R2.ugroup_id";
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby(): string
    {
        if (! $this->getField()->isUsed()) {
            return '';
        }
        $R2 = 'R2_' . $this->field->id;
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
    public function getQuerySelectAggregate($functions)
    {
        $R1       = 'R1_' . $this->field->id;
        $R2       = 'R2_' . $this->field->id;
        $R3       = 'R3_' . $this->field->id;
        $R4       = 'R4_' . $this->field->id;
        $same     = [];
        $separate = [];
        foreach ($functions as $f) {
            if (in_array($f, $this->field->getAggregateFunctions())) {
                if (substr($f, -5) === '_GRBY') {
                    $separate[] = [
                        'function' => $f,
                        'select'   => "$R4.name AS label, count(*) AS value",
                        'group_by' => "$R4.name",
                    ];
                } else {
                    $select = "$f($R4.name) AS `" . $this->field->name . "_$f`";
                    if ($this->field->isMultiple()) {
                        $separate[] = [
                            'function' => $f,
                            'select'   => $select,
                            'group_by' => null,
                        ];
                    } else {
                        $same[] = $select;
                    }
                }
            }
        }
        return [
            'same_query'       => implode(', ', $same),
            'separate_queries' => $separate,
        ];
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value)
    {
        return $this->format($this->getValue($value));
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset)
    {
        $value        = '';
        $values_array = [];
        if ($v = $changeset->getValue($this->field)) {
            $values = $v->getListValues();
            foreach ($values as $val) {
                $values_array[] = $val->getLabel();
            }
        }
        return implode(",", $values_array);
    }

    public function getDao()
    {
        //return new Tracker_FormElement_Field_List_Bind_UsersDao();
    }

    private function getOpenValueDao()
    {
        return new OpenListValueDao();
    }

    public function getValueDao()
    {
        return $this->value_dao;
    }

    /**
     * Allow the user to define the bind
     *
     * @param Field $field
     *
     * @return string html
     */
    public static function fetchAdminCreateForm($field)
    {
        return self::fetchSelectUgroups('formElement_data[bind][values][]', $field, []);
    }

    /**
     * Fetch the form to edit the formElement
     *
     * @return string html
     */
    public function fetchAdminEditForm()
    {
        $html  = '';
        $html .= '<h3>' . dgettext('tuleap-tracker', 'Bind to user groups') . '</h3>';
        $html .= self::fetchSelectUgroups('bind[values][]', $this->field, $this->values);

        //Select default values
        $html .= $this->getField()->getSelectDefaultValues($this->default_values);

        return $html;
    }

    protected static function fetchSelectUgroups($select_name, $field, $values)
    {
        $hp             = Codendi_HTMLPurifier::instance();
        $ugroup_manager = new UGroupManager();

        $ugroups = $ugroup_manager->getUGroups(
            $field->getTracker()->getProject(),
            [ProjectUGroup::NONE, ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::DOCUMENT_ADMIN, ProjectUGroup::DOCUMENT_TECH]
        );

        $html  = '';
        $html .= '<input type="hidden" name="' . $select_name . '" value="" />';
        $html .= '<select multiple="multiple" name="' . $select_name . '" size="' . min(9, max(5, count($ugroups))) . '">';

        $selected_ugroup_ids = array_map(
            static function ($value) {
                return self::getSelectedUgroupIds($value);
            },
            $values
        );
        foreach ($ugroups as $ugroup) {
            $selected = "";
            if (in_array($ugroup->getId(), $selected_ugroup_ids)) {
                $selected = 'selected="selected"';
            }
            $html .= '<option value="' . $ugroup->getId() . '" ' . $selected . '>';
            $html .= $hp->purify($ugroup->getTranslatedName());
            $html .= '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    private static function getSelectedUgroupIds($value)
    {
        if (! $value->isHidden()) {
            return $value->getUgroupId();
        }
    }

    /**
     * Process the request
     *
     * @param array $params the request parameters
     * @param bool  $no_redirect true if we do not have to redirect the user
     *
     * @return void
     */
    public function process($params, $no_redirect = false)
    {
        $value_dao = $this->getValueDao();
        foreach ($params as $key => $param_value) {
            switch ($key) {
                case 'values':
                    $wanted_ugroup_ids = array_filter($param_value);
                    $this->hideUnwantedValues($wanted_ugroup_ids);
                    foreach ($wanted_ugroup_ids as $ugroup_id) {
                        $value = $this->getValueByUGroupId($ugroup_id);
                        if ($value) {
                            if ($value->isHidden()) {
                                $value_dao->show($value->getId());
                            }
                        } else {
                            $value_dao->create($this->field->getId(), $ugroup_id, false);
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        return parent::process($params, $no_redirect);
    }

    private function hideUnwantedValues(array $wanted_ugroup_ids)
    {
        foreach ($this->getAllValues() as $value) {
            if (! in_array($value->getUGroupId(), $wanted_ugroup_ids)) {
                $this->getValueDao()->hide($value->getId());
            }
        }
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_UgroupsValue or null if no match
     */
    private function getValueByUGroupId($ugroup_id)
    {
        if (isset($this->values_indexed_by_ugroup_id[$ugroup_id])) {
            return $this->values_indexed_by_ugroup_id[$ugroup_id];
        }
    }

    /**
     * Saves a bind in the database
     *
     * @return void
     */
    public function saveObject()
    {
        foreach ($this->values as $value) {
            if ($id = $this->getValueDao()->create($this->field->getId(), $value->getUgroupId(), $value->isHidden())) {
                $value->setId($id);
            }
        }
        parent::saveObject();
    }

    /**
     * Transforms Bind into a SimpleXMLElement
     */
    public function exportToXml(
        SimpleXMLElement $root,
        &$xmlMapping,
        $project_export_context,
        UserXMLExporter $user_xml_exporter,
    ) {
        $items = $root->addChild('items');
        foreach ($this->values as $value) {
            $item = $items->addChild('item');
            $id   = $value->getXMLId();
            $item->addAttribute('ID', $id);
            $xmlMapping['values'][$id] = $value->getId();
            $item->addAttribute('label', $value->getUGroupName());
            $item->addAttribute('is_hidden', (int) $value->isHidden());
        }
        if ($this->default_values) {
            $default_child = $root->addChild('default_values');
            foreach ($this->default_values as $id => $nop) {
                if ($ref = array_search($id, $xmlMapping['values'])) {
                    $default_child->addChild('value')->addAttribute('REF', $ref);
                }
            }
        }
    }

    /**
     * Verifies the consistency of the imported Tracker
     *
     * @return true if Tracler is ok
     */
    public function testImport()
    {
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
    public function getBindValues($bindvalue_ids = null)
    {
        $values = $this->getAllValues();
        if ($bindvalue_ids === null) {
            return $values;
        } else {
            return $this->extractBindValuesByIds($values, $bindvalue_ids);
        }
    }

    /**
     * Give an extract of the bindvalues defined. The extract is based on $bindvalue_ids.
     * If the $bindvalue_ids is empty then return empty array
     *
     * @param array $bindvalue_ids The ids of BindValue to retrieve
     *
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    public function getBindValuesForIds(array $bindvalue_ids)
    {
        return $this->extractBindValuesByIds($this->getAllValues(), $bindvalue_ids);
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    private function extractBindValuesByIds(array $values, array $bindvalue_ids)
    {
        $list_of_bindvalues = [];
        foreach ($bindvalue_ids as $i) {
            if (isset($values[$i])) {
                $list_of_bindvalues[$i] = $values[$i];
            }
        }

        return $list_of_bindvalues;
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
        $recipients = [];
        foreach ($changeset_value->getListValues() as $ugroups_value) {
            if ($ugroups_value instanceof Tracker_FormElement_Field_List_Bind_UgroupsValue) {
                $recipients = array_merge($recipients, $ugroups_value->getMembersName());
            }
        }
        return $recipients;
    }

    /**
     * Say if this fields suport notifications
     *
     * @return bool
     */
    public function isNotificationsSupported()
    {
        return true;
    }

    public function isValid($value)
    {
        if (empty($value)) {
            return true;
        }
        $separated_values = explode(',', $value);
        foreach ($separated_values as $separated_value) {
            if (strpos($separated_value, '!') === false) {
                continue;
            }
            $user_group_name = substr($separated_value, 1);

            $project = $this->getField()->getTracker()->getProject();
            $ugroup  = $this->ugroup_retriever->getUGroupByName($project, $user_group_name);

            if ($ugroup === null) {
                return false;
            }
        }
        return true;
    }

    /**
     * @see Tracker_FormElement_Field_Shareable
     */
    public function fixOriginalValueIds(array $value_mapping)
    {
        // Nothing to do: user value ids stay the same accross projects.
    }

    protected function getRESTBindingList()
    {
        // returns empty array as ugroups are already listed in 'values'
        return [];
    }

    public function getNumericValues(Tracker_Artifact_ChangesetValue $changeset_value)
    {
        // returns an empty array as it doesn't make sense with Ugroups
        return [];
    }

    public function getType()
    {
        return self::TYPE;
    }

    protected function getRESTBindValue(Tracker_FormElement_Field_List_Value $value)
    {
        $project = $value->getProject();
        if (! $project) {
            throw new Project_NotFoundException();
        }
        $ugroup = $this->ugroup_retriever->getUGroup($project, $value->getUgroupId());
        if (! $ugroup) {
            throw new \Tuleap\Project\UGroups\InvalidUGroupException($value->getUgroupId());
        }
        $ugroup_representation = new MinimalUserGroupRepresentation((int) $project->getID(), $ugroup);

        $representation = new FieldListBindUGroupValueRepresentation();
        $representation->build($value, $ugroup_representation);
        return $representation;
    }

    public function getDefaultRESTValues()
    {
        $bind_values = $this->getBindValues(array_keys($this->getDefaultValues()));

        $project_id = $this->getField()->getTracker()->getProject()->getID();

        $rest_array = [];
        foreach ($bind_values as $value) {
            $representation = new MinimalUserGroupRepresentation((int) $project_id, $value->getUgroup());
            $rest_array[]   = $representation;
        }
        return $rest_array;
    }

    public function getFieldDataFromRESTObject(array $rest_data, Tracker_FormElement_Field_List $field)
    {
        $project = $field->getTracker()->getProject();

        $identifier = null;

        if (isset($rest_data['id'])) {
            $value = UserGroupRepresentation::getProjectAndUserGroupFromRESTId($rest_data['id']);
            $id    = $value['user_group_id'];

            $bind_value = $this->getValueByUGroupId($id);
            if ($bind_value) {
                return Tracker_FormElement_Field_OpenList::BIND_PREFIX . $bind_value->getId();
            }

            $user_group = $this->ugroup_retriever->getUGroup($project, $id);
            if (! $user_group) {
                throw new Tracker_FormElement_InvalidFieldValueException('User Group with ID ' . $id . ' does not exist for field ID ' . $field->getId());
            }

            if (! $bind_value) {
                $identifier = $user_group->getName();
            }
        } elseif (isset($rest_data['short_name'])) {
            $name       = (string) $rest_data['short_name'];
            $user_group = $this->ugroup_retriever->getUGroupByName($project, $name);

            if (! $user_group) {
                throw new Tracker_FormElement_InvalidFieldValueException('User Group with short_name ' . $name . ' does not exist for field ID ' . $field->getId());
            }

            $identifier = $name;
        } else {
            throw new Tracker_FormElement_InvalidFieldValueException('OpenList static fields values should be passed as an object with at least one of the properties "id" or "short_name"');
        }

        if ($identifier !== null) {
            $row = $this->getOpenValueDao()->searchByExactLabel($field->getId(), $identifier)->getRow();
            if ($row) {
                return Tracker_FormElement_Field_OpenList::OPEN_PREFIX . $row['id'];
            }
        }

        return Tracker_FormElement_Field_OpenList::NEW_VALUE_PREFIX . $identifier;
    }

    public function getFullRESTValue(Tracker_FormElement_Field_List_Value $value)
    {
        $ugroup_manager = new UGroupManager();
        $project        = $this->getField()->getTracker()->getProject();
        $user_group     = $ugroup_manager->getUGroupByName($project, $value->getLabel());
        if (! $user_group) {
            throw new \Exception("Unable to find the user group " . $value->getLabel());
        }

        return new MinimalUserGroupRepresentation($project->getID(), $user_group);
    }

    public function getFieldDataFromRESTValue($rest_data): int
    {
        $value      = UserGroupRepresentation::getProjectAndUserGroupFromRESTId($rest_data);
        $ugroup_id  = $value['user_group_id'];
        $bind_value = $this->getValueByUGroupId($ugroup_id);

        if ($bind_value) {
            return (int) $bind_value->getId();
        }

        return 0;
    }

    public function accept(BindVisitor $visitor, BindParameters $parameters)
    {
        return $visitor->visitListBindUgroups($this, $parameters);
    }

    /**
     * @param int $bindvalue_id
     * @return Tracker_FormElement_Field_List_BindValue
     */
    public function getBindValueById($bindvalue_id)
    {
        $row = $this->value_dao->searchById($bindvalue_id);

        if (! $row) {
            return new Tracker_FormElement_Field_List_Bind_UgroupsValue(-1, new ProjectUGroup(['ugroup_id' => 0, 'name' => ""]), true);
        }

        return $this->getValueFromRow($row);
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    public function getAllValuesWithActiveUsersOnly(): array
    {
        return [];
    }
}
