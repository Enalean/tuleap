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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchPlannableFeaturesStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureHasAtLeastOneUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class FeatureElementsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_ID       = 202;
    private const BUG_ID           = 1;
    private const USER_STORY_ID    = 2;
    private const BUG_TITLE        = 'Artifact 1';
    private const USER_STORY_TITLE = 'Artifact 2';
    private RetrieveFullArtifactStub $artifact_retriever;
    private SearchPlannableFeaturesStub $features_searcher;

    protected function setUp(): void
    {
        $this->features_searcher = SearchPlannableFeaturesStub::withFeatureIds(self::BUG_ID, self::USER_STORY_ID);
    }

    private function getFeatures(): array
    {
        $retriever = new FeatureElementsRetriever(
            BuildProgramStub::stubValidProgram(),
            $this->features_searcher,
            VerifyFeatureIsVisibleStub::withAlwaysVisibleFeatures(),
            new FeatureRepresentationBuilder(
                $this->artifact_retriever,
                RetrieveFeatureTitleStub::withSuccessiveTitles(self::BUG_TITLE, self::USER_STORY_TITLE),
                RetrieveBackgroundColorStub::withDefaults(),
                VerifyHasAtLeastOnePlannedUserStoryStub::withNothingPlanned(),
                VerifyFeatureHasAtLeastOneUserStoryStub::withoutStories()
            )
        );

        return $retriever->retrieveFeaturesToBePlanned(self::PROGRAM_ID, UserIdentifierStub::buildGenericUser());
    }

    public function testItBuildsACollectionOfOpenedElements(): void
    {
        $project      = ProjectTestBuilder::aProject()->withId(202)->withPublicName('My project')->build();
        $tracker_one  = $this->buildTracker(1, 'bug', $project);
        $artifact_one = $this->buildArtifact(self::BUG_ID, $tracker_one);
        $tracker_two  = $this->buildTracker(2, 'user stories', $project);
        $artifact_two = $this->buildArtifact(self::USER_STORY_ID, $tracker_two);

        $this->artifact_retriever = RetrieveFullArtifactStub::withSuccessiveArtifacts($artifact_one, $artifact_two);

        $collection = [
            new FeatureRepresentation(
                self::BUG_ID,
                self::BUG_TITLE,
                'bug #1',
                '/plugins/tracker/?aid=1',
                MinimalTrackerRepresentation::build($tracker_one),
                new BackgroundColor("lake-placid-blue"),
                false,
                false
            ),
            new FeatureRepresentation(
                self::USER_STORY_ID,
                self::USER_STORY_TITLE,
                'user stories #2',
                '/plugins/tracker/?aid=2',
                MinimalTrackerRepresentation::build($tracker_two),
                new BackgroundColor("lake-placid-blue"),
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
