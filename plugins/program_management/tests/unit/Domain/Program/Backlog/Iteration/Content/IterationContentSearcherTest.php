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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveTrackerFromUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\VerifyIsOpen;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirroredTimeboxes;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\FeatureOfUserStoryRetrieverBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryURIStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchUserStoryPlannedInIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsOpenStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserStoryIsVisibleStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationContentSearcherTest extends TestCase
{
    private const USER_STORY_ONE_ID = 555;
    private const FIRST_COLOR       = 'sherwood-green';
    private const USER_STORY_TWO_ID = 666;
    private const SECOND_COLOR      = 'deep-blue';
    private const ITERATION_ID      = 777;

    private VerifyIsIterationStub $verify_is_iteration;
    private VerifyIsVisibleArtifact $is_visible_artifact;
    private SearchMirroredTimeboxes $search_mirrored_timeboxes;
    private SearchUserStoryPlannedInIteration $search_user_story_planned_in_iteration;
    private RetrieveUserStoryTitle $retrieve_title_value;
    private RetrieveUserStoryURI $retrieve_uri;
    private RetrieveUserStoryCrossRef $retrieve_cross_ref;
    private VerifyIsOpen $retrieve_is_open;
    private RetrieveBackgroundColor $retrieve_background_color;
    private RetrieveTrackerFromUserStory $retrieve_tracker_id;
    private UserIdentifier $user_identifier;

    protected function setUp(): void
    {
        $this->verify_is_iteration                    = VerifyIsIterationStub::withValidIteration();
        $this->is_visible_artifact                    = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->search_mirrored_timeboxes              = SearchMirroredTimeboxesStub::withIds(self::ITERATION_ID);
        $this->search_user_story_planned_in_iteration = SearchUserStoryPlannedInIterationStub::withUserStoryIds(
            self::USER_STORY_ONE_ID,
            self::USER_STORY_TWO_ID
        );

        $this->retrieve_title_value      = RetrieveUserStoryTitleStub::withSuccessiveValues(
            'User Story 1',
            'User Story 2'
        );
        $this->retrieve_uri              = new RetrieveUserStoryURIStub();
        $this->retrieve_cross_ref        = RetrieveUserStoryCrossRefStub::withShortname('Story');
        $this->retrieve_is_open          = VerifyIsOpenStub::withOpen();
        $this->retrieve_background_color = RetrieveBackgroundColorStub::withSuccessiveColors(self::FIRST_COLOR, self::SECOND_COLOR);
        $this->retrieve_tracker_id       = RetrieveTrackerFromUserStoryStub::withDefault();

        $this->user_identifier = UserIdentifierStub::buildGenericUser();
    }

    private function getSearcher(): IterationContentSearcher
    {
        return new IterationContentSearcher(
            $this->verify_is_iteration,
            $this->is_visible_artifact,
            $this->search_user_story_planned_in_iteration,
            VerifyUserStoryIsVisibleStub::withAlwaysVisibleUserStories(),
            $this->retrieve_title_value,
            $this->retrieve_uri,
            $this->retrieve_cross_ref,
            $this->retrieve_is_open,
            $this->retrieve_background_color,
            $this->retrieve_tracker_id,
            $this->search_mirrored_timeboxes,
            FeatureOfUserStoryRetrieverBuilder::withSuccessiveFeatures('feature', 'feature 2')
        );
    }

    public function testItThrowsAnErrorWhenIterationIsNotFound(): void
    {
        $this->verify_is_iteration = VerifyIsIterationStub::withNotIteration();

        $this->expectException(IterationNotFoundException::class);
        $this->getSearcher()->retrievePlannedUserStories(self::ITERATION_ID, $this->user_identifier);
    }

    public function testItBuildsUserStory(): void
    {
        $content = $this->getSearcher()->retrievePlannedUserStories(self::ITERATION_ID, $this->user_identifier);
        self::assertCount(2, $content);

        [$first_user_story, $second_user_story] = $content;
        self::assertSame(self::USER_STORY_ONE_ID, $first_user_story->user_story_identifier->getId());
        self::assertSame(self::FIRST_COLOR, $first_user_story->background_color->getBackgroundColorName());

        self::assertSame(self::USER_STORY_TWO_ID, $second_user_story->user_story_identifier->getId());
        self::assertSame(self::SECOND_COLOR, $second_user_story->background_color->getBackgroundColorName());
    }
}
