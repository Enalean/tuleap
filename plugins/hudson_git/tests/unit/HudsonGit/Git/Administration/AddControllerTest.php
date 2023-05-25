<?php
/**
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

namespace Tuleap\HudsonGit\Git\Administration;

use CSRFSynchronizerToken;
use GitPermissionsManager;
use GitPlugin;
use HTTPRequest;
use Project;
use ProjectManager;
use RuntimeException;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;

final class AddControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AddController $controller;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&ProjectManager
     */
    private $project_manager;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&BaseLayout
     */
    private $layout;

    /**
     * @var HTTPRequest&PHPUnit\Framework\MockObject\MockObject
     */
    private $request;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&Project
     */
    private $project;

    /**
     * @var GitPermissionsManager&PHPUnit\Framework\MockObject\MockObject
     */
    private $git_permissions_manager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&JenkinsServerAdder
     */
    private $git_jenkins_administration_server_adder;

    /**
     * @var CSRFSynchronizerToken&PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_manager                         = $this->createMock(ProjectManager::class);
        $this->git_permissions_manager                 = $this->createMock(GitPermissionsManager::class);
        $this->git_jenkins_administration_server_adder = $this->createMock(JenkinsServerAdder::class);
        $this->csrf_token                              = $this->createMock(CSRFSynchronizerToken::class);

        $this->controller = new AddController(
            $this->project_manager,
            $this->git_permissions_manager,
            $this->git_jenkins_administration_server_adder,
            $this->csrf_token
        );

        $this->layout  = $this->createMock(BaseLayout::class);
        $this->request = $this->createMock(HTTPRequest::class);
        $this->project = $this->createMock(Project::class);

        $this->project->method('isError')->willReturn(false);
        $this->project->method('getUnixName')->willReturn('test');

        $this->csrf_token->method('check');
    }

    public function testProcessThrowsNotFoundWhenProjectIdIsNotProvided(): void
    {
        $this->request->expects(self::once())->method('get')->with('project_id')->willReturn(false);

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, []);
    }

    public function testProcessThrowsNotFoundWhenProjectIsInError(): void
    {
        $this->request->expects(self::once())->method('get')->with('project_id')->willReturn('101');

        $this->project_manager->expects(self::once())
            ->method('getProject')
            ->with(101)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, []);
    }

    public function testProcessThrowsNotFoundExceptionWhenProjectDoesNotUseGitService(): void
    {
        $this->request->expects(self::once())->method('get')->with('project_id')->willReturn('101');

        $this->project_manager->expects(self::once())
            ->method('getProject')
            ->with(101)
            ->willReturn($this->project);

        $this->project->expects(self::once())
            ->method('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->willReturn(false);

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, []);
    }

    public function testProcessThrowsForbiddenWhenUserIsNotGitAdmin(): void
    {
        $this->request->expects(self::once())->method('get')->with('project_id')->willReturn('101');

        $this->project_manager->expects(self::once())
            ->method('getProject')
            ->with(101)
            ->willReturn($this->project);

        $this->project->expects(self::once())
            ->method('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->willReturn(true);

        $user = UserTestBuilder::aUser()->build();
        $this->request->method('getCurrentUser')->willReturn($user);

        $this->git_permissions_manager->expects(self::once())
            ->method('userIsGitAdmin')
            ->with(
                $user,
                $this->project
            )
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->controller->process($this->request, $this->layout, []);
    }

    public function testProcessThrowsRuntimeExceptionWhenJenkinsServerURLNotProvided(): void
    {
        $this->request->method('get')->willReturnMap([
            ['project_id', '101'],
            ['url', false],
        ]);

        $this->project_manager->expects(self::once())
            ->method('getProject')
            ->with(101)
            ->willReturn($this->project);

        $this->project->expects(self::once())
            ->method('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->willReturn(true);

        $user = UserTestBuilder::aUser()->build();
        $this->request->method('getCurrentUser')->willReturn($user);

        $this->git_permissions_manager->expects(self::once())
            ->method('userIsGitAdmin')
            ->with(
                $user,
                $this->project
            )
            ->willReturn(true);

        $this->expectException(RuntimeException::class);

        $this->controller->process($this->request, $this->layout, []);
    }

    public function testProcessAddsTheJenkinsServer(): void
    {
        $this->request->method('get')->willReturnMap([
            ['project_id', '101'],
            ['url', 'https://example.com/jenkins'],
            ['token', 'my_secret_token'],
        ]);

        $this->project_manager->expects(self::once())
            ->method('getProject')
            ->with(101)
            ->willReturn($this->project);

        $this->project->expects(self::once())
            ->method('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->willReturn(true);

        $user = UserTestBuilder::aUser()->build();
        $this->request->method('getCurrentUser')->willReturn($user);

        $this->git_permissions_manager->expects(self::once())
            ->method('userIsGitAdmin')
            ->with(
                $user,
                $this->project
            )
            ->willReturn(true);

        $this->git_jenkins_administration_server_adder->expects(self::once())
            ->method('addServerInProject')
            ->with(
                $this->project,
                'https://example.com/jenkins',
                self::isInstanceOf(ConcealedString::class),
            );

        $this->layout->method('redirect');
        $this->layout->method('addFeedback');

        $this->controller->process($this->request, $this->layout, []);
    }
}
