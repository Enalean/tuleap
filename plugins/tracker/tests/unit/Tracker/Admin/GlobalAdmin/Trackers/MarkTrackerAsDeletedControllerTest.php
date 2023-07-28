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
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\Trigger\TriggersDao;

final class MarkTrackerAsDeletedControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var EventManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $event_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReferenceManager
     */
    private $reference_manager;
    /**
     * @var MarkTrackerAsDeletedController
     */
    private $controller;
    private PFUser $user;
    private Project $project;
    /**
     * @var \CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GlobalAdminPermissionsChecker
     */
    private $permissions_checker;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&FieldDao
     */
    private $field_dao;

    private TriggersDao&\PHPUnit\Framework\MockObject\MockObject $triggers_dao;
    private \ProjectHistoryDao&\PHPUnit\Framework\MockObject\MockObject $project_history_dao;

    protected function setUp(): void
    {
        $token_provider            = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $this->tracker_factory     = $this->createMock(TrackerFactory::class);
        $this->event_manager       = $this->createMock(EventManager::class);
        $this->reference_manager   = $this->createMock(ReferenceManager::class);
        $this->permissions_checker = $this->createMock(GlobalAdminPermissionsChecker::class);
        $this->field_dao           = $this->createMock(FieldDao::class);
        $this->triggers_dao        = $this->createMock(TriggersDao::class);
        $this->project_history_dao = $this->createMock(\ProjectHistoryDao::class);

        $this->controller = new MarkTrackerAsDeletedController(
            $this->tracker_factory,
            $this->permissions_checker,
            $token_provider,
            $this->event_manager,
            $this->reference_manager,
            $this->field_dao,
            $this->triggers_dao,
            $this->project_history_dao,
        );

        $this->user    = UserTestBuilder::aUser()->build();
        $this->project = ProjectTestBuilder::aProject()->withId(42)->build();

        $this->csrf = $this->createMock(\CSRFSynchronizerToken::class);
        $token_provider->method('getCSRF')->willReturn($this->csrf);
    }

    public function testItThrowsExceptionIfTrackerCannotBeFound(): void
    {
        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->build(),
            $this->createMock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItThrowsExceptionIfTrackerIsDeleted(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withDeletionDate((new \DateTimeImmutable())->getTimestamp())->build();

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->willReturn($tracker);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->build(),
            $this->createMock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItThrowsExceptionIfUserIsNotAllowedToDeleteTracker(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withProject($this->project)->build();

        $this->permissions_checker
            ->expects(self::once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(false);

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->willReturn($tracker);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            $this->createMock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItThrowsExceptionIfTrackerCannotBeDeletedUsedInAnotherService(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('isDeleted')->willReturn(false);
        $tracker->method('getProject')->willReturn($this->project);
        $tracker->method('getInformationsFromOtherServicesAboutUsage')->willReturn(
            [
                'can_be_deleted' => false,
                'message'        => 'Boo',
            ],
        );

        $this->permissions_checker
            ->expects(self::once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(true);

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->willReturn($tracker);

        $this->csrf
            ->expects(self::once())
            ->method('check');

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            $this->createMock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItThrowsExceptionIfTrackerCannotBeDeletedSourceOfSharedField(): void
    {
        $tracker = $this->buildMockTracker();

        $this->permissions_checker
            ->expects(self::once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(true);

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->willReturn($tracker);

        $this->csrf
            ->expects(self::once())
            ->method('check');

        $this->field_dao
            ->expects(self::once())
            ->method('doesTrackerHaveSourceSharedFields')
            ->with(102)
            ->willReturn(true);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            $this->createMock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItThrowsExceptionIfTrackerCannotBeDeletedIfItsSourceOrTargetOfTriggers(): void
    {
        $tracker = $this->buildMockTracker();

        $this->permissions_checker
            ->expects(self::once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(true);

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->willReturn($tracker);

        $this->csrf
            ->expects(self::once())
            ->method('check');

        $this->field_dao
            ->expects(self::once())
            ->method('doesTrackerHaveSourceSharedFields')
            ->with(102)
            ->willReturn(false);

        $this->triggers_dao
            ->expects(self::once())
            ->method('isTrackerImplicatedInTriggers')
            ->with(102)
            ->willReturn(true);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            $this->createMock(BaseLayout::class),
            ['id' => '102']
        );
    }

    public function testItDisplaysAnErrorMessageIfTrackerCannotBeMarkedAsDeletedInDB(): void
    {
        $tracker = $this->buildMockTracker();

        $this->permissions_checker
            ->expects(self::once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(true);

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->willReturn($tracker);

        $this->csrf
            ->expects(self::once())
            ->method('check');

        $this->field_dao
            ->expects(self::once())
            ->method('doesTrackerHaveSourceSharedFields')
            ->with(102)
            ->willReturn(false);

        $this->triggers_dao
            ->expects(self::once())
            ->method('isTrackerImplicatedInTriggers')
            ->with(102)
            ->willReturn(false);

        $this->tracker_factory
            ->expects(self::once())
            ->method('markAsDeleted')
            ->with(102)
            ->willReturn(false);

        $layout = $this->createMock(BaseLayout::class);
        $layout
            ->expects(self::once())
            ->method('addFeedback')
            ->with(Feedback::ERROR, self::anything());

        $layout->expects(self::once())->method('redirect');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            $layout,
            ['id' => '102']
        );
    }

    public function testItMaksAsDeletedTheTracker(): void
    {
        $tracker = $this->buildMockTracker();

        $this->permissions_checker
            ->expects(self::once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(true);

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->willReturn($tracker);

        $this->csrf
            ->expects(self::once())
            ->method('check');

        $this->field_dao
            ->expects(self::once())
            ->method('doesTrackerHaveSourceSharedFields')
            ->with(102)
            ->willReturn(false);

        $this->triggers_dao
            ->expects(self::once())
            ->method('isTrackerImplicatedInTriggers')
            ->with(102)
            ->willReturn(false);

        $this->tracker_factory
            ->expects(self::once())
            ->method('markAsDeleted')
            ->with(102)
            ->willReturn(true);

        $this->event_manager
            ->expects(self::once())
            ->method('processEvent')
            ->with('tracker_event_delete_tracker', ['tracker_id' => 102]);

        $reference = $this->createMock(Reference::class);
        $this->reference_manager
            ->expects(self::once())
            ->method('loadReferenceFromKeywordAndNumArgs')
            ->with('story', 42, 1)
            ->willReturn($reference);
        $this->reference_manager
            ->expects(self::once())
            ->method('deleteReference')
            ->with($reference)
            ->willReturn(true);

        $GLOBALS['Language']
            ->expects(self::once())
            ->method('getText')
            ->with('project_reference', 't_r_deleted')
            ->willReturn('Corresponding Reference Pattern Deleted');

        $layout = $this->createMock(BaseLayout::class);
        $layout
            ->method('addFeedback')
            ->withConsecutive(
                [Feedback::INFO, 'Tracker User story has been successfully deleted'],
                [Feedback::INFO, self::anything(), \Codendi_HTMLPurifier::CONFIG_LIGHT],
                [Feedback::INFO, 'Corresponding Reference Pattern Deleted'],
            );

        $layout->expects(self::once())->method('redirect');

        $this->project_history_dao->method('addHistory');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            $layout,
            ['id' => '102']
        );
    }

    private function buildMockTracker(): Tracker&\PHPUnit\Framework\MockObject\MockObject
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(102);
        $tracker->method('getName')->willReturn('User story');
        $tracker->method('getItemName')->willReturn('Story');
        $tracker->method('isDeleted')->willReturn(false);
        $tracker->method('getProject')->willReturn($this->project);
        $tracker->method('getInformationsFromOtherServicesAboutUsage')->willReturn(
            [
                'can_be_deleted' => true,
            ],
        );
        return $tracker;
    }
}
