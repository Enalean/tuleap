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

use GitPermissionsManager;
use GitPlugin;
use GitRepository;
use HTTPRequest;
use PFUser;
use Project;
use ProjectManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\GlobalLanguageMock;
use Tuleap\HudsonGit\Log\Log;
use Tuleap\HudsonGit\Log\LogFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class AdministrationControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private AdministrationController $controller;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&ProjectManager
     */
    private $project_manager;

    /**
     * @var GitPermissionsManager&PHPUnit\Framework\MockObject\MockObject
     */
    private $git_permissions_manager;

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
     * @var PHPUnit\Framework\MockObject\MockObject&TemplateRenderer
     */
    private $renderer;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&HeaderRenderer
     */
    private $header_renderer;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&JenkinsServerFactory
     */
    private $jenkins_server_factory;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&IncludeAssets
     */
    private $include_assets;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&LogFactory
     */
    private $log_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_manager         = $this->createMock(ProjectManager::class);
        $this->git_permissions_manager = $this->createMock(GitPermissionsManager::class);
        $this->header_renderer         = $this->createMock(HeaderRenderer::class);
        $this->renderer                = $this->createMock(TemplateRenderer::class);
        $this->jenkins_server_factory  = $this->createMock(JenkinsServerFactory::class);
        $this->include_assets          = $this->createMock(IncludeAssets::class);
        $this->log_factory             = $this->createMock(LogFactory::class);

        $this->controller = new AdministrationController(
            $this->project_manager,
            $this->git_permissions_manager,
            $this->jenkins_server_factory,
            $this->log_factory,
            $this->header_renderer,
            $this->renderer,
            $this->include_assets,
            new class implements EventDispatcherInterface {
                public function dispatch(object $event): object
                {
                    return $event;
                }
            }
        );

        $this->layout  = $this->createMock(BaseLayout::class);
        $this->request = $this->createMock(HTTPRequest::class);
        $this->project = $this->createMock(Project::class);

        $this->project->method('isError')->willReturn(false);
        $this->project->method('getID')->willReturn(101);
        $this->project->method('getUnixName')->willReturn('project01');

        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);

        parent::tearDown();
    }

    public function testProcessThrowsNotFoundWhenProjectIsInError(): void
    {
        $variables = ['project_name' => 'test'];

        $this->project_manager->expects(self::once())
            ->method('getProjectByCaseInsensitiveUnixName')
            ->with('test')
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, $variables);
    }

    public function testProcessThrowsNotFoundExceptionWhenProjectDoesNotUseGitService(): void
    {
        $variables = ['project_name' => 'test'];

        $this->project_manager->expects(self::once())
            ->method('getProjectByCaseInsensitiveUnixName')
            ->with('test')
            ->willReturn($this->project);

        $this->project->expects(self::once())
            ->method('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->willReturn(false);

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, $variables);
    }

    public function testProcessThrowsForbiddenWhenUserIsNotGitAdmin(): void
    {
        $variables = ['project_name' => 'test'];

        $this->project_manager->expects(self::once())
            ->method('getProjectByCaseInsensitiveUnixName')
            ->with('test')
            ->willReturn($this->project);

        $this->project->expects(self::once())
            ->method('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->willReturn(true);

        $user = $this->createMock(PFUser::class);
        $this->request->method('getCurrentUser')->willReturn($user);

        $this->git_permissions_manager->expects(self::once())
            ->method('userIsGitAdmin')
            ->with(
                $user,
                $this->project
            )
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->controller->process($this->request, $this->layout, $variables);
    }

    public function testProcessDisplaysThePage(): void
    {
        $variables = ['project_name' => 'test'];

        $this->project_manager->expects(self::once())
            ->method('getProjectByCaseInsensitiveUnixName')
            ->with('test')
            ->willReturn($this->project);

        $this->project->expects(self::once())
            ->method('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->willReturn(true);

        $user = $this->createMock(PFUser::class);
        $this->request->method('getCurrentUser')->willReturn($user);

        $this->git_permissions_manager->expects(self::once())
            ->method('userIsGitAdmin')
            ->with(
                $user,
                $this->project
            )
            ->willReturn(true);

        $jenkins_server = new JenkinsServer(1, 'url', 'encrypted_token', $this->project);
        $this->jenkins_server_factory->expects(self::once())
            ->method('getJenkinsServerOfProject')
            ->with($this->project)
            ->willReturn([$jenkins_server]);

        $repository = $this->createMock(GitRepository::class);
        $repository->method('getName')->willReturn('repo01');
        $log = new Log($repository, 1582622782, 'job_url', null);
        $this->log_factory->method('getLastJobLogsByProjectServer')
            ->with($jenkins_server)
            ->willReturn([$log]);

        $this->header_renderer->expects(self::once())->method('renderServiceAdministrationHeader');
        $this->renderer->expects(self::once())->method('renderToPage');
        $this->layout->expects(self::once())->method('footer');
        $this->layout->expects(self::once())->method('includeFooterJavascriptFile');
        $this->include_assets->expects(self::once())->method('getFileURL');

        $this->controller->process($this->request, $this->layout, $variables);
    }
}
