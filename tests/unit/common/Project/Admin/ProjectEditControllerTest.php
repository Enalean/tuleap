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
use PHPUnit\Framework\MockObject\Stub;
use Project;
use ProjectHistoryDao;
use ProjectManager;
use SystemEventManager;
use TemplateSingleton;
use Tuleap\admin\ProjectEdit\ProjectEditController;
use Tuleap\admin\ProjectEdit\ProjectEditDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectEditControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private ProjectDetailsPresenter&Stub $details_presenter;
    private ProjectEditDao&Stub $dao;
    private ProjectManager&Stub $project_manager;
    private EventManager&Stub $event_manager;
    private SystemEventManager&Stub $system_event_manager;
    private ProjectHistoryDao&Stub $project_history_dao;
    private ProjectEditController $project_edit_controller;
    private Project $project;
    private ProjectRenameChecker&Stub $project_rename_checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()
            ->withId(111)
            ->withStatusSuspended()
            ->withUnixName('old_name')
            ->build();

        $this->details_presenter      = $this->createStub(ProjectDetailsPresenter::class);
        $this->dao                    = $this->createStub(ProjectEditDao::class);
        $this->project_manager        = $this->createStub(ProjectManager::class);
        $this->event_manager          = $this->createStub(EventManager::class);
        $this->system_event_manager   = $this->createStub(SystemEventManager::class);
        $this->project_history_dao    = $this->createStub(ProjectHistoryDao::class);
        $this->project_rename_checker = $this->createStub(ProjectRenameChecker::class);

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
        $request = $this->createMock(\Tuleap\HTTPRequest::class);
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

        $this->project_history_dao->method('groupAddHistory');

        $this->event_manager->method('processEvent');
        $this->event_manager->method('dispatch');

        $this->project_manager->method('removeProjectFromCache');

        try {
            $this->expectExceptionObject(new LayoutInspectorRedirection('/admin/groupedit.php?group_id=111'));
            $this->project_edit_controller->updateProject($request);
        } finally {
            self::assertEquals([['level' => Feedback::INFO, 'message' => 'Updating Project Info']], $this->global_response->inspector->getFeedback());
        }
    }

    public function testUpdateProjectUnixNameDoesntWorkIfUnixNameCantBeEdited(): void
    {
        $request = $this->createMock(\Tuleap\HTTPRequest::class);
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

        $this->project_history_dao->method('groupAddHistory');

        $this->system_event_manager->method('canRenameProject')->willReturn(true);
        $this->project_rename_checker->method('isProjectUnixNameEditable')->willReturn(false);

        $this->event_manager->method('processEvent');
        $this->event_manager->method('dispatch');

        $this->project_manager->method('removeProjectFromCache');

        try {
            $this->expectExceptionObject(new LayoutInspectorRedirection('/admin/groupedit.php?group_id=111'));
            $this->project_edit_controller->updateProject($request);
        } finally {
            self::assertEquals(
                [
                    ['level' => Feedback::WARN, 'message' => "This project doesn't allow short name edition."],
                    ['level' => Feedback::INFO, 'message' => 'Updating Project Info'],
                ],
                $this->global_response->inspector->getFeedback()
            );
        }
    }

    public function testUpdateProjectStatusThrowErrorIfTryingToPassAProjectToPending(): void
    {
        $this->project = ProjectTestBuilder::aProject()
            ->withId(111)
            ->withStatusDeleted()
            ->build();
        $request       = $this->createMock(\Tuleap\HTTPRequest::class);
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

        try {
            $this->expectExceptionObject(new LayoutInspectorRedirection('/admin/groupedit.php?group_id=111'));
            $this->project_edit_controller->updateProject($request);
        } finally {
            self::assertEquals(['A deleted project can not be restored.'], $this->global_response->getFeedbackErrors());
        }
    }

    public function testUpdateProjectStatusThrowErrorIfProjectAlreadyDeleted2(): void
    {
        $this->project = ProjectTestBuilder::aProject()
            ->withId(111)
            ->withStatusDeleted()
            ->build();
        $request       = $this->createMock(\Tuleap\HTTPRequest::class);
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

        try {
            $this->expectExceptionObject(new LayoutInspectorRedirection('/admin/groupedit.php?group_id=111'));
            $this->project_edit_controller->updateProject($request);
        } finally {
            self::assertEquals(['Switching the project status back to "pending" is not possible.'], $this->global_response->getFeedbackErrors());
        }
    }
}
