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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Artifact_Followup_Item;

class FollowUpCommentsPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetOnlyFollowUpWithContent(): void
    {
        $follow_up_can_see = \Mockery::mock(Tracker_Artifact_Followup_Item::class);
        $follow_up_can_see->shouldReceive('diffToPrevious')->once()->andReturn('');
        $follow_up_can_see->shouldReceive('getFollowupContent')->once()->andReturn('<div></div>');
        $follow_up_can_see->shouldReceive('getId')->once()->andReturn(123);
        $follow_up_can_see->shouldReceive('getAvatar')->once()->andReturn("<div class='tracker_artifact_followup_avatar'></div>");
        $follow_up_can_see->shouldReceive('getUserLink')->once()->andReturn('<span class="tracker_artifact_followup_title_user"></span>');
        $follow_up_can_see->shouldReceive('getTimeAgo')->once()->andReturn('<div></div>');

        $follow_up_no_content = \Mockery::mock(Tracker_Artifact_Followup_Item::class);
        $follow_up_no_content->shouldReceive('diffToPrevious')->once()->andReturn('');
        $follow_up_no_content->shouldReceive('getFollowupContent')->once()->andReturn('');

        $presenter = new \Tracker_Artifact_Presenter_FollowUpCommentsPresenter([$follow_up_can_see, $follow_up_no_content], \Mockery::mock(\PFUser::class));

        self::assertCount(1, $presenter->followups);
        self::assertEquals('<div></div>', $presenter->followups[0]['getFollowupContent']);
        self::assertEquals(123, $presenter->followups[0]['getId']);
        self::assertEquals("<div class='tracker_artifact_followup_avatar'></div>", $presenter->followups[0]['getAvatar']);
        self::assertEquals('<span class="tracker_artifact_followup_title_user"></span>', $presenter->followups[0]['getUserLink']);
    }
}
