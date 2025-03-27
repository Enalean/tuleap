<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\GlobalAdmin\Trackers;

use CSRFSynchronizerToken;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use ProjectManager;
use TrackerFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\PromotedTrackerDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Service\PromotedTrackerConfigurationCheckerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PromoteTrackersControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TrackerFactory&MockObject $tracker_factory;
    private PromotedTrackerDao&MockObject $in_new_dropdown_dao;
    private CSRFSynchronizerTokenProvider&MockObject $token_provider;
    private PromoteTrackersController $controller;
    private CSRFSynchronizerToken&MockObject $csrf;
    private ProjectHistoryDao&MockObject $history_dao;
    private GlobalAdminPermissionsChecker&MockObject $perms_checker;
    private PFUser $user;

    protected function setUp(): void
    {
        $project_manager           = $this->createMock(ProjectManager::class);
        $this->perms_checker       = $this->createMock(GlobalAdminPermissionsChecker::class);
        $this->tracker_factory     = $this->createMock(TrackerFactory::class);
        $this->in_new_dropdown_dao = $this->createMock(PromotedTrackerDao::class);
        $this->token_provider      = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $this->history_dao         = $this->createMock(\ProjectHistoryDao::class);

        $this->controller = new PromoteTrackersController(
            $project_manager,
            $this->perms_checker,
            $this->tracker_factory,
            $this->in_new_dropdown_dao,
            $this->token_provider,
            $this->history_dao,
            PromotedTrackerConfigurationCheckerStub::withAllowedProject(),
        );

        $this->user = UserTestBuilder::buildWithDefaults();

        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        $project_manager->method('getProject')->with('102')->willReturn($project);

        $this->csrf = $this->createMock(\CSRFSynchronizerToken::class);
        $this->token_provider->method('getCSRF')->willReturn($this->csrf);
    }

    public function testItRaisesExceptionIfUserHasNoRights(): void
    {
        $this->perms_checker
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            $this->createMock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItRaisesExceptionIfTrackerDoesNotExists(): void
    {
        $this->perms_checker
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->willReturn(true);

        $this->csrf
            ->expects($this->once())
            ->method('check');

        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->with(13)
            ->willReturn(null);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withParam('tracker_id', '13')
                ->withUser($this->user)
                ->build(),
            $this->createMock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItRaisesExceptionIfTrackerIsDeleted(): void
    {
        $this->perms_checker
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->willReturn(true);

        $this->csrf
            ->expects($this->once())
            ->method('check');

        $tracker = TrackerTestBuilder::aTracker()->withDeletionDate(1234567890)->build();
        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->with(13)
            ->willReturn($tracker);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withParam('tracker_id', '13')
                ->withUser($this->user)
                ->build(),
            $this->createMock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItRaisesExceptionIfTrackerBelongsToAnotherProject(): void
    {
        $this->perms_checker
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->willReturn(true);

        $this->csrf
            ->expects($this->once())
            ->method('check');

        $tracker = TrackerTestBuilder::aTracker()->build();
        $this->tracker_factory
            ->method('getTrackerById')
            ->with(13)
            ->willReturn($tracker);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withParam('tracker_id', '13')
                ->withUser($this->user)
                ->build(),
            $this->createMock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItPromotesTheTracker(): void
    {
        $this->perms_checker
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->willReturn(true);

        $this->csrf
            ->expects($this->once())
            ->method('check');

        $tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(102)->build())
            ->withId(13)
            ->build();
        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->with(13)
            ->willReturn($tracker);

        $this->in_new_dropdown_dao
            ->expects($this->once())
            ->method('insert')
            ->with(13);

        $this->history_dao
            ->expects($this->once())
            ->method('groupAddHistory');

        $layout = $this->createMock(BaseLayout::class);
        $layout->method('addFeedback');
        $layout->method('redirect');

        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withParam('tracker_id', '13')
                ->withParam('is_promoted', 'on')
                ->withUser($this->user)
                ->build(),
            $layout,
            ['id' => '102']
        );
    }

    public function testItRemovesPromotion(): void
    {
        $this->perms_checker
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->willReturn(true);

        $this->csrf
            ->expects($this->once())
            ->method('check');

        $tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(102)->build())
            ->withId(13)
            ->build();
        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->with(13)
            ->willReturn($tracker);

        $this->in_new_dropdown_dao
            ->expects($this->once())
            ->method('delete')
            ->with(13);

        $this->history_dao
            ->expects($this->once())
            ->method('groupAddHistory');

        $layout = $this->createMock(BaseLayout::class);
        $layout->method('addFeedback');
        $layout->method('redirect');

        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withParam('tracker_id', '13')
                ->withUser($this->user)
                ->build(),
            $layout,
            ['id' => '102']
        );
    }
}
