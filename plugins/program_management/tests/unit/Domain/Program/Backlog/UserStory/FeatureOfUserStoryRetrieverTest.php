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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeatureHasUserStoriesVerifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\FeatureOfUserStoryRetriever;
use Tuleap\ProgramManagement\Tests\Builder\FeatureHasUserStoriesVerifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\UserStoryIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\CheckIsValidFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureCrossReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFeatureURIStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchParentFeatureOfAUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;
use Tuleap\Test\PHPUnit\TestCase;

final class FeatureOfUserStoryRetrieverTest extends TestCase
{
    private const FEATURE_ID = 1;
    private SearchParentFeatureOfAUserStoryStub $search_parent_feature_of_a_user_story;
    private FeatureHasUserStoriesVerifier $feature_has_user_stories_verifier;
    private UserStoryIdentifier $story_identifier;
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $this->story_identifier                      = UserStoryIdentifierBuilder::withId(1);
        $this->user_identifier                       = UserIdentifierStub::buildGenericUser();
        $this->search_parent_feature_of_a_user_story = SearchParentFeatureOfAUserStoryStub::withoutParentFeature();
        $this->feature_has_user_stories_verifier     = FeatureHasUserStoriesVerifierBuilder::buildWithoutUserStories();
    }

    private function getRetriever(): FeatureOfUserStoryRetriever
    {
        $title_retriever           = RetrieveFeatureTitleStub::withTitle('US 1');
        $uri_retriever             = new RetrieveFeatureURIStub();
        $cross_reference_retriever = RetrieveFeatureCrossReferenceStub::withShortname('feature');
        $planned_verifier          = VerifyHasAtLeastOnePlannedUserStoryStub::withPlannedUserStory();
        $check_is_valid_feature    = CheckIsValidFeatureStub::withAlwaysValidFeatures();
        $background_retriever      = RetrieveBackgroundColorStub::withColor('fiesta-red');
        $tracker_retriever         = RetrieveTrackerOfFeatureStub::withId(10);

        return new FeatureOfUserStoryRetriever(
            $title_retriever,
            $uri_retriever,
            $cross_reference_retriever,
            $planned_verifier,
            $check_is_valid_feature,
            $background_retriever,
            $tracker_retriever,
            $this->search_parent_feature_of_a_user_story,
            $this->feature_has_user_stories_verifier
        );
    }

    public function testItReturnsNullWhenFeatureHasNoParent(): void
    {
        self::assertNull($this->getRetriever()->retrieveFeature($this->story_identifier, $this->user_identifier));
    }

    public function testItReturnsNullWhenFeatureIsNotValid(): void
    {
        $this->search_parent_feature_of_a_user_story = SearchParentFeatureOfAUserStoryStub::withoutParentFeature();
        $this->feature_has_user_stories_verifier     = FeatureHasUserStoriesVerifierBuilder::buildWithUserStories();

        self::assertNull($this->getRetriever()->retrieveFeature($this->story_identifier, $this->user_identifier));
    }

    public function testItReturnsFeature(): void
    {
        $this->search_parent_feature_of_a_user_story = SearchParentFeatureOfAUserStoryStub::withParentFeatureId(self::FEATURE_ID);
        $this->feature_has_user_stories_verifier     = FeatureHasUserStoriesVerifierBuilder::buildWithUserStories();

        self::assertEquals(
            self::FEATURE_ID,
            $this->getRetriever()->retrieveFeature($this->story_identifier, $this->user_identifier)?->feature_identifier->getId()
        );
    }
}
