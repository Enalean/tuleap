<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\ProgramIncrementTrackerConfiguration;

use Tuleap\ProgramManagement\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class PotentialProgramIncrementTrackerConfigurationPresentersBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->tracker_factory = $this->createMock(\TrackerFactory::class);
        $this->tracker_factory->method('getTrackersByGroupId')->willReturn([
            TrackerTestBuilder::aTracker()->withId(300)->withName('program increment tracker')->build(),
            TrackerTestBuilder::aTracker()->withId(500)->withName('feature tracker')->build(),
        ]);
    }

    public function testBuildTrackerPresentersWithCheckedTrackerIfExist(): void
    {
        $retriever = RetrieveProgramIncrementTrackerStub::buildValidTrackerId(300);

        $builder    = new PotentialProgramIncrementTrackerConfigurationPresentersBuilder($this->tracker_factory, $retriever);
        $presenters = $builder->buildPotentialProgramIncrementTrackerPresenters(100);

        self::assertCount(2, $presenters);
        self::assertSame(300, $presenters[0]->id);
        self::assertTrue($presenters[0]->is_selected);
        self::assertSame(500, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }

    public function testBuildTrackerPresentersWithoutCheckedTracker(): void
    {
        $retriever = RetrieveProgramIncrementTrackerStub::buildNoProgramIncrementTracker();

        $builder    = new PotentialProgramIncrementTrackerConfigurationPresentersBuilder($this->tracker_factory, $retriever);
        $presenters = $builder->buildPotentialProgramIncrementTrackerPresenters(100);

        self::assertCount(2, $presenters);
        self::assertSame(300, $presenters[0]->id);
        self::assertFalse($presenters[0]->is_selected);
        self::assertSame(500, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }
}
