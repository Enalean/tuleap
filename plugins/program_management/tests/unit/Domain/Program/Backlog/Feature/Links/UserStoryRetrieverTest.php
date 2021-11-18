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

use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryURIStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsOpenStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsPlannableStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

final class UserStoryRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID    = 56;
    private const USER_STORY_ID = 125;

    protected function getRetriever(): UserStoryRetriever
    {
        return new UserStoryRetriever(
            SearchChildrenOfFeatureStub::withChildren([['children_id' => self::USER_STORY_ID]]),
            VerifyIsPlannableStub::buildPlannableElement(),
            RetrieveBackgroundColorStub::withDefaults(),
            VerifyFeatureIsVisibleStub::buildVisibleFeature(),
            RetrieveUserStoryTitleStub::withValue('Title'),
            RetrieveUserStoryURIStub::withId(self::USER_STORY_ID),
            RetrieveUserStoryCrossRefStub::withValues('story', self::USER_STORY_ID),
            VerifyIsOpenStub::withOpen(),
            RetrieveTrackerFromUserStoryStub::withId(self::TRACKER_ID),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            RetrieveTrackerOfArtifactStub::withIds(self::TRACKER_ID)
        );
    }

    public function testItBuildsUserStories(): void
    {
        $children = $this->getRetriever()->retrieveStories(10, UserIdentifierStub::buildGenericUser());

        self::assertCount(1, $children);

        self::assertEquals(self::USER_STORY_ID, $children[0]->user_story_identifier->getId());
        self::assertEquals('Title', $children[0]->title);
        self::assertEquals('/plugins/tracker/?aid=' . self::USER_STORY_ID, $children[0]->uri);
        self::assertEquals('story #' . self::USER_STORY_ID, $children[0]->cross_ref);
        self::assertEquals(true, $children[0]->is_open);
        self::assertEquals("lake-placid-blue", $children[0]->background_color->getBackgroundColorName());
        self::assertEquals(self::TRACKER_ID, $children[0]->tracker_identifier->getId());
    }
}
