<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields;

use ProjectUGroup;
use Tracker_FormElement_Field_List;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use TuleapTestCase;

class ListFieldCheckerWithBindUgroupsTest extends TuleapTestCase
{
    /** @var ListFieldChecker */
    private $list_field_checker;
    /** @var Tracker_FormElement_Field_List */
    private $field;
    /** @var Comparison */
    private $comparison;
    /** @var Tracker_FormElement_Field_List_Bind_Ugroups */
    private $bind;
    /** @var UgroupLabelConverter */
    private $ugroup_label_converter;

    public function setUp()
    {
        parent::setUp();

        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();

        $this->list_field_checker = new ListFieldChecker(
            new EmptyStringAllowed(),
            new CollectionOfListValuesExtractor(),
            $list_field_bind_value_normalizer,
            new CollectionOfNormalizedBindLabelsExtractor(
                $list_field_bind_value_normalizer
            )
        );

        $this->comparison       = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison');
        $this->bind             = partial_mock('Tracker_FormElement_Field_List_Bind_Ugroups', array(
            'getAllValues'
        ));
        $this->field            = aCheckboxField()->withBind($this->bind)->build();
        $project_members_ugroup = mock('\ProjectUGroup');
        stub($project_members_ugroup)->getTranslatedName()->returns('Project members');
        $project_members_value = aBindUgroupsValue()->withId(2)->withUgroup($project_members_ugroup)->build();
        $custom_ugroup_value   = aBindUgroupsValue()->withId(185)->withUgroup(
            new ProjectUGroup(array(
                'ugroup_id' => 183,
                'name'      => 'Mountaineers'
            ))
        )->build();

        $list_values = array(
            2   => $project_members_value,
            185 => $custom_ugroup_value
        );
        stub($this->bind)->getAllValues()->returns($list_values);
    }

    public function itDoesNotThrowWhenDynamicUgroupIsInValuesAndIsSupported()
    {
        $value_wrapper = new SimpleValueWrapper('Project Members');
        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->pass();
    }

    public function itDoesNotThrowWhenStaticUgroupIsInValues()
    {
        $value_wrapper = new SimpleValueWrapper('MOUNTAINEERS');
        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->pass();
    }

    public function itThrowsWhenStaticUgroupIsNotInValues()
    {
        $value_wrapper = new SimpleValueWrapper('herbaceous');
        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListValueDoNotExistComparisonException');

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function itThrowsWhenDynamicUgroupIsNotInValues()
    {
        $value_wrapper = new SimpleValueWrapper('Project Administrators');
        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListValueDoNotExistComparisonException');

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function itThrowsWhenDynamicUgroupIsNotSupported()
    {
        $value_wrapper = new SimpleValueWrapper('Registered users');
        stub($this->comparison)->getValueWrapper()->returns($value_wrapper);

        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListValueDoNotExistComparisonException');

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }
}
