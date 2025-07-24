<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanSubmitStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID  = 78;
    private const SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID = 57;
    private const FIRST_MIRRORED_ITERATION_TRACKER_ID          = 49;
    private const SECOND_MIRRORED_ITERATION_TRACKER_ID         = 3;

    private RetrieveMirroredProgramIncrementTrackerStub $mirrored_program_increment_tracker_retriever;
    private RetrieveMirroredIterationTrackerStub $mirrored_iteration_tracker_retriever;
    private UserIdentifier $user_identifier;
    private ConfigurationErrorsCollector $error_collector;
    private TeamProjectsCollection $teams;

    #[\Override]
    protected function setUp(): void
    {
        $this->mirrored_program_increment_tracker_retriever = RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID),
            TrackerReferenceStub::withId(self::SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID),
        );
        $this->mirrored_iteration_tracker_retriever         = RetrieveMirroredIterationTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(self::FIRST_MIRRORED_ITERATION_TRACKER_ID),
            TrackerReferenceStub::withId(self::SECOND_MIRRORED_ITERATION_TRACKER_ID),
        );

        $this->user_identifier = UserIdentifierStub::buildGenericUser();
        $this->error_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);

        $this->teams = TeamProjectsCollectionBuilder::withProjects(
            ProjectReferenceStub::withId(103),
            ProjectReferenceStub::withId(104),
        );
    }

    public function testItBuildsEmptyMirroredProgramIncrementsWhenTeamsAreEmpty(): void
    {
        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers(
            $this->mirrored_program_increment_tracker_retriever,
            TeamProjectsCollectionBuilder::withEmptyTeams(),
            $this->user_identifier,
            $this->error_collector
        );
        self::assertTrue($collection->isEmpty());
        self::assertEmpty($collection->getTrackerIds());
        self::assertEmpty($collection->getTrackers());
    }

    public function testItBuildsEmptyMirroredProgramIncrementsWhenNoVisibleRootPlanning(): void
    {
        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers(
            RetrieveMirroredProgramIncrementTrackerStub::withNoRootPlanning(),
            $this->teams,
            $this->user_identifier,
            $this->error_collector
        );
        self::assertTrue($collection->isEmpty());
        self::assertEmpty($collection->getTrackerIds());
        self::assertEmpty($collection->getTrackers());
    }

    public function testItBuildsEmptyMirroredIterationsWhenTeamsAreEmpty(): void
    {
        $collection = TrackerCollection::buildSecondPlanningMilestoneTracker(
            $this->mirrored_iteration_tracker_retriever,
            TeamProjectsCollectionBuilder::withEmptyTeams(),
            $this->user_identifier,
            $this->error_collector
        );
        self::assertTrue($collection->isEmpty());
        self::assertEmpty($collection->getTrackerIds());
        self::assertEmpty($collection->getTrackers());
    }

    public function testItBuildsEmptyMirroredIterationsWhenNoVisibleRootPlanning(): void
    {
        $collection = TrackerCollection::buildSecondPlanningMilestoneTracker(
            RetrieveMirroredIterationTrackerStub::withNoVisibleRootPlanning(),
            $this->teams,
            $this->user_identifier,
            $this->error_collector
        );
        self::assertTrue($collection->isEmpty());
        self::assertEmpty($collection->getTrackerIds());
        self::assertEmpty($collection->getTrackers());
    }

    public function testItBuildsEmptyMirroredIterationsWhenNoVisibleSecondPlanning(): void
    {
        $collection = TrackerCollection::buildSecondPlanningMilestoneTracker(
            RetrieveMirroredIterationTrackerStub::withNoVisibleSecondPlanning(),
            $this->teams,
            $this->user_identifier,
            $this->error_collector
        );
        self::assertTrue($collection->isEmpty());
        self::assertEmpty($collection->getTrackerIds());
        self::assertEmpty($collection->getTrackers());
    }

    public function testReturnsTrueWhenUserCanSubmitInAllRootPlanningTrackers(): void
    {
        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers(
            $this->mirrored_program_increment_tracker_retriever,
            $this->teams,
            $this->user_identifier,
            $this->error_collector
        );
        self::assertTrue(
            $collection->canUserSubmitAnArtifactInAllTrackers(
                $this->user_identifier,
                $this->error_collector,
                VerifyUserCanSubmitStub::userCanSubmit()
            )
        );

        [$first_tracker, $second_tracker] = $collection->getTrackers();
        self::assertEquals(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, $first_tracker->getId());
        self::assertEquals(self::SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, $second_tracker->getId());

        $ids = $collection->getTrackerIds();
        self::assertContains(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, $ids);
        self::assertContains(self::SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, $ids);
    }

    public function testReturnsTrueWhenUserCanSubmitInAllSecondPlanningTrackers(): void
    {
        $collection = TrackerCollection::buildSecondPlanningMilestoneTracker(
            $this->mirrored_iteration_tracker_retriever,
            $this->teams,
            $this->user_identifier,
            $this->error_collector
        );
        self::assertTrue(
            $collection->canUserSubmitAnArtifactInAllTrackers(
                $this->user_identifier,
                $this->error_collector,
                VerifyUserCanSubmitStub::userCanSubmit()
            )
        );

        [$first_tracker, $second_tracker] = $collection->getTrackers();
        self::assertEquals(self::FIRST_MIRRORED_ITERATION_TRACKER_ID, $first_tracker->getId());
        self::assertEquals(self::SECOND_MIRRORED_ITERATION_TRACKER_ID, $second_tracker->getId());

        $ids = $collection->getTrackerIds();
        self::assertContains(self::FIRST_MIRRORED_ITERATION_TRACKER_ID, $ids);
        self::assertContains(self::SECOND_MIRRORED_ITERATION_TRACKER_ID, $ids);
    }

    public function testReturnsFalseWhenUserCanNotSubmitAnArtifactInAllRootPlanningTrackers(): void
    {
        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers(
            $this->mirrored_program_increment_tracker_retriever,
            $this->teams,
            $this->user_identifier,
            $this->error_collector
        );
        self::assertFalse(
            $collection->canUserSubmitAnArtifactInAllTrackers(
                $this->user_identifier,
                $this->error_collector,
                VerifyUserCanSubmitStub::userCanNotSubmit()
            )
        );
    }

    public function testReturnsFalseWhenUserCanNotSubmitAnArtifactInAllSecondPlanningTrackers(): void
    {
        $collection = TrackerCollection::buildSecondPlanningMilestoneTracker(
            $this->mirrored_iteration_tracker_retriever,
            $this->teams,
            $this->user_identifier,
            $this->error_collector
        );
        self::assertFalse(
            $collection->canUserSubmitAnArtifactInAllTrackers(
                $this->user_identifier,
                $this->error_collector,
                VerifyUserCanSubmitStub::userCanNotSubmit()
            )
        );
    }

    public function testCollectsAllInvalidTrackers(): void
    {
        $collection           = TrackerCollection::buildSecondPlanningMilestoneTracker(
            $this->mirrored_iteration_tracker_retriever,
            $this->teams,
            $this->user_identifier,
            $this->error_collector
        );
        $configuration_errors = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        self::assertFalse(
            $collection->canUserSubmitAnArtifactInAllTrackers(
                $this->user_identifier,
                $configuration_errors,
                VerifyUserCanSubmitStub::userCanNotSubmit()
            )
        );
        self::assertCount(2, $configuration_errors->getTeamTrackerIdErrors());
    }

    public function testCollectsTheFirstError(): void
    {
        $collection           = TrackerCollection::buildSecondPlanningMilestoneTracker(
            $this->mirrored_iteration_tracker_retriever,
            $this->teams,
            $this->user_identifier,
            $this->error_collector
        );
        $configuration_errors = $this->error_collector;
        self::assertFalse(
            $collection->canUserSubmitAnArtifactInAllTrackers(
                $this->user_identifier,
                $configuration_errors,
                VerifyUserCanSubmitStub::userCanNotSubmit()
            )
        );
        self::assertCount(1, $configuration_errors->getTeamTrackerIdErrors());
    }
}
