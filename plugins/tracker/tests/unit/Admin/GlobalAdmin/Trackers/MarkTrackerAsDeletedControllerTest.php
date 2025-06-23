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

use Codendi_HTMLPurifier;
use DateTimeImmutable;
use EventManager;
use Feedback;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectHistoryDao;
use Reference;
use ReferenceManager;
use TrackerFactory;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Tracker\Widget\SearchWidgetsByTrackerId;
use Tuleap\Tracker\Workflow\Trigger\TriggersDao;

#[DisableReturnValueGenerationForTestDoubles]
final class MarkTrackerAsDeletedControllerTest extends TestCase
{
    use GlobalLanguageMock;

    private TrackerFactory&MockObject $tracker_factory;
    private EventManager&MockObject $event_manager;
    private ReferenceManager&MockObject $reference_manager;
    private MarkTrackerAsDeletedController $controller;
    private PFUser $user;
    private Project $project;
    private GlobalAdminPermissionsChecker&MockObject $permissions_checker;
    private FieldDao&MockObject $field_dao;
    private TriggersDao&MockObject $triggers_dao;
    private ProjectHistoryDao&MockObject $project_history_dao;
    private SearchWidgetsByTrackerId&MockObject $widgets_retriever;
    private DashboardWidgetDao&MockObject $dashboard_widget_dao;

    protected function setUp(): void
    {
        $token_provider             = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $this->tracker_factory      = $this->createMock(TrackerFactory::class);
        $this->event_manager        = $this->createMock(EventManager::class);
        $this->reference_manager    = $this->createMock(ReferenceManager::class);
        $this->permissions_checker  = $this->createMock(GlobalAdminPermissionsChecker::class);
        $this->field_dao            = $this->createMock(FieldDao::class);
        $this->triggers_dao         = $this->createMock(TriggersDao::class);
        $this->project_history_dao  = $this->createMock(ProjectHistoryDao::class);
        $this->widgets_retriever    = $this->createMock(SearchWidgetsByTrackerId::class);
        $this->dashboard_widget_dao = $this->createMock(DashboardWidgetDao::class);

        $this->controller = new MarkTrackerAsDeletedController(
            $this->tracker_factory,
            $this->permissions_checker,
            $token_provider,
            $this->event_manager,
            $this->reference_manager,
            $this->field_dao,
            $this->triggers_dao,
            $this->project_history_dao,
            $this->widgets_retriever,
            $this->dashboard_widget_dao,
        );

        $this->user    = UserTestBuilder::aUser()->build();
        $this->project = ProjectTestBuilder::aProject()->withId(42)->build();

        $token_provider->method('getCSRF')->willReturn(CSRFSynchronizerTokenStub::buildSelf());
    }

    public function testItThrowsExceptionIfTrackerCannotBeFound(): void
    {
        $this->tracker_factory
            ->expects($this->once())
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
        $tracker = TrackerTestBuilder::aTracker()->withDeletionDate((new DateTimeImmutable())->getTimestamp())->build();

        $this->tracker_factory
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(false);

        $this->tracker_factory
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(true);

        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->willReturn($tracker);

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
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(true);

        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->willReturn($tracker);

        $this->field_dao
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(true);

        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->willReturn($tracker);

        $this->field_dao
            ->expects($this->once())
            ->method('doesTrackerHaveSourceSharedFields')
            ->with(102)
            ->willReturn(false);

        $this->triggers_dao
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(true);

        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->willReturn($tracker);

        $this->field_dao
            ->expects($this->once())
            ->method('doesTrackerHaveSourceSharedFields')
            ->with(102)
            ->willReturn(false);

        $this->triggers_dao
            ->expects($this->once())
            ->method('isTrackerImplicatedInTriggers')
            ->with(102)
            ->willReturn(false);

        $this->tracker_factory
            ->expects($this->once())
            ->method('markAsDeleted')
            ->with(102)
            ->willReturn(false);

        $layout = $this->createMock(BaseLayout::class);
        $layout
            ->expects($this->once())
            ->method('addFeedback')
            ->with(Feedback::ERROR, self::anything());

        $layout->expects($this->once())->method('redirect');

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
            ->expects($this->once())
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->user)
            ->willReturn(true);

        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->willReturn($tracker);

        $this->field_dao
            ->expects($this->once())
            ->method('doesTrackerHaveSourceSharedFields')
            ->with(102)
            ->willReturn(false);

        $this->triggers_dao
            ->expects($this->once())
            ->method('isTrackerImplicatedInTriggers')
            ->with(102)
            ->willReturn(false);

        $this->tracker_factory
            ->expects($this->once())
            ->method('markAsDeleted')
            ->with(102)
            ->willReturn(true);

        $this->event_manager
            ->expects($this->once())
            ->method('processEvent')
            ->with('tracker_event_delete_tracker', ['tracker_id' => 102]);

        $reference = $this->createMock(Reference::class);
        $this->reference_manager
            ->expects($this->once())
            ->method('loadReferenceFromKeywordAndNumArgs')
            ->with('story', 42, 1)
            ->willReturn($reference);
        $this->reference_manager
            ->expects($this->once())
            ->method('deleteReference')
            ->with($reference)
            ->willReturn(true);

        $this->widgets_retriever->expects($this->once())->method('searchByTrackerId')->willReturn([
            [
                'owner_id'       => 101,
                'widget_id'      => 645,
                'dashboard_id'   => 5,
                'dashboard_type' => 'project',
            ],
        ]);
        $this->dashboard_widget_dao->expects($this->once())->method('deleteWidget')->with(101, 5, 'project', 645);

        $layout  = $this->createMock(BaseLayout::class);
        $matcher = $this->exactly(4);
        $layout->expects($matcher)
            ->method('addFeedback')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame(Feedback::INFO, $parameters[0]);
                    self::assertSame('Tracker User story has been successfully deleted', $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame(Feedback::INFO, $parameters[0]);
                    self::assertSame(Codendi_HTMLPurifier::CONFIG_LIGHT, $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 3) {
                    self::assertSame(Feedback::INFO, $parameters[0]);
                    self::assertSame('Corresponding Reference Pattern Deleted', $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 4) {
                    self::assertSame(Feedback::INFO, $parameters[0]);
                    self::assertSame('Corresponding widgets deleted', $parameters[1]);
                }
            });

        $layout->expects($this->once())->method('redirect');

        $this->project_history_dao->method('addHistory');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            $layout,
            ['id' => '102']
        );
    }

    private function buildMockTracker(): Tracker&MockObject
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
