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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Presenter;

use Tracker_Artifact_Followup_Item;
use Tracker_Artifact_Presenter_FollowUpCommentsPresenter;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FollowUpCommentsPresenterTest extends TestCase
{
    public function testGetOnlyFollowUpWithContent(): void
    {
        $follow_up_can_see = $this->createMock(Tracker_Artifact_Followup_Item::class);
        $follow_up_can_see->expects(self::once())->method('diffToPrevious')->willReturn('');
        $follow_up_can_see->expects(self::once())->method('getFollowupContent')->willReturn('<div></div>');
        $follow_up_can_see->expects(self::once())->method('getId')->willReturn(123);
        $follow_up_can_see->expects(self::once())->method('getAvatar')->willReturn("<div class='tracker_artifact_followup_avatar'></div>");
        $follow_up_can_see->expects(self::once())->method('getUserLink')->willReturn('<span class="tracker_artifact_followup_title_user"></span>');
        $follow_up_can_see->expects(self::once())->method('getTimeAgo')->willReturn('<div></div>');

        $follow_up_no_content = $this->createMock(Tracker_Artifact_Followup_Item::class);
        $follow_up_no_content->expects(self::once())->method('diffToPrevious')->willReturn('');
        $follow_up_no_content->expects(self::once())->method('getFollowupContent')->willReturn('');

        $presenter = new Tracker_Artifact_Presenter_FollowUpCommentsPresenter([$follow_up_can_see, $follow_up_no_content], UserTestBuilder::buildWithDefaults());

        self::assertCount(1, $presenter->followups);
        self::assertEquals('<div></div>', $presenter->followups[0]['getFollowupContent']);
        self::assertEquals(123, $presenter->followups[0]['getId']);
        self::assertEquals("<div class='tracker_artifact_followup_avatar'></div>", $presenter->followups[0]['getAvatar']);
        self::assertEquals('<span class="tracker_artifact_followup_title_user"></span>', $presenter->followups[0]['getUserLink']);
    }
}
