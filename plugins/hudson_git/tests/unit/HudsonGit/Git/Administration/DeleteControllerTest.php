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
use RuntimeException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;

final class DeleteControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private DeleteController $controller;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&JenkinsServerFactory
     */
    private $git_jenkins_administration_server_factory;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&BaseLayout
     */
    private $layout;

    /**
     * @var HTTPRequest&PHPUnit\Framework\MockObject\MockObject
     */
    private $request;

    private JenkinsServer $jenkins_server;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&Project
     */
    private $project;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&JenkinsServerDeleter
     */
    private $git_jenkins_administration_server_deleter;

    /**
     * @var CSRFSynchronizerToken&PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;
    /**
     * @var GitPermissionsManager&Mockery\MockInterface
     */
    private $git_permissions_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_permissions_manager                   = $this->createMock(GitPermissionsManager::class);
        $this->git_jenkins_administration_server_factory = $this->createMock(JenkinsServerFactory::class);
        $this->git_jenkins_administration_server_deleter = $this->createMock(JenkinsServerDeleter::class);
        $this->csrf_token                                = $this->createMock(CSRFSynchronizerToken::class);

        $this->controller = new DeleteController(
            $this->git_permissions_manager,
            $this->git_jenkins_administration_server_factory,
            $this->git_jenkins_administration_server_deleter,
            $this->csrf_token
        );

        $this->layout  = $this->createMock(BaseLayout::class);
        $this->request = $this->createMock(HTTPRequest::class);
        $this->project = $this->createMock(Project::class);

        $this->jenkins_server = new JenkinsServer(
            1,
            'url',
            null,
            $this->project
        );

        $this->project->method('isError')->willReturn(false);
        $this->project->method('getUnixName')->willReturn('test');

        $this->csrf_token->method('check');
    }

    public function testItThrowsRuntimeIfJenkinsServerIDNotProvided(): void
    {
        $this->request->method('exist')->with('jenkins_server_id')->willReturn(false);

        $this->expectException(RuntimeException::class);

        $this->controller->process(
            $this->request,
            $this->layout,
            []
        );
    }

    public function testItThrowsNotFoundIfJenkinsServerDoesNotExists(): void
    {
        $this->request->method('exist')->with('jenkins_server_id')->willReturn(true);
        $this->request->method('get')->with('jenkins_server_id')->willReturn(1);

        $this->git_jenkins_administration_server_factory->expects(self::once())
            ->method('getJenkinsServerById')
            ->with(1)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $this->request,
            $this->layout,
            []
        );
    }

    public function testProcessThrowsNotFoundExceptionWhenProjectDoesNotUseGitService(): void
    {
        $this->request->method('exist')->with('jenkins_server_id')->willReturn(true);
        $this->request->method('get')->with('jenkins_server_id')->willReturn(1);

        $this->git_jenkins_administration_server_factory->expects(self::once())
            ->method('getJenkinsServerById')
            ->with(1)
            ->willReturn($this->jenkins_server);

        $this->project->expects(self::once())
            ->method('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->willReturn(false);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $this->request,
            $this->layout,
            []
        );
    }

    public function testProcessThrowsForbiddenWhenUserIsNotGitAdmin(): void
    {
        $this->request->method('exist')->with('jenkins_server_id')->willReturn(true);
        $this->request->method('get')->with('jenkins_server_id')->willReturn(1);

        $this->git_jenkins_administration_server_factory->expects(self::once())
            ->method('getJenkinsServerById')
            ->with(1)
            ->willReturn($this->jenkins_server);

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

        $this->controller->process(
            $this->request,
            $this->layout,
            []
        );
    }

    public function testProcessDeletesTheJenkinsServer(): void
    {
        $this->request->method('exist')->with('jenkins_server_id')->willReturn(true);
        $this->request->method('get')->with('jenkins_server_id')->willReturn(1);

        $this->git_jenkins_administration_server_factory->expects(self::once())
            ->method('getJenkinsServerById')
            ->with(1)
            ->willReturn($this->jenkins_server);

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

        $this->git_jenkins_administration_server_deleter->expects(self::once())
            ->method('deleteServer')
            ->with($this->jenkins_server);

        $this->layout->method('redirect');
        $this->layout->method('addFeedback');

        $this->controller->process(
            $this->request,
            $this->layout,
            []
        );
    }
}
