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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValueNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldNotFoundException;
use Tuleap\ProgramManagement\Tests\Stub\EndDateFieldReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\StartDateFieldReferenceStub;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DateValueRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID = 9581;
    /**
     * @var Stub&\Tracker_FormElementFactory
     */
    private $form_element_factory;
    private \Tracker_Artifact_Changeset $changeset;
    private \Tuleap\Tracker\FormElement\Field\Date\DateField $date_field;
    private StartDateFieldReferenceStub $start_date;
    private EndDateFieldReferenceStub $end_date;

    #[\Override]
    protected function setUp(): void
    {
        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);
        $this->changeset            = ChangesetTestBuilder::aChangeset(8298)->build();

        $this->date_field = new \Tuleap\Tracker\FormElement\Field\Date\DateField(
            self::FIELD_ID,
            39,
            1000,
            'date',
            'Date',
            'Irrelevant',
            true,
            'P',
            false,
            '',
            1
        );

        $this->start_date = StartDateFieldReferenceStub::withId(self::FIELD_ID);
        $this->end_date   = EndDateFieldReferenceStub::withId(self::FIELD_ID);
    }

    private function getRetriever(): DateValueRetriever
    {
        return new DateValueRetriever($this->form_element_factory);
    }

    public function testItThrowsWhenFieldMatchingReferenceIsNotFound(): void
    {
        $this->form_element_factory->method('getFieldById')->willReturn(null);

        $this->expectException(FieldNotFoundException::class);
        $this->getRetriever()->getDateFieldTimestamp($this->changeset, $this->end_date);
    }

    public function testItThrowsWhenChangesetValueIsNotFound(): void
    {
        $this->form_element_factory->method('getFieldById')->willReturn($this->date_field);
        $this->changeset->setNoFieldValue($this->date_field);

        $this->expectException(ChangesetValueNotFoundException::class);
        $this->getRetriever()->getDateFieldTimestamp($this->changeset, $this->end_date);
    }

    public function testItThrowsWhenDateValueHasNullTimestamp(): void
    {
        $changeset_value = new \Tracker_Artifact_ChangesetValue_Date(
            4965,
            $this->changeset,
            $this->date_field,
            true,
            null
        );
        $this->changeset->setFieldValue($this->date_field, $changeset_value);
        $this->form_element_factory->method('getFieldById')->willReturn($this->date_field);

        $this->expectException(ChangesetValueNotFoundException::class);
        $this->getRetriever()->getDateFieldTimestamp($this->changeset, $this->end_date);
    }

    public function testItReturnsEndDateValueTimestamp(): void
    {
        $changeset_value = new \Tracker_Artifact_ChangesetValue_Date(
            5802,
            $this->changeset,
            $this->date_field,
            true,
            1801793142
        );
        $this->changeset->setFieldValue($this->date_field, $changeset_value);
        $this->form_element_factory->method('getFieldById')->willReturn($this->date_field);

        self::assertSame(1801793142, $this->getRetriever()->getDateFieldTimestamp($this->changeset, $this->end_date));
    }

    public function testItReturnsStartDateValueTimestamp(): void
    {
        $changeset_value = new \Tracker_Artifact_ChangesetValue_Date(
            5787,
            $this->changeset,
            $this->date_field,
            true,
            1863406312
        );
        $this->changeset->setFieldValue($this->date_field, $changeset_value);
        $this->form_element_factory->method('getFieldById')->willReturn($this->date_field);

        self::assertSame(1863406312, $this->getRetriever()->getDateFieldTimestamp($this->changeset, $this->start_date));
    }
}
