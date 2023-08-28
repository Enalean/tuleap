<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Team;

use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\ProgramManagement\Adapter\Team\PossibleParentSelectorProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveOpenFeatureCount;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveOpenFeatureCountStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveRootPlanningStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchOpenFeaturesStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchProgramsOfTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleByProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationTrackerStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\PossibleParentSelector;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;

final class PossibleParentHandlerTest extends TestCase
{
    private const FEATURE_ID   = 123;
    private const PROGRAM_ID_1 = 899;
    private const PROGRAM_ID_2 = 741;
    private const PROJECT_ID   = 555;

    private SearchOpenFeaturesStub $search_open_features;
    private RetrieveOpenFeatureCount $retrieve_open_feature_count;
    private RetrieveArtifactStub $retrieve_artifact;
    private \Project $project;
    private \Tracker $tracker;
    private \PFUser $user;
    private RetrieveRootPlanningStub $retrieve_planning;
    private PossibleParentSelector $event;
    private PossibleParentHandler $possible_parent;
    private PossibleParentSelectorProxy $possible_parent_selector;

    protected function setUp(): void
    {
        $this->search_open_features = SearchOpenFeaturesStub::withRows(
            [
                [
                    'artifact_id' => self::FEATURE_ID,
                    'program_id'  => self::PROGRAM_ID_1,
                    'title'       => 'A fine feature',
                ],
            ]
        );

        $this->retrieve_open_feature_count = RetrieveOpenFeatureCountStub::withValue(0);

        $this->user    = UserTestBuilder::buildWithDefaults();
        $this->project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $this->tracker = TrackerTestBuilder::aTracker()
            ->withId(789)
            ->withProject($this->project)
            ->build();

        $this->event = new PossibleParentSelector($this->user, $this->tracker, 0, 10);

        $root_planning           = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withBacklogTrackers($this->tracker)
            ->build();
        $this->retrieve_planning = RetrieveRootPlanningStub::withProjectAndPlanning(self::PROJECT_ID, $root_planning);

        $this->possible_parent = new PossibleParentHandler(
            VerifyFeatureIsVisibleByProgramStub::withAlwaysVisibleFeatures(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1),
            $this->search_open_features,
            $this->retrieve_open_feature_count,
            VerifyIsIterationTrackerStub::buildNotIteration()
        );


        $this->retrieve_artifact = RetrieveArtifactStub::withArtifacts(
            ArtifactTestBuilder::anArtifact(self::FEATURE_ID)->build()
        );

        $this->possible_parent_selector = PossibleParentSelectorProxy::fromEvent(
            $this->event,
            $this->retrieve_planning,
            $this->retrieve_artifact
        );
    }

    public function testItHasOneParent(): void
    {
        $this->possible_parent->handle($this->possible_parent_selector);

        self::assertNotNull($this->event->getPossibleParents());

        assertEquals(
            [self::FEATURE_ID],
            array_map(
                static fn(Artifact $feature): int => $feature->getId(),
                $this->event->getPossibleParents()->getArtifacts()
            )
        );
        assertEquals(
            ["A fine feature"],
            array_map(
                static fn(Artifact $feature): ?string => $feature->getTitle(),
                $this->event->getPossibleParents()->getArtifacts()
            )
        );
    }

    public function testItHasOffsetAndLimit(): void
    {
        $event = new PossibleParentSelector($this->user, $this->tracker, 100, 50);

        $possible_parent_selector = PossibleParentSelectorProxy::fromEvent(
            $event,
            $this->retrieve_planning,
            $this->retrieve_artifact
        );

        $this->retrieve_open_feature_count = RetrieveOpenFeatureCountStub::withValue(200);

        $possible_parent = new PossibleParentHandler(
            VerifyFeatureIsVisibleByProgramStub::withAlwaysVisibleFeatures(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1),
            $this->search_open_features,
            $this->retrieve_open_feature_count,
            VerifyIsIterationTrackerStub::buildNotIteration()
        );

        $possible_parent->handle($possible_parent_selector);

        assertEquals(100, $event->offset);
        assertEquals(50, $event->limit);
        assertEquals(200, $event->getPossibleParents()?->getTotalSize());
    }

    public function testItContinueIfProgramAccessExceptionIsThrow(): void
    {
        $this->retrieve_open_feature_count = RetrieveOpenFeatureCountStub::withValue(0);
        $search_open_features              = SearchOpenFeaturesStub::withRows([]);

        $possible_parent = new PossibleParentHandler(
            VerifyFeatureIsVisibleByProgramStub::withFeatureNotVisibleOrNotInProgram(),
            BuildProgramStub::stubInvalidProgramAccess(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1),
            $search_open_features,
            $this->retrieve_open_feature_count,
            VerifyIsIterationTrackerStub::buildNotIteration()
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertEquals(0, $this->event->offset);
        assertEquals(10, $this->event->limit);
        assertEquals(0, $this->event->getPossibleParents()?->getTotalSize());
    }

    public function testDisableCreateWhenInTheContextOfTeamAttachedToProgramToAvoidCrossProjectRedirections(): void
    {
        $this->possible_parent->handle($this->possible_parent_selector);

        assertFalse($this->event->canCreate());
    }

    public function testItDoesntFillPossibleParentWhenTrackerIsNotInATeam(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyFeatureIsVisibleByProgramStub::withAlwaysVisibleFeatures(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(),
            $this->search_open_features,
            $this->retrieve_open_feature_count,
            VerifyIsIterationTrackerStub::buildNotIteration()
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertNull($this->event->getPossibleParents());
    }

    public function testAnArtifactThatCannotBeInTeamProjectBacklogWillNotHavePossibleParents(): void
    {
        $other_backlog_tracker    = TrackerTestBuilder::aTracker()->withId(666)->build();
        $root_planning            = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withBacklogTrackers($other_backlog_tracker)
            ->build();
        $retrieve_planning        = RetrieveRootPlanningStub::withProjectAndPlanning(self::PROJECT_ID, $root_planning);
        $possible_parent_selector = PossibleParentSelectorProxy::fromEvent(
            $this->event,
            $retrieve_planning,
            $this->retrieve_artifact
        );


        $this->possible_parent->handle($possible_parent_selector);

        assertNull($this->event->getPossibleParents());
    }

    public function testItDoesntAddToPossibleParentsAnArtifactThatIsNotVisible(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyFeatureIsVisibleByProgramStub::withFeatureNotVisibleOrNotInProgram(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1),
            $this->search_open_features,
            $this->retrieve_open_feature_count,
            VerifyIsIterationTrackerStub::buildNotIteration()
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertEquals([], $this->event->getPossibleParents()?->getArtifacts());
    }

    public function testItLooksForProgramsAtOnce(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyFeatureIsVisibleByProgramStub::withAlwaysVisibleFeatures(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1, self::PROGRAM_ID_2),
            $this->search_open_features,
            $this->retrieve_open_feature_count,
            VerifyIsIterationTrackerStub::buildNotIteration()
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertEquals(
            [self::PROGRAM_ID_1, self::PROGRAM_ID_2],
            array_map(
                static fn(ProgramIdentifier $prgm_id) => $prgm_id->getId(),
                $this->search_open_features->getProgramIdentifiers()
            )
        );
    }

    public function testItDisableSelectorForIterationTracker(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyFeatureIsVisibleByProgramStub::withAlwaysVisibleFeatures(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1, self::PROGRAM_ID_2),
            $this->search_open_features,
            $this->retrieve_open_feature_count,
            VerifyIsIterationTrackerStub::buildValidIteration()
        );
        $possible_parent->handle($this->possible_parent_selector);

        self::assertNull($this->event->getPossibleParents());
        self::assertFalse($this->event->isSelectorDisplayed());
    }
}
