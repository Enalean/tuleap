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

namespace Tuleap\ProgramManagement\Adapter\Team;

use PFUser;
use Tracker;
use Tuleap\AgileDashboard\Planning\RetrieveRootPlanning;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureReference;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveRootPlanningStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\PossibleParentSelector;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

final class PossibleParentSelectorProxyTest extends TestCase
{
    private const TEAM_PROJECT_ID = 555;
    private PFUser $user;
    private Tracker $bug_tracker;
    private Tracker $user_story_tracker;
    private RetrieveRootPlanning $retrieve_root_planning;
    private RetrieveArtifact $retrieve_artifact;
    private ?FeatureIdentifier $feature_53;
    private FeatureReference $feature_53_reference;

    protected function setUp(): void
    {
        $this->user               = UserTestBuilder::buildWithDefaults();
        $team_project             = ProjectTestBuilder::aProject()->withId(self::TEAM_PROJECT_ID)->build();
        $this->user_story_tracker = TrackerTestBuilder::aTracker()
            ->withId(789)
            ->withProject($team_project)
            ->build();
        $this->bug_tracker        = TrackerTestBuilder::aTracker()
            ->withId(324)
            ->withProject($team_project)
            ->build();

        $root_planning                = PlanningBuilder::aPlanning(self::TEAM_PROJECT_ID)
            ->withBacklogTrackers($this->user_story_tracker)
            ->build();
        $this->retrieve_root_planning = RetrieveRootPlanningStub::withProjectAndPlanning(self::TEAM_PROJECT_ID, $root_planning);

        $this->feature_53           = FeatureIdentifierBuilder::withId(53);
        $this->feature_53_reference = new FeatureReference($this->feature_53, 'A fine feature');

        $this->retrieve_artifact = RetrieveArtifactStub::withArtifacts(
            ArtifactTestBuilder::anArtifact($this->feature_53_reference->id)->build()
        );
    }

    public function testUserStoryTrackerIsPartOfScrumTopBacklog(): void
    {
        $event_proxy = PossibleParentSelectorProxy::fromEvent(
            new PossibleParentSelector($this->user, $this->user_story_tracker, 0, 0),
            $this->retrieve_root_planning,
            $this->retrieve_artifact,
        );

        assertTrue($event_proxy->trackerIsInRootPlanning());
    }

    public function testBugTrackerIsNotPartOfScrumTopBacklog(): void
    {
        $event_proxy = PossibleParentSelectorProxy::fromEvent(
            new PossibleParentSelector($this->user, $this->bug_tracker, 0, 0),
            $this->retrieve_root_planning,
            $this->retrieve_artifact,
        );

        assertFalse($event_proxy->trackerIsInRootPlanning());
    }

    public function testItReturnsArtifactsWhenFeaturesGiven(): void
    {
        $event       = new PossibleParentSelector($this->user, $this->user_story_tracker, 0, 0);
        $event_proxy = PossibleParentSelectorProxy::fromEvent(
            $event,
            $this->retrieve_root_planning,
            $this->retrieve_artifact,
        );

        $event_proxy->setPossibleParents(1, $this->feature_53_reference);

        if (! $event->getPossibleParents()) {
            throw new \LogicException("Event does not have possible parents");
        }

        self::assertEquals([$this->feature_53?->id], array_map(static fn (Artifact $artifact) => $artifact->getId(), $event->getPossibleParents()->getArtifacts()));
        self::assertEquals([$this->feature_53_reference->title], array_map(static fn (Artifact $artifact) => $artifact->getTitle(), $event->getPossibleParents()->getArtifacts()));
    }

    public function testItThrowsExceptionWhenFeatureCannotBeMappedToAnArtifact(): void
    {
        $event = new PossibleParentSelector($this->user, $this->user_story_tracker, 0, 0);

        $event_proxy = PossibleParentSelectorProxy::fromEvent(
            $event,
            $this->retrieve_root_planning,
            RetrieveArtifactStub::withNoArtifact(),
        );

        $this->expectException(\RuntimeException::class);

        $event_proxy->setPossibleParents(1, $this->feature_53_reference);
    }

    public function testItReturnsPaginatedArtifactsWithStoreSize(): void
    {
        $event       = new PossibleParentSelector($this->user, $this->user_story_tracker, 1, 1);
        $event_proxy = PossibleParentSelectorProxy::fromEvent(
            $event,
            $this->retrieve_root_planning,
            $this->retrieve_artifact,
        );

        $event_proxy->setPossibleParents(
            75,
            $this->feature_53_reference,
        );

        assertEquals($event->getPossibleParents()?->getTotalSize(), 75);
    }
}
