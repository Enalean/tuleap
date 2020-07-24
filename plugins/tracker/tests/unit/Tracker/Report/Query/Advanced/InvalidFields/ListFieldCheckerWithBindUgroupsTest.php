<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use Tracker_FormElement_Field_Checkbox;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_UgroupsValue;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;

class ListFieldCheckerWithBindUgroupsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ListFieldChecker */
    private $list_field_checker;
    /** @var Tracker_FormElement_Field_List */
    private $field;
    /** @var Comparison */
    private $comparison;
    /** @var Tracker_FormElement_Field_List_Bind_Ugroups */
    private $bind;
    /** @var UgroupLabelConverter */
    private $ugroup_label_converter_for_collection;
    /** @var UgroupLabelConverter */
    private $ugroup_label_converter_for_list_checker;

    protected function setUp(): void
    {
        parent::setUp();

        $list_field_bind_value_normalizer              = new ListFieldBindValueNormalizer();
        $this->ugroup_label_converter_for_collection   = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter::class);
        $this->ugroup_label_converter_for_list_checker = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter::class);

        $this->list_field_checker = new ListFieldChecker(
            new EmptyStringAllowed(),
            new CollectionOfListValuesExtractor(),
            $list_field_bind_value_normalizer,
            new CollectionOfNormalizedBindLabelsExtractor(
                $list_field_bind_value_normalizer,
                $this->ugroup_label_converter_for_collection
            ),
            $this->ugroup_label_converter_for_list_checker
        );

        $this->comparison       = \Mockery::spy(\Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison::class);
        $this->bind             = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Ugroups::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->field            = $this->buildCheckboxField();

        $project_members_ugroup = \Mockery::spy(\ProjectUGroup::class);
        $project_members_ugroup->shouldReceive('getTranslatedName')->andReturns('Project members');

        $project_members_value = new Tracker_FormElement_Field_List_Bind_UgroupsValue(2, $project_members_ugroup, false);
        $custom_ugroup_value = new Tracker_FormElement_Field_List_Bind_UgroupsValue(
            185,
            new ProjectUGroup(
                [
                    'ugroup_id' => 183,
                    'name'      => 'Mountaineers'
                ]
            ),
            false
        );

        $list_values = [
            2   => $project_members_value,
            185 => $custom_ugroup_value
        ];
        $this->bind->shouldReceive('getAllValues')->andReturns($list_values);

        $this->ugroup_label_converter_for_collection->shouldReceive('isASupportedDynamicUgroup')->with('Project members')->andReturns(true);
        $this->ugroup_label_converter_for_collection->shouldReceive('convertLabelToTranslationKey')->with('Project members')->andReturns('ugroup_project_members_name_key');
        $this->ugroup_label_converter_for_collection->shouldReceive('isASupportedDynamicUgroup')->with('Mountaineers')->andReturns(false);
    }

    private function buildCheckboxField(): Tracker_FormElement_Field_Checkbox
    {
        $field =  new Tracker_FormElement_Field_Checkbox(
            1,
            101,
            null,
            'checkbox',
            'Checkbox',
            null,
            true,
            null,
            null,
            null,
            null,
            null
        );

        $field->setBind($this->bind);

        return $field;
    }

    public function testItDoesNotThrowWhenDynamicUgroupIsInValuesAndIsSupported(): void
    {
        $this->ugroup_label_converter_for_list_checker->shouldReceive('isASupportedDynamicUgroup')->andReturns(true);
        $this->ugroup_label_converter_for_list_checker->shouldReceive('convertLabelToTranslationKey')->andReturns('ugroup_project_members_name_key');

        $value_wrapper = new SimpleValueWrapper('Project Members');
        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->doesNotPerformAssertions();
    }

    public function testItDoesNotThrowWhenStaticUgroupIsInValues(): void
    {
        $this->ugroup_label_converter_for_list_checker->shouldReceive('isASupportedDynamicUgroup')->andReturns(false);

        $value_wrapper = new SimpleValueWrapper('MOUNTAINEERS');
        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
        $this->doesNotPerformAssertions();
    }

    public function testItThrowsWhenStaticUgroupIsNotInValues(): void
    {
        $this->ugroup_label_converter_for_list_checker->shouldReceive('isASupportedDynamicUgroup')->andReturns(false);

        $value_wrapper = new SimpleValueWrapper('herbaceous');
        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);

        $this->expectException(\Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListValueDoNotExistComparisonException::class);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function testItThrowsWhenDynamicUgroupIsNotInValues(): void
    {
        $this->ugroup_label_converter_for_list_checker->shouldReceive('isASupportedDynamicUgroup')->andReturns(true);
        $this->ugroup_label_converter_for_list_checker->shouldReceive('convertLabelToTranslationKey')->andReturns('ugroup_project_admins_name_key');

        $value_wrapper = new SimpleValueWrapper('Project Administrators');
        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);

        $this->expectException(\Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListValueDoNotExistComparisonException::class);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }

    public function testItThrowsWhenDynamicUgroupIsNotSupported(): void
    {
        $this->ugroup_label_converter_for_list_checker->shouldReceive('isASupportedDynamicUgroup')->andReturns(false);

        $value_wrapper = new SimpleValueWrapper('Registered users');
        $this->comparison->shouldReceive('getValueWrapper')->andReturns($value_wrapper);

        $this->expectException(\Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListValueDoNotExistComparisonException::class);

        $this->list_field_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }
}
