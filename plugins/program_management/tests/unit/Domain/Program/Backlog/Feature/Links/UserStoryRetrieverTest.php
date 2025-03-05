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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links;

use Tuleap\ProgramManagement\Tests\Stub\CheckIsValidFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryURIStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsOpenStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserStoryIsVisibleStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserStoryRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID        = 56;
    private const USER_STORY_ONE_ID = 125;
    private const FIRST_COLOR       = 'teddy-brown';
    private const USER_STORY_TWO_ID = 126;
    private const SECOND_COLOR      = 'army-green';

    private function getRetriever(): UserStoryRetriever
    {
        return new UserStoryRetriever(
            SearchChildrenOfFeatureStub::withUserStoryIds(self::USER_STORY_ONE_ID, self::USER_STORY_TWO_ID),
            CheckIsValidFeatureStub::withAlwaysValidFeatures(),
            RetrieveBackgroundColorStub::withSuccessiveColors(self::FIRST_COLOR, self::SECOND_COLOR),
            RetrieveUserStoryTitleStub::withSuccessiveValues('Title', 'Other title'),
            new RetrieveUserStoryURIStub(),
            RetrieveUserStoryCrossRefStub::withShortname('story'),
            VerifyIsOpenStub::withOpen(),
            RetrieveTrackerFromUserStoryStub::withId(self::TRACKER_ID),
            VerifyUserStoryIsVisibleStub::withAlwaysVisibleUserStories(),
        );
    }

    public function testItBuildsUserStories(): void
    {
        $children = $this->getRetriever()->retrieveStories(10, UserIdentifierStub::buildGenericUser());

        [$first_story, $second_story] = $children;
        self::assertCount(2, $children);

        self::assertSame(self::USER_STORY_ONE_ID, $first_story->user_story_identifier->getId());
        self::assertSame('Title', $first_story->title);
        self::assertSame('/plugins/tracker/?aid=' . self::USER_STORY_ONE_ID, $first_story->uri);
        self::assertSame('story #' . self::USER_STORY_ONE_ID, $first_story->cross_ref);
        self::assertTrue($first_story->is_open);
        self::assertSame(self::FIRST_COLOR, $first_story->background_color->getBackgroundColorName());
        self::assertSame(self::TRACKER_ID, $first_story->tracker_identifier->getId());

        self::assertSame(self::USER_STORY_TWO_ID, $second_story->user_story_identifier->getId());
        self::assertSame('Other title', $second_story->title);
        self::assertSame('/plugins/tracker/?aid=' . self::USER_STORY_TWO_ID, $second_story->uri);
        self::assertSame('story #' . self::USER_STORY_TWO_ID, $second_story->cross_ref);
        self::assertTrue($second_story->is_open);
        self::assertSame(self::SECOND_COLOR, $second_story->background_color->getBackgroundColorName());
        self::assertSame(self::TRACKER_ID, $second_story->tracker_identifier->getId());
    }
}
