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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Backlog;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\UserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Tests\Builder\FeatureOfUserStoryRetrieverBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryURIStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchFeaturesStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchUserStoryPlannedInIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsOpenStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserStoryIsVisibleStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BacklogSearcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID     = 59;
    private const FIRST_USER_STORY_ID      = 316;
    private const FIRST_TITLE              = 'intuitional refinage';
    private const FIRST_TRACKER_SHORTNAME  = 'requirement';
    private const FIRST_TRACKER_ID         = 69;
    private const FIRST_COLOR              = 'daphne-blue';
    private const SECOND_USER_STORY_ID     = 245;
    private const SECOND_TITLE             = 'sylvanity monolatrist';
    private const SECOND_TRACKER_SHORTNAME = 'story';
    private const SECOND_TRACKER_ID        = 91;
    private const SECOND_COLOR             = 'plum-crazy';
    private const THIRD_USER_STORY_ID      = 290;
    private const FOURTH_USER_STORY_ID     = 832;
    private const EXPECTED_URI             = '/plugins/tracker/?aid=';
    private VerifyIsProgramIncrementStub $program_increment_verifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withValidProgramIncrement();
    }

    /**
     * @return UserStory[]
     */
    private function searchBacklog(): array
    {
        $searcher = new BacklogSearcher(
            $this->program_increment_verifier,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            SearchFeaturesStub::withFeatureIds(930, 380),
            VerifyFeatureIsVisibleStub::withAlwaysVisibleFeatures(),
            SearchChildrenOfFeatureStub::withSuccessiveIds([
                [self::FIRST_USER_STORY_ID, self::THIRD_USER_STORY_ID],
                [self::SECOND_USER_STORY_ID, self::FOURTH_USER_STORY_ID],
            ]),
            VerifyUserStoryIsVisibleStub::withAlwaysVisibleUserStories(),
            SearchIterationsStub::withIterations([
                [ 'id' => 779, 'chanegset_id' => 1],
                [ 'id' => 684, 'chanegset_id' => 2],
            ]),
            SearchMirroredTimeboxesStub::withSuccessiveIds([[223], [722]]),
            SearchUserStoryPlannedInIterationStub::withSuccessiveIds([
                [self::THIRD_USER_STORY_ID],
                [self::FOURTH_USER_STORY_ID],
            ]),
            RetrieveUserStoryTitleStub::withSuccessiveValues(self::FIRST_TITLE, self::SECOND_TITLE),
            new RetrieveUserStoryURIStub(),
            RetrieveUserStoryCrossRefStub::withSuccessiveShortNames(
                self::FIRST_TRACKER_SHORTNAME,
                self::SECOND_TRACKER_SHORTNAME
            ),
            VerifyIsOpenStub::withOpen(),
            RetrieveBackgroundColorStub::withSuccessiveColors(self::FIRST_COLOR, self::SECOND_COLOR),
            RetrieveTrackerFromUserStoryStub::withSuccessiveIds(self::FIRST_TRACKER_ID, self::SECOND_TRACKER_ID),
            FeatureOfUserStoryRetrieverBuilder::withSuccessiveFeatures('Feature 1', 'Feature 2')
        );

        return $searcher->searchUnplannedUserStories(
            self::PROGRAM_INCREMENT_ID,
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItReturnsUserStoriesThatAreNotPlannedInAnyMirrorIteration(): void
    {
        $user_stories = $this->searchBacklog();
        self::assertCount(2, $user_stories);
        [$first_story, $second_story] = $user_stories;

        self::assertSame(self::FIRST_USER_STORY_ID, $first_story->user_story_identifier->getId());
        self::assertSame(self::FIRST_TITLE, $first_story->title);
        self::assertSame('requirement #' . self::FIRST_USER_STORY_ID, $first_story->cross_ref);
        self::assertSame(self::EXPECTED_URI . self::FIRST_USER_STORY_ID, $first_story->uri);
        self::assertSame(self::FIRST_COLOR, $first_story->background_color->getBackgroundColorName());
        self::assertSame(self::FIRST_TRACKER_ID, $first_story->tracker_identifier->getId());
        self::assertSame('Feature 1', $first_story->feature?->title);
        self::assertTrue($first_story->is_open);

        self::assertSame(self::SECOND_USER_STORY_ID, $second_story->user_story_identifier->getId());
        self::assertSame(self::SECOND_TITLE, $second_story->title);
        self::assertSame('story #' . self::SECOND_USER_STORY_ID, $second_story->cross_ref);
        self::assertSame(self::EXPECTED_URI . self::SECOND_USER_STORY_ID, $second_story->uri);
        self::assertSame(self::SECOND_COLOR, $second_story->background_color->getBackgroundColorName());
        self::assertSame(self::SECOND_TRACKER_ID, $second_story->tracker_identifier->getId());
        self::assertSame('Feature 2', $second_story->feature?->title);
        self::assertTrue($second_story->is_open);
    }

    public function testItThrowsIfGivenIdIsNotAProgramIncrement(): void
    {
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withNotProgramIncrement();

        $this->expectException(ProgramIncrementNotFoundException::class);
        $this->searchBacklog();
    }
}
