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

use Tuleap\Option\Option;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
class Tracker_FormElement_Field_List_Bind_Null extends Tracker_FormElement_Field_List_Bind
{
    public const TYPE = 'null';

    public function __construct(public \Tuleap\DB\DatabaseUUIDV7Factory $uuid_factory, $field)
    {
        parent::__construct($this->uuid_factory, $field, [], []);
    }

    /**
     * @return array all values of the field
     */
    #[\Override]
    public function getAllValues()
    {
        return [];
    }

    /**
     * Get available values of this field for REST usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    #[\Override]
    public function getRESTAvailableValues()
    {
        return [];
    }

    /**
     * Get the field data for artifact submission
     *
     * @param string $submitted_value
     * @param bool   $is_multiple     if the value is multiple or not
     *
     * @return mixed the field data corresponding to the alue for artifact submision
     */
    #[\Override]
    public function getFieldData($submitted_value, $is_multiple)
    {
        return [];
    }

    /**
     * @return array
     */
    #[\Override]
    public function getValue($value_id)
    {
        return [];
    }

    /**
     * @return array
     */
    #[\Override]
    public function getChangesetValues($changeset_id)
    {
        return [];
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    #[\Override]
    public function fetchRawValue($value)
    {
        return '';
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    #[\Override]
    public function fetchRawValueFromChangeset($changeset)
    {
        return '';
    }

    /**
     * @return string
     */
    #[\Override]
    public function formatCriteriaValue($value_id)
    {
        return '';
    }

    /**
     * @return string
     */
    #[\Override]
    public function formatMailCriteriaValue($value_id)
    {
        return '';
    }

    /**
     * @return string
     */
    #[\Override]
    public function formatChangesetValue($value)
    {
        return '';
    }

    /**
     * @return string
     */
    #[\Override]
    public function formatChangesetValueForCSV($value)
    {
        return '';
    }

    #[\Override]
    public function getCriteriaFromWhere($criteria_value): Option
    {
        return Option::nothing(ParametrizedFromWhere::class);
    }

    /**
     * Get the "select" statement to retrieve field values
     * @see getQueryFrom
     */
    #[\Override]
    public function getQuerySelect(): string
    {
        return '';
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
    #[\Override]
    public function getQueryFrom($changesetvalue_table = 'tracker_changeset_value_list')
    {
        return '';
    }

    /**
     * Get a bindvalue by its row
     *
     * @param array $row The row identifying the bindvalue
     *
     * @return Tracker_FormElement_Field_List_BindValue
     */
    #[\Override]
    public function getValueFromRow($row)
    {
        return null;
    }

    /**
     * Get the sql fragment used to retrieve value for a changeset to display the bindvalue in table rows for example.
     * Used by OpenList.
     *
     * @return array [
     *                  'bindtable_select'     => "user.user_name, user.realname, CONCAT(user.realname,' (',user.user_name,')') AS full_name",
     *                  'bindtable_select_nb'  => 3,
     *                  'bindtable_from'       => 'user',
     *                  'bindtable_join_on_id' => 'user.user_id',
     *              ]
     */
    #[\Override]
    public function getBindtableSqlFragment()
    {
        return [];
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    #[\Override]
    public function getQueryOrderby(): string
    {
        return '';
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    #[\Override]
    public function getQueryGroupby(): string
    {
        return '';
    }

    #[\Override]
    public function getDao()
    {
        return null;
    }

    #[\Override]
    public function getValueDao()
    {
        return null;
    }

    /**
     * Fetch the form to edit the formElement
     *
     * @return string html
     */
    #[\Override]
    public function fetchAdminEditForm()
    {
        return '';
    }

    /**
     * Allow the user to define the bind
     *
     * @param Field $field
     *
     * @return string html
     */
    #[\Override]
    public static function fetchAdminCreateForm($field)
    {
        return '';
    }

    /**
     * Transforms Bind into a SimpleXMLElement
     */
    #[\Override]
    public function exportBindToXml(
        SimpleXMLElement $root,
        array &$xmlMapping,
        bool $project_export_context,
        UserXMLExporter $user_xml_exporter,
    ): void {
    }

    /**
     * Give an extract of the bindvalues defined. The extract is based on $bindvalue_ids.
     * If the $bindvalue_ids is null then return all values.
     *
     * @param array $bindvalue_ids The ids of BindValue to retrieve
     *
     * @Return array the BindValue(s)
     */
    #[\Override]
    public function getBindValues($bindvalue_ids = null)
    {
        return [];
    }

    #[\Override]
    public function getBindValuesForIds(array $bindvalue_ids)
    {
        return [];
    }

    /**
     * Fixes original value ids after field duplication.
     *
     * @param array $value_mapping An array associating old value ids to new value ids.
     */
    #[\Override]
    public function fixOriginalValueIds(array $value_mapping)
    {
        return [];
    }

    #[\Override]
    public function getQuerySelectAggregate($functions)
    {
        return [];
    }

    #[\Override]
    protected function getRESTBindingList()
    {
        return [];
    }

    #[\Override]
    public function getNumericValues(Tracker_Artifact_ChangesetValue $changeset_value)
    {
        return [];
    }

    #[\Override]
    public function getType()
    {
        return self::TYPE;
    }

    #[\Override]
    public function getFieldDataFromRESTObject(array $rest_data, ListField $field)
    {
        return;
    }

    #[\Override]
    public function getFullRESTValue(Tracker_FormElement_Field_List_Value $value)
    {
        return;
    }

    #[\Override]
    public function accept(BindVisitor $visitor, BindParameters $parameters)
    {
        return $visitor->visitListBindNull($this, $parameters);
    }

    /**
     * @param int $bindvalue_id
     * @return Tracker_FormElement_Field_List_BindValue
     */
    #[\Override]
    public function getBindValueById($bindvalue_id)
    {
        return new Tracker_FormElement_Field_List_Bind_StaticValue_None();
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    #[\Override]
    public function getAllValuesWithActiveUsersOnly(): array
    {
        return [];
    }
}
