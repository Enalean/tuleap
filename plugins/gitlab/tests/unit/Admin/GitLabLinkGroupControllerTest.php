<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Admin;

use Git_Mirror_MirrorDataMapper;
use GitPermissionsManager;
use GitPlugin;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use Tuleap\Git\Events\GitAdminGetExternalPanePresenters;
use Tuleap\Git\GitPresenters\AdminExternalPanePresenter;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\ProjectByUnixNameFactory;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProjectByUnixUnixNameFactory;

class GitLabLinkGroupControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID        = 150;
    private const PROJECT_UNIX_NAME = 'tuleap-gitlab';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HeaderRenderer
     */
    private $header_renderer;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;
    /**
     * @var GitPermissionsManager&\PHPUnit\Framework\MockObject\Stub
     */
    private $git_permission_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TemplateRenderer
     */
    private $template_renderer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BaseLayout
     */
    private $layout;
    private \HTTPRequest $request;
    private \Project $project;

    protected function setUp(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $this->header_renderer        = $this->createMock(HeaderRenderer::class);
        $this->mirror_data_mapper     = $this->createStub(Git_Mirror_MirrorDataMapper::class);
        $this->git_permission_manager = $this->createStub(GitPermissionsManager::class);
        $this->template_renderer      = $this->createMock(TemplateRenderer::class);
        $this->layout                 = $this->createMock(BaseLayout::class);
        $this->request                = new \HTTPRequest();
        $this->request->setCurrentUser($current_user);

        $this->project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUnixName(self::PROJECT_UNIX_NAME)
            ->withUsedService(GitPlugin::SERVICE_SHORTNAME)
            ->build();
    }

    public function testItThrowsWhenProjectIsNotFound(): void
    {
        $controller = $this->getController(
            ProjectByUnixUnixNameFactory::buildWithoutProject(),
            new class implements EventDispatcherInterface {
                public function dispatch(object $event)
                {
                    return $event;
                }
            }
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            $this->request,
            $this->layout,
            [
                'project_name' => self::PROJECT_UNIX_NAME,
            ]
        );
    }

    public function testItThrowsWhenGitServiceIsNotUsed(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUnixName(self::PROJECT_UNIX_NAME)
            ->withUsedService('not_the_git_service')
            ->build();

        $controller = $this->getController(
            ProjectByUnixUnixNameFactory::buildWith($project),
            new class implements EventDispatcherInterface {
                public function dispatch(object $event)
                {
                    return $event;
                }
            }
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            $this->request,
            $this->layout,
            [
                'project_name' => self::PROJECT_UNIX_NAME,
            ]
        );
    }

    public function testItThrowsWhenUserIsNotAdministrator(): void
    {
        $this->git_permission_manager->method('userIsGitAdmin')->willReturn(false);

        $controller = $this->getController(
            ProjectByUnixUnixNameFactory::buildWith($this->project),
            new class implements EventDispatcherInterface {
                public function dispatch(object $event)
                {
                    return $event;
                }
            }
        );

        $this->expectException(ForbiddenException::class);

        $controller->process(
            $this->request,
            $this->layout,
            [
                'project_name' => self::PROJECT_UNIX_NAME,
            ]
        );
    }

    public function testItRendersTheGitlabLinkGroupPane(): void
    {
        $gitlab_tab  = GitLabLinkGroupTabPresenter::withActiveState($this->project);
        $another_tab = new AdminExternalPanePresenter('Another pane', 'url/to/another/pane', false);

        $this->git_permission_manager->method('userIsGitAdmin')->willReturn(true);
        $this->mirror_data_mapper->method('fetchAllForProject')->willReturn([]);

        $this->header_renderer->expects(self::once())->method('renderServiceAdministrationHeader');
        $this->layout->expects(self::once())->method("footer");
        $this->template_renderer->expects(self::once())
            ->method('renderToPage')
            ->with(
                'git-administration-gitlab-link-group',
                new GitLabLinkGroupPanePresenter(self::PROJECT_ID, false, [
                    $gitlab_tab,
                    $another_tab,
                ])
            );

        $this->getController(
            ProjectByUnixUnixNameFactory::buildWith($this->project),
            new class ([$gitlab_tab, $another_tab]) implements EventDispatcherInterface {
                /**
                 * @param AdminExternalPanePresenter[] $tabs
                 */
                public function __construct(private array $tabs)
                {
                }

                public function dispatch(object $event)
                {
                    if (! ($event instanceof GitAdminGetExternalPanePresenters)) {
                        return $event;
                    }

                    foreach ($this->tabs as $tab) {
                        $event->addExternalPanePresenter($tab);
                    }

                    return $event;
                }
            }
        )->process(
            $this->request,
            $this->layout,
            [
                'project_name' => self::PROJECT_UNIX_NAME,
            ]
        );
    }

    private function getController(
        ProjectByUnixNameFactory $project_by_id_factory,
        EventDispatcherInterface $event_dispatcher,
    ): GitLabLinkGroupController {
        return new GitLabLinkGroupController(
            $project_by_id_factory,
            $event_dispatcher,
            $this->header_renderer,
            $this->mirror_data_mapper,
            $this->git_permission_manager,
            $this->template_renderer
        );
    }
}
