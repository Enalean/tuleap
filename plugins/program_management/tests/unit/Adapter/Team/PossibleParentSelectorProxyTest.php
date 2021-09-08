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
use Planning;
use Tracker;
use Tuleap\AgileDashboard\Planning\RetrieveRootPlanning;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\PossibleParentSelector;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

final class PossibleParentSelectorProxyTest extends TestCase
{
    private PFUser $user;
    private Tracker $bug_tracker;
    private Tracker $user_story_tracker;
    private RetrieveRootPlanning $retrieve_root_planning;
    private RetrieveArtifact $retrieve_artifact;
    private ?FeatureIdentifier $feature_53;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user               = UserTestBuilder::aUser()->build();
        $team_project             = ProjectTestBuilder::aProject()->withId(555)->build();
        $this->user_story_tracker = TrackerTestBuilder::aTracker()
            ->withId(789)
            ->withProject($team_project)
            ->build();
        $this->bug_tracker        = TrackerTestBuilder::aTracker()
            ->withId(324)
            ->withProject($team_project)
            ->build();

        $this->retrieve_root_planning = new class ((int) $team_project->getID(), $this->user_story_tracker->getId()) implements RetrieveRootPlanning
        {
            public function __construct(private int $team_project_id, private int $backlog_tracker_id)
            {
            }

            public function getRootPlanning(PFUser $user, int $group_id): Planning|false
            {
                if ($group_id !== $this->team_project_id) {
                    return false;
                }
                return new Planning(
                    34,
                    'Release plan',
                    $this->team_project_id,
                    'Backlog',
                    'Releases',
                    [$this->backlog_tracker_id],
                    1
                );
            }
        };

        $this->retrieve_artifact = new class implements RetrieveArtifact
        {
            public function getArtifactById($id): ?Artifact
            {
                return ArtifactTestBuilder::anArtifact((int) $id)
                    ->build();
            }
        };

        $this->feature_53 = FeatureIdentifier::fromId(
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            53,
            UserProxy::buildFromPFUser($this->user),
            ProgramIdentifierBuilder::build(),
            null,
        );
    }

    public function testUserStoryTrackerIsPartOfScrumTopBacklog(): void
    {
        $event_proxy = PossibleParentSelectorProxy::fromEvent(
            new PossibleParentSelector($this->user, $this->user_story_tracker),
            $this->retrieve_root_planning,
            $this->retrieve_artifact,
        );

        assertTrue($event_proxy->trackerIsInRootPlanning());
    }

    public function testBugTrackerIsNotPartOfScrumTopBacklog(): void
    {
        $event_proxy = PossibleParentSelectorProxy::fromEvent(
            new PossibleParentSelector($this->user, $this->bug_tracker),
            $this->retrieve_root_planning,
            $this->retrieve_artifact,
        );

        assertFalse($event_proxy->trackerIsInRootPlanning());
    }

    public function testItReturnsArtifactsWhenFeaturesGiven(): void
    {
        $event       = new PossibleParentSelector($this->user, $this->user_story_tracker);
        $event_proxy = PossibleParentSelectorProxy::fromEvent(
            $event,
            $this->retrieve_root_planning,
            $this->retrieve_artifact,
        );

        $event_proxy->setPossibleParents(
            $this->feature_53,
        );

        self::assertEquals([$this->feature_53->id], array_map(static fn (Artifact $artifact) => $artifact->getId(), $event->getPossibleParents()->getArtifacts()));
    }

    public function testItThrowsExceptionWhenFeatureCannotBeMappedToAnArtifact(): void
    {
        $event = new PossibleParentSelector($this->user, $this->user_story_tracker);

        $retrieve_artifact = new class implements RetrieveArtifact
        {
            public function getArtifactById($id): ?Artifact
            {
                return null;
            }
        };

        $event_proxy = PossibleParentSelectorProxy::fromEvent(
            $event,
            $this->retrieve_root_planning,
            $retrieve_artifact,
        );

        $this->expectException(\RuntimeException::class);

        $event_proxy->setPossibleParents(
            $this->feature_53,
        );
    }
}
