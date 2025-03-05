<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlannableTrackersIds;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlannableTrackersIdsStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlannableTrackersRetrieverTest extends TestCase
{
    private RetrievePlannableTrackersIds $plan_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->plan_dao        = RetrievePlannableTrackersIdsStub::buildIds(1, 2);
        $this->tracker_factory = $this->createMock(\TrackerFactory::class);
    }

    public function testItBuildsAnEmptyCollectionOfTracker(): void
    {
        $retriever = new PlannableTrackersRetriever($this->plan_dao, $this->tracker_factory);
        $this->tracker_factory->method('getTrackerById')->willReturn(null);

        self::assertEquals([], $retriever->getPlannableTrackersOfProgram(101));
    }

    public function testItBuildsATrackerReferenceList(): void
    {
        $retriever = new PlannableTrackersRetriever($this->plan_dao, $this->tracker_factory);
        $project   = new \Project(['group_id' => '101', 'group_name' => 'A project', 'unix_group_name' => 'a_project', 'icon_codepoint' => '']);
        $tracker1  = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $tracker2  = TrackerTestBuilder::aTracker()->withId(2)->withProject($project)->build();
        $this->tracker_factory->method('getTrackerById')->willReturnOnConsecutiveCalls($tracker1, $tracker2);

        self::assertEquals(
            [TrackerReferenceProxy::fromTracker($tracker1), TrackerReferenceProxy::fromTracker($tracker2)],
            $retriever->getPlannableTrackersOfProgram((int) $project->getID())
        );
    }
}
