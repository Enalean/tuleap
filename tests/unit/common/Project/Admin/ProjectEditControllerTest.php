<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin;

use EventManager;
use Feedback;
use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectHistoryDao;
use ProjectManager;
use SystemEventManager;
use TemplateSingleton;
use Tuleap\admin\ProjectEdit\ProjectEditController;
use Tuleap\admin\ProjectEdit\ProjectEditDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectEditControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private ProjectDetailsPresenter&MockObject $details_presenter;
    private ProjectEditDao&MockObject $dao;
    private ProjectManager&MockObject $project_manager;
    private EventManager&MockObject $event_manager;
    private SystemEventManager&MockObject $system_event_manager;
    private ProjectHistoryDao&MockObject $project_history_dao;
    private ProjectEditController $project_edit_controller;
    private Project $project;
    private ProjectRenameChecker&MockObject $project_rename_checker;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()
            ->withId(111)
            ->withStatusSuspended()
            ->withUnixName('old_name')
            ->build();

        $this->details_presenter      = $this->createMock(ProjectDetailsPresenter::class);
        $this->dao                    = $this->createMock(ProjectEditDao::class);
        $this->project_manager        = $this->createMock(ProjectManager::class);
        $this->event_manager          = $this->createMock(EventManager::class);
        $this->system_event_manager   = $this->createMock(SystemEventManager::class);
        $this->project_history_dao    = $this->createMock(ProjectHistoryDao::class);
        $this->project_rename_checker = $this->createMock(ProjectRenameChecker::class);

        $this->project_edit_controller = new ProjectEditController(
            $this->details_presenter,
            $this->dao,
            $this->project_manager,
            $this->event_manager,
            $this->system_event_manager,
            $this->project_history_dao,
            $this->project_rename_checker
        );
    }

    public function testUpdateProjectStatus(): void
    {
        $request = $this->createMock(HTTPRequest::class);
        $matcher = self::exactly(2);
        $request->expects($matcher)->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('new_name', $parameters[0]);
                return null;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('group_id', $parameters[0]);
                return 111;
            }
        });

        $this->project_manager->method('getProject')->willReturn($this->project);
        $matcher = $this->exactly(2);

        $request->expects($matcher)->method('getValidated')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('form_status', $parameters[0]);
                self::assertSame('string', $parameters[1]);
                self::assertSame('H', $parameters[2]);
                return 'A';
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('group_type', $parameters[0]);
                self::assertSame('string', $parameters[1]);
                self::assertSame(TemplateSingleton::PROJECT, (int) $parameters[2]);
                return TemplateSingleton::PROJECT;
            }
        });

        $this->dao->method('updateProjectStatusAndType');

        $GLOBALS['Response']->method('addFeedback')->with(Feedback::INFO, 'Updating Project Info');

        $this->project_history_dao->method('groupAddHistory');

        $this->event_manager->method('processEvent');
        $this->event_manager->method('dispatch');

        $this->project_manager->method('removeProjectFromCache');

        $this->project_edit_controller->updateProject($request);
    }

    public function testUpdateProjectUnixNameDoesntWorkIfUnixNameCantBeEdited(): void
    {
        $request = $this->createMock(HTTPRequest::class);
        $matcher = self::exactly(2);
        $request->expects($matcher)->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('new_name', $parameters[0]);
                return 'new_name';
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('group_id', $parameters[0]);
                return 111;
            }
        });

        $this->project_manager->method('getProject')->willReturn($this->project);
        $matcher = $this->exactly(2);

        $request->expects($matcher)->method('getValidated')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('form_status', $parameters[0]);
                self::assertSame('string', $parameters[1]);
                self::assertSame('H', $parameters[2]);
                return 'A';
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('group_type', $parameters[0]);
                self::assertSame('string', $parameters[1]);
                self::assertSame(TemplateSingleton::PROJECT, (int) $parameters[2]);
                return TemplateSingleton::PROJECT;
            }
        });

        $this->dao->method('updateProjectStatusAndType');
        $matcher = $this->exactly(2);

        $GLOBALS['Response']->expects($matcher)->method('addFeedback')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(Feedback::WARN, $parameters[0]);
                self::assertSame("This project doesn't allow short name edition.", $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(Feedback::INFO, $parameters[0]);
                self::assertSame('Updating Project Info', $parameters[1]);
            }
        });

        $this->project_history_dao->method('groupAddHistory');

        $this->system_event_manager->method('canRenameProject')->willReturn(true);
        $this->project_rename_checker->method('isProjectUnixNameEditable')->willReturn(false);

        $this->event_manager->method('processEvent');
        $this->event_manager->method('dispatch');

        $this->project_manager->method('removeProjectFromCache');

        $this->project_edit_controller->updateProject($request);
    }

    public function testUpdateProjectStatusThrowErrorIfTryingToPassAProjectToPending(): void
    {
        $this->project = ProjectTestBuilder::aProject()
            ->withId(111)
            ->withStatusDeleted()
            ->build();
        $request       = $this->createMock(HTTPRequest::class);
        $matcher       = $this->exactly(2);
        $request->expects($matcher)->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('new_name', $parameters[0]);
                return null;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('group_id', $parameters[0]);
                return 111;
            }
        });

        $this->project_manager->method('getProject')->willReturn($this->project);
        $matcher = $this->exactly(2);

        $request->expects($matcher)->method('getValidated')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('form_status', $parameters[0]);
                self::assertSame('string', $parameters[1]);
                self::assertSame('D', $parameters[2]);
                return 'A';
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('group_type', $parameters[0]);
                self::assertSame('string', $parameters[1]);
                self::assertSame(TemplateSingleton::PROJECT, (int) $parameters[2]);
                return TemplateSingleton::PROJECT;
            }
        });

        $GLOBALS['Response']->method('addFeedback')->with(Feedback::ERROR, 'A deleted project can not be restored.');
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        $this->dao->expects($this->never())->method('updateProjectStatusAndType');

        $this->project_edit_controller->updateProject($request);
    }

    public function testUpdateProjectStatusThrowErrorIfProjectAlreadyDeleted2(): void
    {
        $this->project = ProjectTestBuilder::aProject()
            ->withId(111)
            ->withStatusDeleted()
            ->build();
        $request       = $this->createMock(HTTPRequest::class);
        $matcher       = $this->exactly(2);
        $request->expects($matcher)->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('new_name', $parameters[0]);
                return null;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('group_id', $parameters[0]);
                return 111;
            }
        });

        $this->project_manager->method('getProject')->willReturn($this->project);
        $matcher = $this->exactly(2);

        $request->expects($matcher)->method('getValidated')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('form_status', $parameters[0]);
                self::assertSame('string', $parameters[1]);
                self::assertSame('D', $parameters[2]);
                return 'P';
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('group_type', $parameters[0]);
                self::assertSame('string', $parameters[1]);
                self::assertSame(TemplateSingleton::PROJECT, (int) $parameters[2]);
                return TemplateSingleton::PROJECT;
            }
        });

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')
            ->with(Feedback::ERROR, 'Switching the project status back to "pending" is not possible.');
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        $this->dao->expects($this->never())->method('updateProjectStatusAndType');

        $this->project_edit_controller->updateProject($request);
    }
}
