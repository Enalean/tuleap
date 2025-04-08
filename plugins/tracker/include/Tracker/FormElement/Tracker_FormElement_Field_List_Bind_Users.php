<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindListUserValueGetter;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUsersDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\PlatformUsersGetterSingleton;
use Tuleap\Tracker\Import\Spotter;
use Tuleap\Tracker\REST\FieldListBindUserValueRepresentation;
use Tuleap\Tracker\REST\FormElement\UserListValueRepresentation;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use Tuleap\User\REST\UserRepresentation;

class Tracker_FormElement_Field_List_Bind_Users extends Tracker_FormElement_Field_List_Bind //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const TYPE = 'users';

    public const REGISTERED_USERS_UGROUP_NAME = 'ugroup_2';

    public const REST_BINDING_LIST_ID    = 'ugroup_id';
    public const REST_BINDING_LIST_LABEL = 'name';

    /** @var UserManager */
    protected $userManager;
    protected $value_function = [];
    protected $values;


    public function __construct(public \Tuleap\DB\DatabaseUUIDV7Factory $uuid_factory, $field, $value_function, $default_values, $decorators)
    {
        parent::__construct($uuid_factory, $field, $default_values, $decorators);

        if (! empty($value_function)) {
            $this->value_function = explode(',', $value_function);
        }
        $this->userManager = UserManager::instance();
    }

    /**
     * @return bool
     */
    public function isExistingValue($value_id)
    {
        $import_spotter = Spotter::instance();
        if ($import_spotter->isImportRunning()) {
            $user = $this->getUserManager()->getUserById($value_id);
            return $user !== null;
        }
        return parent::isExistingValue($value_id);
    }

    /**
     * @return array of value_functions
     */
    public function getValueFunction()
    {
        return $this->value_function;
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
        if ($value->getId() == 100) {
            return '';
        } else {
            return $value->fetchFormatted();
        }
    }

    /**
     * @return string
     */
    public function formatCardValue($value, Tracker_CardDisplayPreferences $display_preferences)
    {
        return $value->fetchCard($display_preferences);
    }

    /**
     * @return string
     */
    public function formatChangesetValueForCSV($value)
    {
        if ($value->getId() == 100) {
            return '';  // NULL value for CSV
        } else {
            return $value->getUsername();
        }
    }

    /**
     * @return string
     */
    public function formatChangesetValueWithoutLink($value)
    {
        return Codendi_HTMLPurifier::instance()->purify($value->getLabel());
    }

    /**
     * @return array
     */
    public function getChangesetValues($changeset_id)
    {
        $uh     = UserHelper::instance();
        $values = [];
        foreach ($this->getDao()->searchChangesetValues($changeset_id, $this->field->id, $uh->getDisplayNameSQLQuery(), $uh->getDisplayNameSQLOrder()) as $row) {
            $values[] =  new Tracker_FormElement_Field_List_Bind_UsersValue($this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes()), $row['id'], $row['user_name'], $row['full_name']);
        }
        return $values;
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_UsersValue | null
     */
    public function getValue($value_id)
    {
        if ($value_id == 100) {
            $v = new Tracker_FormElement_Field_List_Bind_UsersValue($this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes()), 0);
        } else {
            $vs = $this->getAllValues();
            $v  = null;
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
     * Get available values of this field for REST usage
     * Fields like int, float, date, string don't have available values
     *
     * @return array | null The values or null if there are no specific available values
     */
    public function getRESTAvailableValues()
    {
        $ugroups = [];

        foreach ($this->value_function as $ugroup) {
            if ($ugroup != self::REGISTERED_USERS_UGROUP_NAME && $ugroup != '') {
                $ugroups[] = $ugroup;
            }
        }

        $rest_values = [];
        if (! empty($ugroups)) {
            foreach ($this->getAllValuesByUGroupList($ugroups) as $value) {
                $rest_values[] = $this->getRESTBindValue($value);
            }
        }
        return $rest_values;
    }

    /**
     * Get the list of of ugroups used in this field     *
     *
     * @return array the list of all ugroups with id and name
     */
    protected function getRESTBindingList()
    {
        $ugroups = [];
        foreach ($this->value_function as $ugroup) {
            if ($ugroup) {
                switch ($ugroup) {
                    case 'group_members':
                        $ugroups[] = [
                            self::REST_BINDING_LIST_ID    => $GLOBALS['UGROUP_PROJECT_MEMBERS'],
                            self::REST_BINDING_LIST_LABEL => \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) ugroup_get_name_from_id($GLOBALS['UGROUP_PROJECT_MEMBERS'])),
                        ];
                        break;
                    case 'group_admins':
                        $ugroups[] = [
                            self::REST_BINDING_LIST_ID    => $GLOBALS['UGROUP_PROJECT_ADMIN'],
                            self::REST_BINDING_LIST_LABEL => \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) ugroup_get_name_from_id($GLOBALS['UGROUP_PROJECT_ADMIN'])),
                        ];
                        break;
                    case 'artifact_submitters':
                        $ugroups[] = [
                            self::REST_BINDING_LIST_ID    => 0,
                            self::REST_BINDING_LIST_LABEL => $ugroup,
                        ];
                        break;
                    default:
                        if (preg_match('/ugroup_([0-9]+)/', $ugroup, $matches)) {
                            $ugroup_data = db_fetch_array(ugroup_db_get_ugroup($matches[1]));
                            if ($ugroup_data) {
                                $user_group = new ProjectUGroup($ugroup_data);

                                $ugroups[] = [
                                    self::REST_BINDING_LIST_ID    => $matches[1],
                                    self::REST_BINDING_LIST_LABEL => $user_group->getNormalizedName(),
                                ];
                            }
                        }
                        break;
                }
            }
        }
        return $ugroups;
    }

    /**
     * Get all values to be displayed in the field depending of a ugroup list
     *
     * @param array  $ugroups, a list of ugroups
     *
     * @return Tracker_FormElement_Field_List_Bind_UsersValue[]
     */
    protected function getAllValuesByUGroupList($ugroups)
    {
        if ($this->values === null) {
            $value_getter = new BindListUserValueGetter($this->getDefaultValueDao(), UserHelper::instance(), PlatformUsersGetterSingleton::instance(), $this->uuid_factory);
            $this->values = $value_getter->getSubsetOfUsersValueWithUserIds(
                $ugroups,
                [],
                $this->field
            );
        }

        return $this->values;
    }

    /**
     * If all values for this field are already fetched, then returns the collection. Else perform a lookup to retrieve
     * only the needed ids. This avoids to load ten thousands of users for nothing.
     *
     * @return Tracker_FormElement_Field_List_Bind_UsersValue[]
     */
    private function getValuesCollectionContainingIds(array $bindvalue_ids)
    {
        if ($this->values) {
            return $this->values;
        }

        if (empty($bindvalue_ids)) {
            return [];
        }

        $value_getter = new BindListUserValueGetter($this->getDefaultValueDao(), UserHelper::instance(), PlatformUsersGetterSingleton::instance(), $this->uuid_factory);
        return $value_getter->getSubsetOfUsersValueWithUserIds(
            $this->value_function,
            $bindvalue_ids,
            $this->field
        );
    }

    /**
     * Get all values to be displayed in the field
     *
     * @return Tracker_FormElement_Field_List_Bind_UsersValue[]
     */
    public function getAllValues()
    {
        return $this->getAllValuesByUGroupList($this->value_function);
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_UsersValue[]
     */
    public function getAllValuesWithActiveUsersOnly(): array
    {
        if ($this->values === null) {
            $value_getter = new BindListUserValueGetter($this->getDefaultValueDao(), UserHelper::instance(), PlatformUsersGetterSingleton::instance(), $this->uuid_factory);
            $this->values = $value_getter->getActiveUsersValue(
                $this->value_function,
                $this->field
            );
        }

        return $this->values;
    }

    /**
     * @var array of additionnal values (typically users that are not active or removed from the value_function)
     */
    protected $additionnal_values = [];

    /**
     * Return the addtionnal value
     *
     * @return Tracker_FormElement_Field_List_Bind_UsersValue|null
     */
    protected function getAdditionnalValue($value_id)
    {
        if (! isset($this->additionnal_values[$value_id])) {
            $this->additionnal_values[$value_id] = null;
            if ($user = $this->userManager->getUserById($value_id)) {
                $this->additionnal_values[$value_id] = new Tracker_FormElement_Field_List_Bind_UsersValue($this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes()), $user->getId());
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
    public function getValueFromRow($row)
    {
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
    public function getBindtableSqlFragment()
    {
        return [
            'select'     => "user.user_name,
                             user.realname,
                             CONCAT(user.realname,' (',user.user_name,')') AS full_name", //TODO: use UserHelper to respect user preferences
            'select_nb'  => 3,
            'from'       => 'user',
            'join_on_id' => 'user.user_id',
        ];
    }

    /**
     * Get the field data for artifact submission
     *
     * @param string  $submitted_value
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
                if (in_array($value->getUsername(), $submitted_values)) {
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
                if ($value->getUsername() == $submitted_value) {
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
        return "$R2.user_id AS " . $this->field->getQuerySelectName();
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
        return "LEFT JOIN ( tracker_changeset_value AS $R1
                    INNER JOIN $changesetvalue_table AS $R3 ON ($R3.changeset_value_id = $R1.id)
                    LEFT JOIN user AS $R2 ON ($R2.user_id = $R3.bindvalue_id )
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = " . $this->field->id . ' )';
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby(): string
    {
        if (! $this->getField()->isUsed()) {
            return '';
        }
        $uh = UserHelper::instance();
        $R2 = 'R2_' . $this->field->id;
        return $R2 . '.' . str_replace('user.', '', $uh->getDisplayNameSQLOrder());
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
    public function getQuerySelectAggregate($functions)
    {
        $R1       = 'R1_' . $this->field->id;
        $R2       = 'R2_' . $this->field->id;
        $same     = [];
        $separate = [];
        foreach ($functions as $f) {
            if (in_array($f, $this->field->getAggregateFunctions())) {
                if (substr($f, -5) === '_GRBY') {
                    $separate[] = [
                        'function' => $f,
                        'select'   => "$R2.user_name AS label, count(*) AS value",
                        'group_by' => "$R2.user_name",
                    ];
                } else {
                    $select = "$f($R2.user_name) AS `" . $this->field->name . "_$f`";
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
        return implode(',', $values_array);
    }

    public function getDao()
    {
        return new BindUsersDao();
    }

    /**
     * for testing purpose
     */
    protected function getUserManager(): UserManager
    {
        return $this->userManager;
    }

    public function getValueDao()
    {
        return new UserDao();
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
        return self::fetchSelectUsers('formElement_data[bind][value_function][]', $field, []);
    }

    /**
     * Fetch the form to edit the formElement
     *
     * @return string html
     */
    public function fetchAdminEditForm()
    {
        $html  = '';
        $html .= '<h3>' . 'Bind to users' . '</h3>';
        $html .= self::fetchSelectUsers('bind[value_function][]', $this->field, $this->value_function);

        //Select default values
        $html .= $this->getField()->getSelectDefaultValues($this->default_values);

        return $html;
    }

    protected static function fetchSelectUsers($select_name, $field, $value_function)
    {
        $hp       = Codendi_HTMLPurifier::instance();
        $html     = '';
        $html    .= '<input type="hidden" name="' . $select_name . '" value="" />';
        $html    .= '<select multiple="multiple" name="' . $select_name . '" data-test="list-user-bind-values" >
                  <option value="">' . $GLOBALS['Language']->getText('global', 'none') . '</option>';
        $selected = '';
        if (in_array('artifact_submitters', $value_function)) {
            $selected = 'selected="selected"';
        }
        $html .= '<option value="artifact_submitters" ' . $selected . '>' . dgettext('tuleap-tracker', 'Artifact submitters') . '</option>';

        $selected   = '';
        $ugroup_res = ugroup_db_get_ugroup($GLOBALS['UGROUP_PROJECT_MEMBERS']);
        $name       = \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) db_result($ugroup_res, 0, 'name'));
        if (in_array('group_members', $value_function)) {
            $selected = 'selected="selected"';
        }
        $html .= '<option value="group_members" ' . $selected . '>' . $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) . '</option>';

        $selected   = '';
        $ugroup_res = ugroup_db_get_ugroup($GLOBALS['UGROUP_PROJECT_ADMIN']);
        $name       = \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) db_result($ugroup_res, 0, 'name'));
        if (in_array('group_admins', $value_function)) {
            $selected = 'selected="selected"';
        }
        $html .= '<option value="group_admins" ' . $selected . '>' . $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) . '</option>';

        /** @psalm-suppress DeprecatedFunction */
        $ugroup_res = ugroup_db_get_existing_ugroups(100);
        $rows       = db_numrows($ugroup_res);
        for ($i = 0; $i < $rows; $i++) {
            $ug       = db_result($ugroup_res, $i, 'ugroup_id');
            $selected = '';
            if (
                ($ug == $GLOBALS['UGROUP_NONE']) ||
                ($ug == $GLOBALS['UGROUP_ANONYMOUS']) ||
                ($ug == $GLOBALS['UGROUP_PROJECT_MEMBERS']) ||
                ($ug == $GLOBALS['UGROUP_PROJECT_ADMIN']) ||
                ($ug == $GLOBALS['UGROUP_TRACKER_ADMIN'])
            ) {
                   continue;
            }

            $ugr = 'ugroup_' . $ug;
            if (in_array($ugr, $value_function)) {
                $selected = 'selected="selected"';
            }
            $html .= '<option value="' . $ugr . '" ' . $selected . '>' . $hp->purify(\Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) db_result($ugroup_res, $i, 'name')), CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
        }

        $group_id = $field->getTracker()->getGroupId();
        if ($group_id != 100) {
            /** @psalm-suppress DeprecatedFunction */
            $ugroup_res = ugroup_db_get_existing_ugroups($group_id);
            $rows       = db_numrows($ugroup_res);
            for ($i = 0; $i < $rows; $i++) {
                $selected = '';
                $ug       = db_result($ugroup_res, $i, 'ugroup_id');
                $ugr      = 'ugroup_' . $ug;
                if (in_array($ugr, $value_function)) {
                    $selected = 'selected="selected"';
                }
                $html .= '<option value="' . $ugr . '" ' . $selected . '>' . $hp->purify(\Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) db_result($ugroup_res, $i, 'name')), CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
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
    public function process($params, $no_redirect = false)
    {
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'value_function':
                    if (is_array($value) && $this->value_function != $value) {
                        if ($this->getDao()->save($this->field->getId(), $value)) {
                            $this->value_function = $value;
                            if (! $no_redirect) {
                                $GLOBALS['Response']->addFeedback('info', 'Values updated');
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        return parent::process($params, $no_redirect);
    }

    protected function filterDefaultValues(array $bind_default): array
    {
        if (empty($bind_default)) {
            return $bind_default;
        }

        if (! $this->field instanceof Tracker_FormElement_Field_OpenList) {
            return parent::filterDefaultValues($bind_default);
        }

        $bind_default = explode(',', $bind_default[0]);
        foreach ($bind_default as $key => $value) {
            $bind_default[$key] = str_replace(Tracker_FormElement_Field_OpenList::BIND_PREFIX, '', $value);
        }

        return parent::filterDefaultValues($bind_default);
    }

    /**
     * Transforms Bind into a SimpleXMLElement
     */
    public function exportBindToXml(
        SimpleXMLElement $root,
        array &$xmlMapping,
        bool $project_export_context,
        UserXMLExporter $user_xml_exporter,
    ): void {
        if ($this->value_function) {
            $child = $root->addChild('items');
            foreach ($this->value_function as $vf) {
                if ($vf) {
                    $child->addChild('item')->addAttribute('label', $vf);
                }
            }

            if ($project_export_context) {
                $default_values_root = $root->addChild('default_values');
                foreach ($this->default_values as $user_id => $default_value) {
                    $user_xml_exporter->exportUserByUserId($user_id, $default_values_root, 'value');
                }
            }
        }
    }

    /**
     * Verifies the consistency of the imported Tracker
     *
     * @return bool true if Tracler is ok
     */
    public function testImport()
    {
        if (parent::testImport()) {
            if (static::class == 'Tracker_FormElement_Field_Text') {
                if (! (isset($this->default_properties['rows']) && isset($this->default_properties['cols']))) {
                    var_dump($this, 'Properties must be "rows" and "cols"');
                    return false;
                }
            } elseif (static::class == 'Tracker_FormElement_Field_String') {
                if (! (isset($this->default_properties['maxchars']) && isset($this->default_properties['size']))) {
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
    public function getBindValues($bindvalue_ids = null)
    {
        $values = $this->getAllValuesWithActiveUsersOnly();
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
     * @Return Tracker_FormElement_Field_List_BindValue[]
     */
    public function getBindValuesForIds(array $bindvalue_ids)
    {
        $values = $this->getValuesCollectionContainingIds($bindvalue_ids);

        return $this->extractBindValuesByIds($values, $bindvalue_ids);
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
            } else {
                // User not found in the binded ugroup. Look for users that are either:
                //  1. not anymore active
                //  2. not member of the binded ugroup
                $value = $this->getAdditionnalValue($i);
                if ($value) {
                    $list_of_bindvalues[$i] = $value;
                }
            }
        }
        return $list_of_bindvalues;
    }

    /**
     * Saves a bind in the database
     *
     * @return void
     */
    public function saveObject()
    {
        $dao = new BindUsersDao();
        $dao->save($this->field->getId(), $this->getValueFunction());
        parent::saveObject();
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
        foreach ($changeset_value->getListValues() as $user_value) {
            if ($user_value->getId() != 100) {
                $recipients[] = $user_value->getUsername();
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
        if ($value) {
            $values = explode(',', $value);
            foreach ($values as $v) {
                if (stripos($v, '!') !== false) {
                    //we check the string is an email
                    $rule = new Rule_Email();
                    if (! $rule->isValid($v)) {
                        //we check the string correspond to a username
                        if (! $this->userManager->getUserByIdentifier(substr($v, 1))) {
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
    public function fixOriginalValueIds(array $value_mapping)
    {
        // Nothing to do: user value ids stay the same accross projects.
    }

    public function getNumericValues(Tracker_Artifact_ChangesetValue $changeset_value)
    {
        // returns an empty array as it doesn't make sense with Users
        return [];
    }

    public function getType()
    {
        return self::TYPE;
    }

    protected function getRESTBindValue(Tracker_FormElement_Field_List_Value $value)
    {
        $user_representation = new UserListValueRepresentation();
        assert($value instanceof Tracker_FormElement_Field_List_Bind_UsersValue);
        $user_representation->build($value);

        $representation = new FieldListBindUserValueRepresentation();
        $representation->build($value, $user_representation);

        return $representation;
    }

    public function getDefaultRESTValues()
    {
        $bind_values = $this->getBindValuesForIds(array_keys($this->getDefaultValues()));

        $rest_array = [];
        foreach ($bind_values as $value) {
            $representation = \Tuleap\User\REST\UserRepresentation::build($value->getUser(), new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),);
            $rest_array[]   = $representation;
        }
        return $rest_array;
    }

    public function getFieldDataFromRESTObject(array $rest_data, Tracker_FormElement_Field_List $field)
    {
        if (isset($rest_data['id']) && is_numeric($rest_data['id'])) {
            $id   = (int) $rest_data['id'];
            $user = $this->getValue($id);

            if (! $user) {
                throw new Tracker_FormElement_InvalidFieldValueException('Cannot Bind to user with ID ' . $id . ' for field ID ' . $field->getId());
            }
            return Tracker_FormElement_Field_OpenList::BIND_PREFIX . $id;
        }

        if (isset($rest_data['username'])) {
            $identifier = (string) $rest_data['username'];
            $user       = $this->userManager->getUserByIdentifier($identifier);

            if (! $user) {
                throw new Tracker_FormElement_InvalidFieldValueException('Cannot Bind to user "' . $identifier . '" for field ID ' . $field->getId());
            }

            return Tracker_FormElement_Field_OpenList::BIND_PREFIX . $user->getId();
        }

        if (! isset($rest_data['email'])) {
            throw new Tracker_FormElement_InvalidFieldValueException('OpenList user fields values should be passed as an object with at least one of the properties "id", "username" or "email"');
        }

        $identifier = (string) $rest_data['email'];
        $user       = $this->userManager->getUserByIdentifier("email:$identifier");

        if (! $user) {
            return Tracker_FormElement_Field_OpenList::NEW_VALUE_PREFIX . $identifier;
        }
        return Tracker_FormElement_Field_OpenList::BIND_PREFIX . $user->getId();
    }

    public function getFullRESTValue(Tracker_FormElement_Field_List_Value $value)
    {
        $user_manager = UserManager::instance();
        $user         = $user_manager->getUserByUserName($value->getLabel());
        if (! $user) {
            $user = new PFUser();
            $user->setEmail($value->getLabel());
        }

        return UserRepresentation::build($user, new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),);
    }

    public function accept(BindVisitor $visitor, BindParameters $parameters)
    {
        return $visitor->visitListBindUsers($this, $parameters);
    }

    /**
     * @param int $bindvalue_id
     */
    public function getBindValueById($bindvalue_id): Tracker_FormElement_Field_List_BindValue
    {
        return new Tracker_FormElement_Field_List_Bind_UsersValue($this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes()), $bindvalue_id);
    }
}
