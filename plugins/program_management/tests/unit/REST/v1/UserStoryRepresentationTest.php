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

namespace Tuleap\ProgramManagement\REST\v1;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\UserStory;
use Tuleap\ProgramManagement\Tests\Builder\UserStoryIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryTitleStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStoryURIStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsOpenStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserStoryRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_STORY_ID     = 993;
    private const TITLE             = 'devorative pilocarpine';
    private const URI               = '/plugins/tracker/?aid=' . self::USER_STORY_ID;
    private const BACKGROUND_COLOR  = 'coral-pink';
    private const TRACKER_ID        = 36;
    private const TRACKER_SHORTNAME = 'user_story';
    private const PROJECT_ID        = 280;

    private function getRepresentation(): UserStoryRepresentation
    {
        $team_project  = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $story_tracker = TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->withProject($team_project)
            ->build();

        return UserStoryRepresentation::build(
            RetrieveFullTrackerStub::withTracker($story_tracker),
            UserStory::build(
                RetrieveUserStoryTitleStub::withValue(self::TITLE),
                new RetrieveUserStoryURIStub(),
                RetrieveUserStoryCrossRefStub::withShortname(self::TRACKER_SHORTNAME),
                VerifyIsOpenStub::withOpen(),
                RetrieveBackgroundColorStub::withColor(self::BACKGROUND_COLOR),
                RetrieveTrackerFromUserStoryStub::withId(self::TRACKER_ID),
                UserStoryIdentifierBuilder::withId(self::USER_STORY_ID),
                UserIdentifierStub::buildGenericUser()
            )
        );
    }

    public function testItBuildsFromUserStory(): void
    {
        $representation = $this->getRepresentation();
        self::assertSame(self::USER_STORY_ID, $representation->id);
        self::assertSame(self::TITLE, $representation->title);
        self::assertSame(self::URI, $representation->uri);
        self::assertSame('user_story #' . self::USER_STORY_ID, $representation->xref);
        self::assertTrue($representation->is_open);
        self::assertSame(self::BACKGROUND_COLOR, $representation->background_color);
        self::assertSame(self::TRACKER_ID, $representation->tracker->id);
        self::assertSame(self::PROJECT_ID, $representation->project->id);
    }
}
