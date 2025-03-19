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
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldReferencesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\DurationFieldReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\EndDateFieldReferenceStub;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldValuesGathererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const CHANGESET_ID = 8501;
    private SynchronizedFieldReferences $fields;
    private \Tracker_Artifact_Changeset $changeset;
    /**
     * @var Stub&\Tracker_FormElementFactory
     */
    private $form_element_factory;
    private \Tracker_FormElement_Field_String $title_field;
    private \Tracker_FormElement_Field_Text $description_field;
    private \Tracker_FormElement_Field_Selectbox $status_field;
    private \Tracker_FormElement_Field_Date $start_date_field;
    private \Tracker_FormElement_Field_Date $end_date_field;
    private \Tracker_FormElement_Field_Integer $duration_field;
    private DurationFieldReferenceStub $duration_reference;
    private EndDateFieldReferenceStub $end_date_reference;

    protected function setUp(): void
    {
        $this->title_field       = new \Tracker_FormElement_Field_String(1376, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2);
        $this->description_field = new \Tracker_FormElement_Field_Text(1412, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3);
        $this->status_field      = new \Tracker_FormElement_Field_Selectbox(1499, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4);
        $this->start_date_field  = new \Tracker_FormElement_Field_Date(1784, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 5);
        $this->end_date_field    = new \Tracker_FormElement_Field_Date(1368, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 6);
        $this->duration_field    = new \Tracker_FormElement_Field_Integer(1618, 89, 1000, 'duration', 'Duration', 'Irrelevant', true, 'P', false, '', 7);

        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);
        $this->fields               = SynchronizedFieldReferencesBuilder::build();
        $this->changeset            = ChangesetTestBuilder::aChangeset(self::CHANGESET_ID)->build();
        $this->duration_reference   = DurationFieldReferenceStub::withDefaults();
        $this->end_date_reference   = EndDateFieldReferenceStub::withDefaults();
    }

    private function getGatherer(): FieldValuesGatherer
    {
        return new FieldValuesGatherer(
            $this->changeset,
            $this->form_element_factory,
            new DateValueRetriever($this->form_element_factory)
        );
    }

    public static function dataProviderMethodUnderTest(): array
    {
        return [
            'when title value is not found'       => ['getTitleValue', 'title'],
            'when description value is not found' => ['getDescriptionValue', 'description'],
            'when start date value is not found'  => ['getStartDateValue', 'start_date'],
            'when end date value is not found'    => ['getEndDateValue', 'end_period'],
            'when status value is not found'      => ['getStatusValues', 'status'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderMethodUnderTest')]
    public function testItThrowsWhenFieldMatchingReferenceIsNotFound(
        string $method_under_test,
        string $property_to_call,
    ): void {
        $this->form_element_factory->method('getFieldById')->willReturn(null);

        $this->expectException(FieldNotFoundException::class);
        call_user_func([$this->getGatherer(), $method_under_test], $this->fields->{$property_to_call});
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderMethodUnderTest')]
    public function testItThrowsWhenChangesetValuesAreNotFound(
        string $method_under_test,
        string $property_to_call,
    ): void {
        $field = $this->createStub(\Tracker_FormElement_Field::class);
        $field->method('getId')->willReturn(404);
        $this->form_element_factory->method('getFieldById')->willReturn($field);
        $this->changeset->setNoFieldValue($field);

        $this->expectException(ChangesetValueNotFoundException::class);
        call_user_func([$this->getGatherer(), $method_under_test], $this->fields->{$property_to_call});
    }

    public function testItThrowsWhenDurationFieldIsNotFound(): void
    {
        $this->form_element_factory->method('getFieldById')->willReturn(null);

        $this->expectException(FieldNotFoundException::class);
        $this->getGatherer()->getDurationValue($this->duration_reference);
    }

    public function testItThrowsWhenDurationChangesetValueIsNotFound(): void
    {
        $this->form_element_factory->method('getFieldById')->willReturn($this->duration_field);
        $this->changeset->setNoFieldValue($this->duration_field);

        $this->expectException(ChangesetValueNotFoundException::class);
        $this->getGatherer()->getDurationValue($this->duration_reference);
    }

    public function testItThrowsWhenTitleIsNotAString(): void
    {
        $changeset_value = new \Tracker_Artifact_ChangesetValue_Text(
            7335,
            $this->changeset,
            $this->title_field,
            true,
            'A title that is a Text field',
            'text'
        );
        $this->changeset->setFieldValue($this->title_field, $changeset_value);
        $this->form_element_factory->method('getFieldById')->willReturn($this->title_field);

        $this->expectException(UnsupportedTitleFieldException::class);
        $this->getGatherer()->getTitleValue($this->fields->title);
    }

    public function testItReturnsTitleValue(): void
    {
        $changeset_value = $this->createStub(\Tracker_Artifact_ChangesetValue_String::class);
        $changeset_value->method('getValue')->willReturn('My title');
        $this->changeset->setFieldValue($this->title_field, $changeset_value);
        $this->form_element_factory->method('getFieldById')->willReturn($this->title_field);

        self::assertSame('My title', $this->getGatherer()->getTitleValue($this->fields->title));
    }

    public function testItReturnsDescriptionValue(): void
    {
        $changeset_value = new \Tracker_Artifact_ChangesetValue_Text(
            7019,
            $this->changeset,
            $this->title_field,
            true,
            'My description',
            'text'
        );
        $this->changeset->setFieldValue($this->description_field, $changeset_value);
        $this->form_element_factory->method('getFieldById')->willReturn($this->description_field);

        $text_value = $this->getGatherer()->getDescriptionValue($this->fields->description);
        self::assertSame('My description', $text_value->getValue());
        self::assertSame('text', $text_value->getFormat());
    }

    public function testItReturnsStartDateValue(): void
    {
        $changeset_value = new \Tracker_Artifact_ChangesetValue_Date(
            1374,
            $this->changeset,
            $this->start_date_field,
            true,
            1601579528
        );
        $this->changeset->setFieldValue($this->start_date_field, $changeset_value);
        $this->form_element_factory->method('getFieldById')->willReturn($this->start_date_field);

        self::assertSame(1601579528, $this->getGatherer()->getStartDateValue($this->fields->start_date));
    }

    public function testItReturnsEndDateValue(): void
    {
        $changeset_value = new \Tracker_Artifact_ChangesetValue_Date(
            5545,
            $this->changeset,
            $this->end_date_field,
            true,
            1601579528
        );
        $this->changeset->setFieldValue($this->end_date_field, $changeset_value);
        $this->form_element_factory->method('getFieldById')->willReturn($this->end_date_field);

        self::assertSame(1601579528, $this->getGatherer()->getEndDateValue($this->end_date_reference));
    }

    public function testItReturnsDurationValue(): void
    {
        $changeset_value = new \Tracker_Artifact_ChangesetValue_Integer(
            1561,
            $this->changeset,
            $this->duration_field,
            true,
            34
        );
        $this->changeset->setFieldValue($this->duration_field, $changeset_value);
        $this->form_element_factory->method('getFieldById')->willReturn($this->duration_field);

        self::assertSame(34, $this->getGatherer()->getDurationValue($this->duration_reference));
    }

    public function testItReturnsStatusValuesWithStaticBind(): void
    {
        $first_bind_value  = ListStaticValueBuilder::aStaticValue('Planned')->withId(557)->build();
        $second_bind_value = ListStaticValueBuilder::aStaticValue('Current')->withId(698)->build();
        $changeset_value   = new \Tracker_Artifact_ChangesetValue_List(
            9331,
            $this->changeset,
            $this->status_field,
            true,
            [$first_bind_value, $second_bind_value]
        );
        $this->changeset->setFieldValue($this->status_field, $changeset_value);
        $this->form_element_factory->method('getFieldById')->willReturn($this->status_field);

        $values = $this->getGatherer()->getStatusValues($this->fields->status);
        $labels = array_map(static fn(BindValueLabel $label): string => $label->getLabel(), $values);
        self::assertContains('Planned', $labels);
        self::assertContains('Current', $labels);
    }

    public function testItReturnsStatusValuesWithUsersBind(): void
    {
        $first_bind_value  = ListUserValueBuilder::aUserWithId(138)->withDisplayedName('Meridith Gregg')->build();
        $second_bind_value = ListUserValueBuilder::aUserWithId(129)->withDisplayedName('Mildred Mantel')->build();
        $changeset_value   = new \Tracker_Artifact_ChangesetValue_List(
            9331,
            $this->changeset,
            $this->status_field,
            true,
            [$first_bind_value, $second_bind_value]
        );
        $this->changeset->setFieldValue($this->status_field, $changeset_value);
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
        $first_bind_value  = ListUserGroupValueBuilder::aUserGroupValue($first_ugroup)->withId(95)->build();
        $second_ugroup     = new \ProjectUGroup([
            'ugroup_id' => 351,
            'name'      => 'bicyanide benzothiopyran',
        ]);
        $second_bind_value = ListUserGroupValueBuilder::aUserGroupValue($second_ugroup)->withId(256)->build();
        $changeset_value   = new \Tracker_Artifact_ChangesetValue_List(
            9331,
            $this->changeset,
            $this->status_field,
            true,
            [$first_bind_value, $second_bind_value]
        );
        $this->changeset->setFieldValue($this->status_field, $changeset_value);
        $this->form_element_factory->method('getFieldById')->willReturn($this->status_field);

        $values = $this->getGatherer()->getStatusValues($this->fields->status);
        $labels = array_map(static fn(BindValueLabel $label): string => $label->getLabel(), $values);
        self::assertContains('project_members', $labels);
        self::assertContains('bicyanide benzothiopyran', $labels);
    }
}
