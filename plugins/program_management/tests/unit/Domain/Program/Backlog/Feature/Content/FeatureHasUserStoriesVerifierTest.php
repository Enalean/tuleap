<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content;

use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserStoryIsVisibleStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeatureHasUserStoriesVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_USER_STORY_ID  = 236;
    private const SECOND_USER_STORY_ID = 111;
    private SearchChildrenOfFeatureStub $search_children_of_feature;
    private VerifyUserStoryIsVisibleStub $visibility_verifier;

    protected function setUp(): void
    {
        $this->search_children_of_feature = SearchChildrenOfFeatureStub::withUserStoryIds(
            self::FIRST_USER_STORY_ID,
            self::SECOND_USER_STORY_ID
        );
        $this->visibility_verifier        = VerifyUserStoryIsVisibleStub::withAlwaysVisibleUserStories();
    }

    private function hasAtLeastOneStory(): bool
    {
        $user    = UserIdentifierStub::buildGenericUser();
        $feature = FeatureIdentifierBuilder::withId(101);

        $verifier = new FeatureHasUserStoriesVerifier(
            $this->search_children_of_feature,
            $this->visibility_verifier
        );
        return $verifier->hasStoryLinked($feature, $user);
    }

    public function testHasALinkedUserStoryToFeature(): void
    {
        self::assertTrue($this->hasAtLeastOneStory());
    }

    public function testHasStoryLinkedWhenUserCanSeeAtLeastOneUserStory(): void
    {
        $this->visibility_verifier = VerifyUserStoryIsVisibleStub::withVisibleIds(self::SECOND_USER_STORY_ID);
        self::assertTrue($this->hasAtLeastOneStory());
    }

    public function testHasNotALinkedUserStoryToFeature(): void
    {
        $this->search_children_of_feature = SearchChildrenOfFeatureStub::withoutUserStories();
        self::assertFalse($this->hasAtLeastOneStory());
    }

    public function testHasNotALinkedUserStoryToFeatureThatUserCanSee(): void
    {
        $this->visibility_verifier = VerifyUserStoryIsVisibleStub::withNoVisibleUserStory();
        self::assertFalse($this->hasAtLeastOneStory());
    }
}
