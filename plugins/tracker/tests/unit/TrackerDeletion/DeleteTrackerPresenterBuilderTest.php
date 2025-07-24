<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\TrackerDeletion;

use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\TrackerDeletion\RetrieveDeletedTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DeleteTrackerPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    #[\Override]
    protected function setUp(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    public function testDisplayDeletedTrackersWithNoTrackers(): void
    {
        $tracker_retriever = new TrackerDeletionRetriever(
            RetrieveDeletedTrackerStub::withoutTracker(),
            RetrieveTrackerStub::withoutTracker()
        );

        $builder = new DeleteTrackerPresenterBuilder($tracker_retriever);

        $presenter = $builder->displayDeletedTrackers();
        self::assertEquals([], $presenter->deleted_trackers_list);
    }

    public function testDisplayDeletedTrackersWithValidTrackers(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $tracker                = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $tracker->deletion_date = time();

        $tracker_retriever = new TrackerDeletionRetriever(
            RetrieveDeletedTrackerStub::withTracker($tracker),
            RetrieveTrackerStub::withTracker($tracker)
        );

        $builder = new DeleteTrackerPresenterBuilder($tracker_retriever);

        $presenter = $builder->displayDeletedTrackers();
        self::assertCount(1, $presenter->deleted_trackers_list);
        self::assertEquals($tracker->getTracker()->getId(), $presenter->deleted_trackers_list[0]->id);
        self::assertFalse($presenter->has_warnings);
    }

    public function testDisplayDeletedTrackersWithTrackersHavingWarnings(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(null);
        $tracker                = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $tracker->deletion_date = time();

        $tracker_retriever = new TrackerDeletionRetriever(
            RetrieveDeletedTrackerStub::withTracker($tracker),
            RetrieveTrackerStub::withTracker($tracker)
        );

        $builder = new DeleteTrackerPresenterBuilder($tracker_retriever);

        $presenter = $builder->displayDeletedTrackers();

        self::assertCount(0, $presenter->deleted_trackers_list);
        self::assertTrue($presenter->has_warnings);
    }
}
