<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;

require_once('bootstrap.php');


class Tracker_FormElement_Field_List_Bind_JsonFormatTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->bind = partial_mock('Tracker_FormElement_Field_List_Bind4Tests', array('getAllValues'));

        $this->v1 = mock('Tracker_FormElement_Field_List_BindValue');
        $this->v2 = mock('Tracker_FormElement_Field_List_BindValue');
    }

    public function itDelegatesFormattingToValues()
    {
        expect($this->v1)->fetchFormattedForJson()->once();
        expect($this->v2)->fetchFormattedForJson()->once();

        stub($this->bind)->getAllValues()->returns(array($this->v1, $this->v2));

        $this->bind->fetchFormattedForJson();
    }

    public function itFormatsValuesForJson()
    {
        stub($this->v1)->fetchFormattedForJson()->returns('whatever 1');
        stub($this->v2)->fetchFormattedForJson()->returns('whatever 2');
        stub($this->bind)->getAllValues()->returns(array($this->v1, $this->v2));

        $this->assertIdentical(
            $this->bind->fetchFormattedForJson(),
            array(
                'whatever 1',
                'whatever 2',
            )
        );
    }

    public function itSendsAnEmptyArrayInJSONFormatWhenNoValues()
    {
        stub($this->bind)->getAllValues()->returns(array());
        $this->assertIdentical(
            $this->bind->fetchFormattedForJson(),
            array()
        );
    }
}

class Tracker_FormElement_Field_List_Bind_ValuesTest extends TuleapTestCase
{
    public const NON_EXISTANT_VALUE_ID = 100;
    private $bind;
    private $value_id1 = 101;
    private $value1;

    public function setUp()
    {
        parent::setUp();
        $this->bind = partial_mock('Tracker_FormElement_Field_List_Bind4Tests', array('getAllValues'));

        $this->value1 = mock('Tracker_FormElement_Field_List_BindValue');

        stub($this->bind)->getAllValues()->returns(array($this->value_id1 => $this->value1));
    }

    public function itVerifiesAValueExist()
    {
        $this->assertTrue($this->bind->isExistingValue($this->value_id1));
        $this->assertFalse($this->bind->isExistingValue(self::NON_EXISTANT_VALUE_ID));
    }
}

class Tracker_FormElement_Field_List_Bind4Tests extends Tracker_FormElement_Field_List_Bind
{
    protected function getRESTBindingList()
    {
    }

    public function exportToXml(
        SimpleXMLElement $root,
        &$xmlMapping,
        $project_export_context,
        UserXMLExporter $user_xml_exporter
    ) {
    }

    public function fetchAdminEditForm()
    {
    }

    public function fetchRawValue($value)
    {
    }

    public function fetchRawValueFromChangeset($changeset)
    {
    }

    public function fixOriginalValueIds(array $value_mapping)
    {
    }

    public function formatChangesetValue($value)
    {
    }

    public function formatChangesetValueForCSV($value)
    {
    }

    public function formatChangesetValueWithoutLink($value)
    {
    }

    public function formatCriteriaValue($value_id)
    {
    }

    public function formatMailCriteriaValue($value_id)
    {
    }

    public function getAllValues()
    {
    }

    public function getAllValuesWithActiveUsersOnly() : array
    {
        return [];
    }

    public function getBindValues($bindvalue_ids = null)
    {
    }

    public function getBindValuesForIds(array $bindvalue_ids)
    {
    }

    public function getBindtableSqlFragment()
    {
    }

    public function getChangesetValues($changeset_id)
    {
    }

    public function getCriteriaFrom($criteria_value)
    {
    }

    public function getCriteriaWhere($criteria)
    {
    }

    public function getDao()
    {
    }

    public function getFieldData($rest_value, $is_multiple)
    {
    }

    public function getNumericValues(Tracker_Artifact_ChangesetValue $changeset_value)
    {
    }

    public function getQueryFrom($changesetvalue_table = '')
    {
    }

    public function getQueryGroupby()
    {
    }

    public function getQueryOrderby()
    {
    }

    public function getQuerySelect()
    {
    }

    public function getQuerySelectAggregate($functions)
    {
    }

    public function getValue($value_id)
    {
    }

    public function getValueDao()
    {
    }

    public function getValueFromRow($row)
    {
    }

    public static function fetchAdminCreateForm($field)
    {
    }

    public function getType()
    {
    }

    public function getFieldDataFromRESTObject(array $rest_data, Tracker_FormElement_Field_List $field)
    {
    }

    public function getFullRESTValue(Tracker_FormElement_Field_List_Value $value)
    {
    }

    public function accept(BindVisitor $visitor, BindParameters $parameters)
    {
    }

    public function getBindValueById($bindvalue_id)
    {
    }
}
