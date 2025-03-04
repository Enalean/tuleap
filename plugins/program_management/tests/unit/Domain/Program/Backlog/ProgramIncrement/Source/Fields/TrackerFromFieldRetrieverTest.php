<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tracker_FormElementFactory;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\TrackerFromFieldRetriever;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldNotFoundException;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerFromFieldRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Tracker_FormElementFactory
     */
    private $form_element_factory;
    private TrackerFromFieldRetriever $retriever;

    protected function setUp(): void
    {
        $this->form_element_factory = $this->createStub(Tracker_FormElementFactory::class);
        $this->retriever            = new TrackerFromFieldRetriever($this->form_element_factory);
    }

    public function testItThrowExceptionWhenFieldIsNotFound(): void
    {
        $this->form_element_factory->method('getFieldById')->willReturn(null);

        $this->expectException(FieldNotFoundException::class);
        $this->retriever->fromFieldId(1);
    }

    public function testItReturnsTheField(): void
    {
        $field   = $this->createStub(\Tracker_FormElement_Field::class);
        $tracker = TrackerTestBuilder::aTracker()->withId(1)->withName('Tracker')
            ->withProject(new \Project(['group_id' => 102, 'group_name' => 'A project']))
            ->build();
        $field->method('getTracker')->willReturn($tracker);
        $this->form_element_factory->method('getFieldById')->willReturn($field);

        $program_tracker = $this->retriever->fromFieldId(1);
        self::assertSame(1, $program_tracker->getId());
        self::assertSame('Tracker', $program_tracker->getLabel());
        self::assertSame(102, $program_tracker->getProjectId());
        self::assertSame('A project', $program_tracker->getProjectLabel());
    }
}
