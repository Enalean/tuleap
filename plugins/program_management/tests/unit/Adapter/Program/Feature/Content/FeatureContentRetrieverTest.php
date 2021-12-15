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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Content;

use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureRepresentationBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchFeaturesStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureHasAtLeastOneUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class FeatureContentRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID  = 202;
    private const BUG_ARTIFACT_ID       = 689;
    private const BUG_TITLE             = 'alkalescent';
    private const USER_STORY_ID         = 337;
    private const USER_STORY_TITLE      = 'tracklessly';
    private const BUG_TRACKER_ID        = 32;
    private const USER_STORY_TRACKER_ID = 34;
    private RetrieveFullArtifactStub $artifact_retriever;

    private function getFeatures(): array
    {
        $retriever = new FeatureContentRetriever(
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            SearchFeaturesStub::withFeatureIds(self::BUG_ARTIFACT_ID, self::USER_STORY_ID),
            VerifyFeatureIsVisibleStub::withAlwaysVisibleFeatures(),
            new FeatureRepresentationBuilder(
                $this->artifact_retriever,
                RetrieveFeatureTitleStub::withSuccessiveTitles(self::BUG_TITLE, self::USER_STORY_TITLE),
                RetrieveBackgroundColorStub::withDefaults(),
                VerifyHasAtLeastOnePlannedUserStoryStub::withNothingPlanned(),
                VerifyFeatureHasAtLeastOneUserStoryStub::withoutStories()
            ),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts()
        );

        return $retriever->retrieveProgramIncrementContent(
            self::PROGRAM_INCREMENT_ID,
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsACollectionOfOpenedElements(): void
    {
        $team_project       = ProjectTestBuilder::aProject()
            ->withId(153)
            ->withPublicName('Blue Team')
            ->build();
        $bug_tracker        = $this->buildTracker(self::BUG_TRACKER_ID, 'bug', $team_project);
        $user_story_tracker = $this->buildTracker(self::USER_STORY_TRACKER_ID, 'user stories', $team_project);

        $bug_artifact             = $this->buildArtifact(self::BUG_ARTIFACT_ID, $bug_tracker);
        $user_story_artifact      = $this->buildArtifact(self::USER_STORY_ID, $user_story_tracker);
        $this->artifact_retriever = RetrieveFullArtifactStub::withSuccessiveArtifacts(
            $bug_artifact,
            $user_story_artifact
        );

        $collection = [
            new FeatureRepresentation(
                self::BUG_ARTIFACT_ID,
                self::BUG_TITLE,
                'bug #' . self::BUG_ARTIFACT_ID,
                '/plugins/tracker/?aid=' . self::BUG_ARTIFACT_ID,
                MinimalTrackerRepresentation::build($bug_tracker),
                new BackgroundColor('lake-placid-blue'),
                false,
                false
            ),
            new FeatureRepresentation(
                self::USER_STORY_ID,
                self::USER_STORY_TITLE,
                'user stories #' . self::USER_STORY_ID,
                '/plugins/tracker/?aid=' . self::USER_STORY_ID,
                MinimalTrackerRepresentation::build($user_story_tracker),
                new BackgroundColor('lake-placid-blue'),
                false,
                false
            ),
        ];

        self::assertEquals($collection, $this->getFeatures());
    }

    private function buildTracker(int $tracker_id, string $name, \Project $project): \Tracker
    {
        return TrackerTestBuilder::aTracker()
            ->withId($tracker_id)
            ->withName($name)
            ->withColor(TrackerColor::fromName('deep-blue'))
            ->withProject($project)
            ->build();
    }

    private function buildArtifact(int $artifact_id, \Tracker $tracker): Artifact
    {
        return ArtifactTestBuilder::anArtifact($artifact_id)
            ->inTracker($tracker)
            ->build();
    }
}
