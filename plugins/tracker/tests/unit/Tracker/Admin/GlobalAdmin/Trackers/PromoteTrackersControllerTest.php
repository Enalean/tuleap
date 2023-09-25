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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use ProjectManager;
use TrackerFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\PromotedTrackerDao;
use Tuleap\Tracker\Test\Stub\Tracker\Service\PromotedTrackerConfigurationCheckerStub;

class PromoteTrackersControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PromotedTrackerDao
     */
    private $in_new_dropdown_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CSRFSynchronizerTokenProvider
     */
    private $token_provider;
    /**
     * @var PromoteTrackersController
     */
    private $controller;
    /**
     * @var \CSRFSynchronizerToken|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $csrf;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GlobalAdminPermissionsChecker
     */
    private $perms_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $project_manager           = Mockery::mock(ProjectManager::class);
        $this->perms_checker       = Mockery::mock(GlobalAdminPermissionsChecker::class);
        $this->tracker_factory     = Mockery::mock(TrackerFactory::class);
        $this->in_new_dropdown_dao = Mockery::mock(PromotedTrackerDao::class);
        $this->token_provider      = Mockery::mock(CSRFSynchronizerTokenProvider::class);
        $this->history_dao         = Mockery::mock(\ProjectHistoryDao::class);

        $this->controller = new PromoteTrackersController(
            $project_manager,
            $this->perms_checker,
            $this->tracker_factory,
            $this->in_new_dropdown_dao,
            $this->token_provider,
            $this->history_dao,
            PromotedTrackerConfigurationCheckerStub::withAllowedProject(),
        );

        $this->user = Mockery::mock(PFUser::class);

        $project = Mockery::mock(\Project::class)
            ->shouldReceive(['getID' => 102])
            ->getMock();

        $project_manager->shouldReceive('getProject')->with('102')->andReturn($project);

        $this->csrf = Mockery::mock(\CSRFSynchronizerToken::class);
        $this->token_provider->shouldReceive('getCSRF')->andReturn($this->csrf);
    }

    public function testItRaisesExceptionIfUserHasNoRights(): void
    {
        $this->perms_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->once()
            ->andReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            Mockery::mock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItRaisesExceptionIfTrackerDoesNotExists(): void
    {
        $this->perms_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->once()
            ->andReturn(true);

        $this->csrf
            ->shouldReceive('check')
            ->once();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(13)
            ->once()
            ->andReturnNull();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withParam('tracker_id', '13')
                ->withUser($this->user)
                ->build(),
            Mockery::mock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItRaisesExceptionIfTrackerIsDeleted(): void
    {
        $this->perms_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->once()
            ->andReturn(true);

        $this->csrf
            ->shouldReceive('check')
            ->once();

        $tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive(['isDeleted' => true])
            ->getMock();
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(13)
            ->once()
            ->andReturn($tracker);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withParam('tracker_id', '13')
                ->withUser($this->user)
                ->build(),
            Mockery::mock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItRaisesExceptionIfTrackerBelongsToAnotherProject(): void
    {
        $this->perms_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->once()
            ->andReturn(true);

        $this->csrf
            ->shouldReceive('check')
            ->once();

        $tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive(['isDeleted' => false, 'getGroupId' => 101])
            ->getMock();
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(13)
            ->once()
            ->andReturn($tracker);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withParam('tracker_id', '13')
                ->withUser($this->user)
                ->build(),
            Mockery::mock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItPromotesTheTracker(): void
    {
        $this->perms_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->once()
            ->andReturn(true);

        $this->csrf
            ->shouldReceive('check')
            ->once();

        $tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive(['isDeleted' => false, 'getGroupId' => 102, 'getId' => 13, 'getName' => 'Bugs'])
            ->getMock();
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(13)
            ->once()
            ->andReturn($tracker);

        $this->in_new_dropdown_dao
            ->shouldReceive('insert')
            ->with(13)
            ->once();

        $this->history_dao
            ->shouldReceive('groupAddHistory')
            ->once();

        $layout = Mockery::mock(BaseLayout::class);
        $layout->shouldReceive('addFeedback');
        $layout->shouldReceive('redirect');

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
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->once()
            ->andReturn(true);

        $this->csrf
            ->shouldReceive('check')
            ->once();

        $tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive(['isDeleted' => false, 'getGroupId' => 102, 'getId' => 13, 'getName' => 'Bugs'])
            ->getMock();
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(13)
            ->once()
            ->andReturn($tracker);

        $this->in_new_dropdown_dao
            ->shouldReceive('delete')
            ->with(13)
            ->once();

        $this->history_dao
            ->shouldReceive('groupAddHistory')
            ->once();

        $layout = Mockery::mock(BaseLayout::class);
        $layout->shouldReceive('addFeedback');
        $layout->shouldReceive('redirect');

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
