<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BindValueLabel;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValueNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\UnsupportedTitleFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;

final class FieldValuesGathererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SynchronizedFieldReferences $fields;
    private Stub|\Tracker_Artifact_Changeset $changeset;
    private Stub|\Tracker_FormElementFactory $form_element_factory;
    private \Tracker_FormElement_Field_String $title_field;
    private \Tracker_FormElement_Field_Text $description_field;
    private \Tracker_FormElement_Field_Selectbox $status_field;
    private \Tracker_FormElement_Field_Date $start_date_field;
    private \Tracker_FormElement_Field_Date $end_period_field;

    protected function setUp(): void
    {
        $this->title_field       = new \Tracker_FormElement_Field_String(1376, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2);
        $this->description_field = new \Tracker_FormElement_Field_Text(1412, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3);
        $this->status_field      = new \Tracker_FormElement_Field_Selectbox(1499, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4);
        $this->start_date_field  = new \Tracker_FormElement_Field_Date(1784, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 5);
        $this->end_period_field  = new \Tracker_FormElement_Field_Date(1368, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 6);

        $this->fields = SynchronizedFieldReferences::fromTrackerIdentifier(
            GatherSynchronizedFieldsStub::withFieldIds(1376, 1412, 1499, 1784, 1368, 1001),
            TrackerIdentifierStub::buildWithDefault()
        );

        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);
        $this->changeset            = $this->createMock(\Tracker_Artifact_Changeset::class);
    }

    private function getGatherer(): FieldValuesGatherer
    {
        return new FieldValuesGatherer($this->changeset, $this->form_element_factory);
    }

    public function dataProviderMethodUnderTest(): array
    {
        return [
            'when title value is not found'       => ['getTitleValue', 'title'],
            'when description value is not found' => ['getDescriptionValue', 'description'],
            'when start date value is not found'  => ['getStartDateValue', 'start_date'],
            'when end period value is not found'  => ['getEndPeriodValue', 'end_period'],
            'when status value is not found'      => ['getStatusValues', 'status']
        ];
    }

    /**
     * @dataProvider dataProviderMethodUnderTest
     */
    public function testItThrowsWhenFieldMatchingReferenceIsNotFound(
        string $method_under_test,
        string $property_to_call
    ): void {
        $this->form_element_factory->method('getFieldById')->willReturn(null);

        $this->expectException(FieldNotFoundException::class);
        call_user_func([$this->getGatherer(), $method_under_test], $this->fields->{$property_to_call});
    }

    /**
     * @dataProvider dataProviderMethodUnderTest
     */
    public function testItThrowsWhenChangesetValuesAreNotFound(
        string $method_under_test,
        string $property_to_call
    ): void {
        $this->changeset->method('getValue')->willReturn(null);
        $this->changeset->method('getId')->willReturn(1);

        $this->form_element_factory->method('getFieldById')->willReturn(
            $this->createStub(\Tracker_FormElement_Field::class)
        );

        $this->expectException(ChangesetValueNotFoundException::class);
        call_user_func([$this->getGatherer(), $method_under_test], $this->fields->{$property_to_call});
    }

    public function testItThrowsWhenTitleIsNotAString(): void
    {
        $changeset_value = $this->createStub(\Tracker_Artifact_ChangesetValue_Text::class);
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $this->form_element_factory->method('getFieldById')->willReturn($this->title_field);

        $this->expectException(UnsupportedTitleFieldException::class);
        $this->getGatherer()->getTitleValue($this->fields->title);
    }

    public function testItReturnsTitleValue(): void
    {
        $changeset_value = $this->createStub(\Tracker_Artifact_ChangesetValue_String::class);
        $changeset_value->method('getValue')->willReturn('My title');
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $this->form_element_factory->method('getFieldById')->willReturn($this->title_field);

        self::assertSame('My title', $this->getGatherer()->getTitleValue($this->fields->title));
    }

    public function testItReturnsDescriptionValue(): void
    {
        $changeset_value = $this->createStub(\Tracker_Artifact_ChangesetValue_Text::class);
        $changeset_value->method('getValue')->willReturn('My description');
        $changeset_value->method('getFormat')->willReturn('text');
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $this->form_element_factory->method('getFieldById')->willReturn($this->description_field);

        $text_value = $this->getGatherer()->getDescriptionValue($this->fields->description);
        self::assertSame('My description', $text_value->getValue());
        self::assertSame('text', $text_value->getFormat());
    }

    public function testItReturnsStartDateValue(): void
    {
        $changeset_value = $this->createStub(\Tracker_Artifact_ChangesetValue_Date::class);
        $changeset_value->method('getDate')->willReturn('2020-10-01');
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $this->form_element_factory->method('getFieldById')->willReturn($this->start_date_field);

        self::assertSame('2020-10-01', $this->getGatherer()->getStartDateValue($this->fields->start_date));
    }

    public function testItReturnsEndPeriodValueWithEndDate(): void
    {
        $changeset_value = $this->createStub(\Tracker_Artifact_ChangesetValue_Date::class);
        $changeset_value->method('getValue')->willReturn('2023-09-01');
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $this->form_element_factory->method('getFieldById')->willReturn($this->end_period_field);

        self::assertSame('2023-09-01', $this->getGatherer()->getEndPeriodValue($this->fields->end_period));
    }

    public function testItReturnsEndPeriodValueWithDuration(): void
    {
        $changeset_value = $this->createStub(\Tracker_Artifact_ChangesetValue_Integer::class);
        $changeset_value->method('getValue')->willReturn(34);
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $this->form_element_factory->method('getFieldById')->willReturn($this->end_period_field);

        self::assertSame('34', $this->getGatherer()->getEndPeriodValue($this->fields->end_period));
    }

    public function testItReturnsStatusValuesWithStaticBind(): void
    {
        $first_bind_value  = new \Tracker_FormElement_Field_List_Bind_StaticValue(557, 'Planned', '', 0, false);
        $second_bind_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(698, 'Current', '', 1, false);
        $changeset_value   = $this->createStub(\Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value->method('getListValues')->willReturn([$first_bind_value, $second_bind_value]);
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $this->form_element_factory->method('getFieldById')->willReturn($this->status_field);

        $values = $this->getGatherer()->getStatusValues($this->fields->status);
        $labels = array_map(static fn(BindValueLabel $label): string => $label->getLabel(), $values);
        self::assertContains('Planned', $labels);
        self::assertContains('Current', $labels);
    }

    public function testItReturnsStatusValuesWithUsersBind(): void
    {
        $first_bind_value  = new \Tracker_FormElement_Field_List_Bind_UsersValue(138, 'mgregg', 'Meridith Gregg');
        $second_bind_value = new \Tracker_FormElement_Field_List_Bind_UsersValue(129, 'mmantel', 'Mildred Mantel');
        $changeset_value   = $this->createStub(\Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value->method('getListValues')->willReturn([$first_bind_value, $second_bind_value]);
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $this->form_element_factory->method('getFieldById')->willReturn($this->status_field);

        $values = $this->getGatherer()->getStatusValues($this->fields->status);
        $labels = array_map(static fn(BindValueLabel $label): string => $label->getLabel(), $values);
        self::assertContains('Meridith Gregg', $labels);
        self::assertContains('Mildred Mantel', $labels);
    }

    public function testItReturnsStatusValuesWithUserGroupsBind(): void
    {
        $first_ugroup      = new \ProjectUGroup([
            'ugroup_id' => \ProjectUGroup::PROJECT_MEMBERS,
            'name'      => \ProjectUGroup::NORMALIZED_NAMES[\ProjectUGroup::PROJECT_MEMBERS],
        ]);
        $first_bind_value  = new \Tracker_FormElement_Field_List_Bind_UgroupsValue(95, $first_ugroup, false);
        $second_ugroup     = new \ProjectUGroup([
            'ugroup_id' => 351,
            'name'      => 'bicyanide benzothiopyran',
        ]);
        $second_bind_value = new \Tracker_FormElement_Field_List_Bind_UgroupsValue(265, $second_ugroup, false);
        $changeset_value   = $this->createStub(\Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value->method('getListValues')->willReturn([$first_bind_value, $second_bind_value]);
        $this->changeset->method('getValue')->willReturn($changeset_value);

        $this->form_element_factory->method('getFieldById')->willReturn($this->status_field);

        $values = $this->getGatherer()->getStatusValues($this->fields->status);
        $labels = array_map(static fn(BindValueLabel $label): string => $label->getLabel(), $values);
        self::assertContains('project_members', $labels);
        self::assertContains('bicyanide benzothiopyran', $labels);
    }
}
