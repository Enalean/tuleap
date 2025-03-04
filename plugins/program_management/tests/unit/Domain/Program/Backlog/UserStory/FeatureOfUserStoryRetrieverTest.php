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
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Feature;
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
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsOpenStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeatureOfUserStoryRetrieverTest extends TestCase
{
    private const FEATURE_ID         = 44;
    private const FEATURE_TITLE      = 'reamass';
    private const FEATURE_SHORT_NAME = 'feature';
    private const BASE_URI           = '/plugins/tracker/?aid=';
    private SearchParentFeatureOfAUserStoryStub $search_parent_feature_of_a_user_story;
    private FeatureHasUserStoriesVerifier $feature_has_user_stories_verifier;

    protected function setUp(): void
    {
        $this->search_parent_feature_of_a_user_story = SearchParentFeatureOfAUserStoryStub::withParentFeatureId(
            self::FEATURE_ID
        );
        $this->feature_has_user_stories_verifier     = FeatureHasUserStoriesVerifierBuilder::buildWithUserStories();
    }

    private function getFeature(): ?Feature
    {
        $retriever = new FeatureOfUserStoryRetriever(
            RetrieveFeatureTitleStub::withTitle(self::FEATURE_TITLE),
            new RetrieveFeatureURIStub(),
            RetrieveFeatureCrossReferenceStub::withShortname(self::FEATURE_SHORT_NAME),
            VerifyHasAtLeastOnePlannedUserStoryStub::withPlannedUserStory(),
            CheckIsValidFeatureStub::withAlwaysValidFeatures(),
            RetrieveBackgroundColorStub::withColor('fiesta-red'),
            RetrieveTrackerOfFeatureStub::withId(10),
            $this->search_parent_feature_of_a_user_story,
            $this->feature_has_user_stories_verifier,
            VerifyFeatureIsOpenStub::withOpen()
        );

        return $retriever->retrieveFeature(
            UserStoryIdentifierBuilder::withId(1),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItReturnsFeature(): void
    {
        $feature = $this->getFeature();
        self::assertNotNull($feature);
        self::assertSame(self::FEATURE_ID, $feature->feature_identifier->getId());
        self::assertSame(self::FEATURE_TITLE, $feature->title);
        self::assertSame(self::BASE_URI . self::FEATURE_ID, $feature->uri);
        self::assertTrue($feature->is_open);
    }

    public function testItReturnsNullWhenFeatureHasNoParent(): void
    {
        $this->search_parent_feature_of_a_user_story = SearchParentFeatureOfAUserStoryStub::withoutParentFeature();
        $this->feature_has_user_stories_verifier     = FeatureHasUserStoriesVerifierBuilder::buildWithoutUserStories();
        self::assertNull($this->getFeature());
    }

    public function testItReturnsNullWhenFeatureIsNotValid(): void
    {
        $this->search_parent_feature_of_a_user_story = SearchParentFeatureOfAUserStoryStub::withoutParentFeature();
        $this->feature_has_user_stories_verifier     = FeatureHasUserStoriesVerifierBuilder::buildWithUserStories();
        self::assertNull($this->getFeature());
    }
}
