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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldHasIncorrectTypeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class SynchronizedFieldsGathererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_TRACKER_ID = 37;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\Stub|\TrackerFactory
     */
    private mixed $tracker_factory;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\Stub|\Tracker_Semantic_TitleFactory
     */
    private mixed $title_factory;
    private ProgramIncrementTrackerIdentifier $program_increment;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker_factory = $this->createStub(\TrackerFactory::class);
        $this->title_factory   = $this->createStub(\Tracker_Semantic_TitleFactory::class);

        $this->program_increment = ProgramIncrementTrackerIdentifier::fromId(
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            self::PROGRAM_INCREMENT_TRACKER_ID
        );
        $this->tracker           = TrackerTestBuilder::aTracker()->withId(self::PROGRAM_INCREMENT_TRACKER_ID)->build();
    }

    private function getGatherer(): SynchronizedFieldsGatherer
    {
        return new SynchronizedFieldsGatherer($this->tracker_factory, $this->title_factory);
    }

    public function testItThrowsWhenTrackerCantBeFound(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->getGatherer()->getTitleField($this->program_increment);
    }

    public function testItThrowsWhenFieldCantBeFound(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $title_semantic = new \Tracker_Semantic_Title($this->tracker);
        $this->title_factory->method('getByTracker')->willReturn($title_semantic);

        $this->expectException(FieldRetrievalException::class);
        $this->getGatherer()->getTitleField($this->program_increment);
    }

    public function testItThrowsWhenTitleIsNotAString(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $title_semantic = new \Tracker_Semantic_Title($this->tracker, $this->getTextField(1, 'Title'));
        $this->title_factory->method('getByTracker')->willReturn($title_semantic);

        $this->expectException(TitleFieldHasIncorrectTypeException::class);
        $this->getGatherer()->getTitleField($this->program_increment);
    }

    public function testItReturnsTitleReference(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(self::PROGRAM_INCREMENT_TRACKER_ID)->build();
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);
        $title_semantic = new \Tracker_Semantic_Title($this->tracker, $this->getStringField(832, 'Semiacquaintance'));
        $this->title_factory->method('getByTracker')->willReturn($title_semantic);

        $title = $this->getGatherer()->getTitleField($this->program_increment);
        self::assertSame(832, $title->getId());
        self::assertSame('Semiacquaintance', $title->getLabel());
    }

    private function getStringField(int $id, string $label): \Tracker_FormElement_Field_String
    {
        return new \Tracker_FormElement_Field_String(
            $id,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            null,
            'irrelevant',
            $label,
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }

    private function getTextField(int $id, string $label): \Tracker_FormElement_Field_Text
    {
        return new \Tracker_FormElement_Field_Text(
            $id,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            null,
            'irrelevant',
            $label,
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }
}
