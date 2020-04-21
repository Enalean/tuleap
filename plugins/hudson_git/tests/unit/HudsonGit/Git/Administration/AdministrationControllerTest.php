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

use Git_Mirror_MirrorDataMapper;
use GitPermissionsManager;
use GitPlugin;
use GitRepository;
use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use TemplateRenderer;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\GlobalLanguageMock;
use Tuleap\HudsonGit\Log\Log;
use Tuleap\HudsonGit\Log\LogFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class AdministrationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var AdministrationController
     */
    private $controller;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var GitPermissionsManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $git_permissions_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BaseLayout
     */
    private $layout;

    /**
     * @var HTTPRequest|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $request;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TemplateRenderer
     */
    private $renderer;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|HeaderRenderer
     */
    private $header_renderer;

    /**
     * @var Git_Mirror_MirrorDataMapper|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $mirror_data_mapper;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JenkinsServerFactory
     */
    private $jenkins_server_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|IncludeAssets
     */
    private $include_assets;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LogFactory
     */
    private $log_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_manager         = Mockery::mock(ProjectManager::class);
        $this->git_permissions_manager = Mockery::mock(GitPermissionsManager::class);
        $this->header_renderer         = Mockery::mock(HeaderRenderer::class);
        $this->renderer                = Mockery::mock(TemplateRenderer::class);
        $this->mirror_data_mapper      = Mockery::mock(Git_Mirror_MirrorDataMapper::class);
        $this->jenkins_server_factory  = Mockery::mock(JenkinsServerFactory::class);
        $this->include_assets          = Mockery::mock(IncludeAssets::class);
        $this->log_factory             = Mockery::mock(LogFactory::class);

        $this->controller = new AdministrationController(
            $this->project_manager,
            $this->git_permissions_manager,
            $this->mirror_data_mapper,
            $this->jenkins_server_factory,
            $this->log_factory,
            $this->header_renderer,
            $this->renderer,
            $this->include_assets
        );

        $this->layout  = Mockery::mock(BaseLayout::class);
        $this->request = Mockery::mock(HTTPRequest::class);
        $this->project = Mockery::mock(Project::class);

        $this->project->shouldReceive('isError')->andReturnFalse();
        $this->project->shouldReceive('getID')->andReturn(101);
        $this->project->shouldReceive('getUnixName')->andReturn('project01');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);

        parent::tearDown();
    }

    public function testProcessThrowsNotFoundWhenProjectIsInError(): void
    {
        $variables = ['project_name' => 'test'];

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->with('test')
            ->once()
            ->andReturnNull();

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, $variables);
    }

    public function testProcessThrowsNotFoundExceptionWhenProjectDoesNotUseGitService(): void
    {
        $variables = ['project_name' => 'test'];

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->with('test')
            ->once()
            ->andReturn($this->project);

        $this->project->shouldReceive('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->once()
            ->andReturnFalse();

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, $variables);
    }

    public function testProcessThrowsForbiddenWhenUserIsNotGitAdmin(): void
    {
        $variables = ['project_name' => 'test'];

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->with('test')
            ->once()
            ->andReturn($this->project);

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

        $this->controller->process($this->request, $this->layout, $variables);
    }

    public function testProcessDisplaysThePage(): void
    {
        $variables = ['project_name' => 'test'];

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->with('test')
            ->once()
            ->andReturn($this->project);

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

        $this->mirror_data_mapper->shouldReceive('fetchAllForProject')->andReturn([]);

        $jenkins_server = new JenkinsServer(1, 'url', $this->project);
        $this->jenkins_server_factory->shouldReceive('getJenkinsServerOfProject')
            ->once()
            ->with($this->project)
            ->andReturn([$jenkins_server]);

        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getName')->andReturn('repo01');
        $log = new Log($repository, 1582622782, 'job_url', null);
        $this->log_factory->shouldReceive('getLastJobLogsByProjectServer')
            ->with($jenkins_server)
            ->andReturn([$log]);

        $this->header_renderer->shouldReceive('renderServiceAdministrationHeader')->once();
        $this->renderer->shouldReceive('renderToPage')->once();
        $this->layout->shouldReceive('footer')->once();
        $this->layout->shouldReceive('includeFooterJavascriptFile')->once();
        $this->include_assets->shouldReceive('getFileURL')->once();

        $this->controller->process($this->request, $this->layout, $variables);
    }
}
