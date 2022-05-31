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

use EventManager;
use Feedback;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Project;
use Reference;
use ReferenceManager;
use Tracker;
use TrackerFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\Workflow\Trigger\TriggersDao;

class MarkTrackerAsDeletedControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var EventManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ReferenceManager
     */
    private $reference_manager;
    /**
     * @var MarkTrackerAsDeletedController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var \CSRFSynchronizerToken|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $csrf;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GlobalAdminPermissionsChecker
     */
    private $permissions_checker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FieldDao
     */
    private $field_dao;

    private TriggersDao|Mockery\LegacyMockInterface|Mockery\MockInterface $triggers_dao;

    protected function setUp(): void
    {
        $token_provider            = Mockery::mock(CSRFSynchronizerTokenProvider::class);
        $this->tracker_factory     = Mockery::mock(TrackerFactory::class);
        $this->event_manager       = Mockery::mock(EventManager::class);
        $this->reference_manager   = Mockery::mock(ReferenceManager::class);
        $this->permissions_checker = Mockery::mock(GlobalAdminPermissionsChecker::class);
        $this->field_dao           = Mockery::mock(FieldDao::class);
        $this->triggers_dao        = Mockery::mock(TriggersDao::class);

        $this->controller = new MarkTrackerAsDeletedController(
            $this->tracker_factory,
            $this->permissions_checker,
            $token_provider,
            $this->event_manager,
            $this->reference_manager,
            $this->field_dao,
            $this->triggers_dao
        );

        $this->user    = Mockery::mock(PFUser::class);
        $this->project = Mockery::mock(Project::class)->shouldReceive(['getID' => 42])->getMock();

        $this->csrf = Mockery::mock(\CSRFSynchronizerToken::class);
        $token_provider->shouldReceive('getCSRF')->andReturn($this->csrf);
    }

    public function testItThrowsExceptionIfTrackerCannotBeFound(): void
    {
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->once()
            ->andReturnNull();

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->build(),
            Mockery::mock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItThrowsExceptionIfTrackerIsDeleted(): void
    {
        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(['isDeleted' => true])
            ->getMock();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->once()
            ->andReturn($tracker);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->build(),
            Mockery::mock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItThrowsExceptionIfUserIsNotAllowedToDeleteTracker(): void
    {
        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'isDeleted'  => false,
                    'getProject' => $this->project,
                ]
            )->getMock();

        $this->permissions_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->once()
            ->andReturnFalse();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->once()
            ->andReturn($tracker);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            Mockery::mock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItThrowsExceptionIfTrackerCannotBeDeletedUsedInAnotherService(): void
    {
        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'isDeleted'                                  => false,
                    'getProject'                                 => $this->project,
                    'getInformationsFromOtherServicesAboutUsage' => [
                        'can_be_deleted' => false,
                        'message'        => 'Boo',
                    ],
                ]
            )->getMock();

        $this->permissions_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->once()
            ->andReturnTrue();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->once()
            ->andReturn($tracker);

        $this->csrf
            ->shouldReceive('check')
            ->once();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            Mockery::mock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItThrowsExceptionIfTrackerCannotBeDeletedSourceOfSharedField(): void
    {
        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                                      => 102,
                    'getName'                                    => 'User story',
                    'isDeleted'                                  => false,
                    'getProject'                                 => $this->project,
                    'getInformationsFromOtherServicesAboutUsage' => [
                        'can_be_deleted' => true,
                    ],
                ]
            )->getMock();

        $this->permissions_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->once()
            ->andReturnTrue();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->once()
            ->andReturn($tracker);

        $this->csrf
            ->shouldReceive('check')
            ->once();

        $this->field_dao->shouldReceive('doesTrackerHaveSourceSharedFields')
            ->once()
            ->with(102)
            ->andReturnTrue();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            Mockery::mock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItThrowsExceptionIfTrackerCannotBeDeletedIfItsSourceOrTargetOfTriggers(): void
    {
        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                                      => 102,
                    'getName'                                    => 'User story',
                    'isDeleted'                                  => false,
                    'getProject'                                 => $this->project,
                    'getInformationsFromOtherServicesAboutUsage' => [
                        'can_be_deleted' => true,
                    ],
                ]
            )->getMock();

        $this->permissions_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->once()
            ->andReturnTrue();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->once()
            ->andReturn($tracker);

        $this->csrf
            ->shouldReceive('check')
            ->once();

        $this->field_dao->shouldReceive('doesTrackerHaveSourceSharedFields')
            ->once()
            ->with(102)
            ->andReturnFalse();

        $this->triggers_dao->shouldReceive('isTrackerImplicatedInTriggers')
            ->once()
            ->with(102)
            ->andReturnTrue();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            Mockery::mock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItDisplaysAnErrorMessageIfTrackerCannotBeMarkedAsDeletedInDB(): void
    {
        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                                      => 102,
                    'getName'                                    => 'User story',
                    'isDeleted'                                  => false,
                    'getProject'                                 => $this->project,
                    'getInformationsFromOtherServicesAboutUsage' => [
                        'can_be_deleted' => true,
                    ],
                ]
            )->getMock();

        $this->permissions_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->once()
            ->andReturnTrue();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->once()
            ->andReturn($tracker);

        $this->csrf
            ->shouldReceive('check')
            ->once();

        $this->field_dao->shouldReceive('doesTrackerHaveSourceSharedFields')
            ->once()
            ->with(102)
            ->andReturnFalse();

        $this->triggers_dao->shouldReceive('isTrackerImplicatedInTriggers')
            ->once()
            ->with(102)
            ->andReturnFalse();

        $this->tracker_factory
            ->shouldReceive('markAsDeleted')
            ->with(102)
            ->once()
            ->andReturnFalse();

        $layout = Mockery::mock(BaseLayout::class);
        $layout->shouldReceive('addFeedback')
            ->with(Feedback::ERROR, Mockery::type('string'))
            ->once();
        $layout->shouldReceive('redirect')->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            $layout,
            ['id' => '102']
        );
    }

    public function testItMaksAsDeletedTheTracker(): void
    {
        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                                      => 102,
                    'getName'                                    => 'User story',
                    'getItemName'                                => 'Story',
                    'isDeleted'                                  => false,
                    'getProject'                                 => $this->project,
                    'getInformationsFromOtherServicesAboutUsage' => [
                        'can_be_deleted' => true,
                    ],
                ]
            )->getMock();

        $this->permissions_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->once()
            ->andReturnTrue();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->once()
            ->andReturn($tracker);

        $this->csrf
            ->shouldReceive('check')
            ->once();

        $this->field_dao->shouldReceive('doesTrackerHaveSourceSharedFields')
            ->once()
            ->with(102)
            ->andReturnFalse();

        $this->triggers_dao->shouldReceive('isTrackerImplicatedInTriggers')
            ->once()
            ->with(102)
            ->andReturnFalse();

        $this->tracker_factory
            ->shouldReceive('markAsDeleted')
            ->with(102)
            ->once()
            ->andReturnTrue();

        $this->event_manager
            ->shouldReceive('processEvent')
            ->with('tracker_event_delete_tracker', ['tracker_id' => 102])
            ->once();

        $reference = Mockery::mock(Reference::class);
        $this->reference_manager
            ->shouldReceive('loadReferenceFromKeywordAndNumArgs')
            ->with('story', 42, 1)
            ->once()
            ->andReturn($reference);
        $this->reference_manager
            ->shouldReceive('deleteReference')
            ->with($reference)
            ->once()
            ->andReturnTrue();

        $GLOBALS['Language']
            ->expects(self::once())
            ->method('getText')
            ->with('project_reference', 't_r_deleted')
            ->willReturn('Corresponding Reference Pattern Deleted');

        $layout = Mockery::mock(BaseLayout::class);
        $layout->shouldReceive('addFeedback')
            ->with(Feedback::INFO, 'Tracker User story has been successfully deleted')
            ->once();
        $layout->shouldReceive('addFeedback')
            ->with(Feedback::INFO, Mockery::type('string'), \Codendi_HTMLPurifier::CONFIG_LIGHT)
            ->once();
        $layout->shouldReceive('addFeedback')
            ->with(Feedback::INFO, 'Corresponding Reference Pattern Deleted')
            ->once();
        $layout->shouldReceive('redirect')->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            $layout,
            ['id' => '102']
        );
    }
}
