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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use RuntimeException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class DeleteControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DeleteController
     */
    private $controller;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JenkinsServerFactory
     */
    private $git_jenkins_administration_server_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BaseLayout
     */
    private $layout;

    /**
     * @var HTTPRequest|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $request;

    /**
     * @var JenkinsServer
     */
    private $jenkins_server;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JenkinsServerDeleter
     */
    private $git_jenkins_administration_server_deleter;

    /**
     * @var CSRFSynchronizerToken|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $csrf_token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_permissions_manager                   = Mockery::mock(GitPermissionsManager::class);
        $this->git_jenkins_administration_server_factory = Mockery::mock(JenkinsServerFactory::class);
        $this->git_jenkins_administration_server_deleter = Mockery::mock(JenkinsServerDeleter::class);
        $this->csrf_token                                = Mockery::mock(CSRFSynchronizerToken::class);

        $this->controller = new DeleteController(
            $this->git_permissions_manager,
            $this->git_jenkins_administration_server_factory,
            $this->git_jenkins_administration_server_deleter,
            $this->csrf_token
        );

        $this->layout  = Mockery::mock(BaseLayout::class);
        $this->request = Mockery::mock(HTTPRequest::class);
        $this->project = Mockery::mock(Project::class);

        $this->jenkins_server = new JenkinsServer(
            1,
            'url',
            $this->project
        );

        $this->project->shouldReceive('isError')->andReturnFalse();
        $this->project->shouldReceive('getUnixName')->andReturn('test');

        $this->csrf_token->shouldReceive('check');
    }

    public function testItThrowsRuntimeIfJenkinsServerIDNotProvided(): void
    {
        $this->request->shouldReceive('exist')->with('jenkins_server_id')->andReturnFalse();

        $this->expectException(RuntimeException::class);

        $this->controller->process(
            $this->request,
            $this->layout,
            []
        );
    }

    public function testItThrowsNotFoundIfJenkinsServerDoesNotExists(): void
    {
        $this->request->shouldReceive('exist')->with('jenkins_server_id')->andReturnTrue();
        $this->request->shouldReceive('get')->with('jenkins_server_id')->andReturn(1);

        $this->git_jenkins_administration_server_factory->shouldReceive('getJenkinsServerById')
            ->once()
            ->with(1)
            ->andReturnNull();

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $this->request,
            $this->layout,
            []
        );
    }

    public function testProcessThrowsNotFoundExceptionWhenProjectDoesNotUseGitService(): void
    {
        $this->request->shouldReceive('exist')->with('jenkins_server_id')->andReturnTrue();
        $this->request->shouldReceive('get')->with('jenkins_server_id')->andReturn(1);

        $this->git_jenkins_administration_server_factory->shouldReceive('getJenkinsServerById')
            ->once()
            ->with(1)
            ->andReturn($this->jenkins_server);

        $this->project->shouldReceive('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->once()
            ->andReturnFalse();

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $this->request,
            $this->layout,
            []
        );
    }

    public function testProcessThrowsForbiddenWhenUserIsNotGitAdmin(): void
    {
        $this->request->shouldReceive('exist')->with('jenkins_server_id')->andReturnTrue();
        $this->request->shouldReceive('get')->with('jenkins_server_id')->andReturn(1);

        $this->git_jenkins_administration_server_factory->shouldReceive('getJenkinsServerById')
            ->once()
            ->with(1)
            ->andReturn($this->jenkins_server);

        $this->project->shouldReceive('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->once()
            ->andReturnTrue();

        $user = Mockery::mock(PFUser::class);
        $this->request->shouldReceive('getCurrentUser')->andReturn($user);

        $this->git_permissions_manager->shouldReceive('userIsGitAdmin')
            ->once()
            ->with(
                $user,
                $this->project
            )
            ->andReturnFalse();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            $this->request,
            $this->layout,
            []
        );
    }

    public function testProcessDeletesTheJenkinsServer(): void
    {
        $this->request->shouldReceive('exist')->with('jenkins_server_id')->andReturnTrue();
        $this->request->shouldReceive('get')->with('jenkins_server_id')->andReturn(1);

        $this->git_jenkins_administration_server_factory->shouldReceive('getJenkinsServerById')
            ->once()
            ->with(1)
            ->andReturn($this->jenkins_server);

        $this->project->shouldReceive('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->once()
            ->andReturnTrue();

        $user = Mockery::mock(PFUser::class);
        $this->request->shouldReceive('getCurrentUser')->andReturn($user);

        $this->git_permissions_manager->shouldReceive('userIsGitAdmin')
            ->once()
            ->with(
                $user,
                $this->project
            )
            ->andReturnTrue();

        $this->git_jenkins_administration_server_deleter->shouldReceive('deleteServer')
            ->with($this->jenkins_server)
            ->once();

        $this->layout->shouldReceive('redirect');
        $this->layout->shouldReceive('addFeedback');

        $this->controller->process(
            $this->request,
            $this->layout,
            []
        );
    }
}
