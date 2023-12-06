<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectUGroup\SynchronizedProjectMembership;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ActivationControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ActivationController $controller;
    private ProjectRetriever&MockObject $project_retriever;
    private ProjectAdministratorChecker&MockObject $administrator_checker;
    private SynchronizedProjectMembershipDao&MockObject $dao;
    private \CSRFSynchronizerToken&MockObject $csrf;
    private BaseLayout&MockObject $layout;
    private \HTTPRequest&MockObject $request;

    protected function setUp(): void
    {
        $this->layout                = $this->createMock(BaseLayout::class);
        $this->request               = $this->createMock(\HTTPRequest::class);
        $this->project_retriever     = $this->createMock(ProjectRetriever::class);
        $this->administrator_checker = $this->createMock(ProjectAdministratorChecker::class);
        $this->dao                   = $this->createMock(SynchronizedProjectMembershipDao::class);
        $this->csrf                  = $this->createMock(\CSRFSynchronizerToken::class);
        $this->controller            = new ActivationController(
            $this->project_retriever,
            $this->administrator_checker,
            $this->dao,
            $this->csrf
        );
    }

    public function testGetUrl(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(104)->build();

        self::assertEquals(
            '/project/104/admin/change-synchronized-project-membership',
            ActivationController::getUrl($project)
        );
    }

    public function testProcessEnablesSynchronizedProjectMembership(): void
    {
        $this->csrf->expects(self::once())->method('check');
        $project = ProjectTestBuilder::aProject()->withId(104)->build();
        $this->project_retriever
            ->expects(self::once())
            ->method('getProjectFromId')
            ->with('104')
            ->willReturn($project);
        $variables = ['project_id' => '104'];
        $this->request
            ->expects(self::once())
            ->method('get')
            ->with('activation')
            ->willReturn('on');
        $user = UserTestBuilder::buildWithDefaults();
        $this->request
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($user);
        $this->administrator_checker
            ->expects(self::once())
            ->method('checkUserIsProjectAdministrator')
            ->with($user, $project);

        $this->dao->expects(self::once())->method('enable');
        $this->dao->expects(self::never())->method('disable');

        $this->layout->method('addFeedback');
        $this->layout->expects(self::once())->method('redirect')
            ->with('/project/admin/ugroup.php?group_id=104');

        $this->controller->process($this->request, $this->layout, $variables);
    }

    public function testProcessDisablesSynchronizedProjectMembership(): void
    {
        $this->csrf->expects(self::once())->method('check');
        $project = ProjectTestBuilder::aProject()->withId(104)->build();
        $this->project_retriever
            ->expects(self::once())
            ->method('getProjectFromId')
            ->with('104')
            ->willReturn($project);
        $variables = ['project_id' => '104'];
        $this->request
            ->expects(self::once())
            ->method('get')
            ->with('activation')
            ->willReturn(false);
        $user = UserTestBuilder::buildWithDefaults();
        $this->request
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($user);
        $this->administrator_checker
            ->expects(self::once())
            ->method('checkUserIsProjectAdministrator')
            ->with($user, $project);

        $this->dao->expects(self::once())->method('disable');
        $this->dao->expects(self::never())->method('enable');

        $this->layout->method('addFeedback');
        $this->layout->expects(self::once())->method('redirect')
            ->with('/project/admin/ugroup.php?group_id=104');

        $this->controller->process($this->request, $this->layout, $variables);
    }
}
