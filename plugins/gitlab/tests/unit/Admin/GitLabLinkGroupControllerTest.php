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
use Tuleap\Git\Events\GitAdminGetExternalPanePresenters;
use Tuleap\Git\GitPresenters\AdminExternalPanePresenter;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Gitlab\Test\Stubs\VerifyProjectIsAlreadyLinkedStub;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\ProjectByUnixNameFactory;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\JavascriptAssetGenericBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stub\EventDispatcherStub;
use Tuleap\Test\Stubs\ProjectByUnixUnixNameFactory;
use Tuleap\Test\Stubs\TemplateRendererStub;

final class GitLabLinkGroupControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const PROJECT_ID        = 150;
    private const PROJECT_UNIX_NAME = 'tuleap-gitlab';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HeaderRenderer
     */
    private $header_renderer;
    /**
     * @var GitPermissionsManager&\PHPUnit\Framework\MockObject\Stub
     */
    private $git_permission_manager;
    private TemplateRendererStub $template_renderer;

    private \Project $project;
    private ProjectByUnixNameFactory $project_factory;
    private VerifyProjectIsAlreadyLinkedStub $project_linked_verifier;

    protected function setUp(): void
    {
        $this->header_renderer        = $this->createMock(HeaderRenderer::class);
        $this->git_permission_manager = $this->createStub(GitPermissionsManager::class);
        $this->template_renderer      = new TemplateRendererStub();

        $this->project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUnixName(self::PROJECT_UNIX_NAME)
            ->withUsedService(GitPlugin::SERVICE_SHORTNAME)
            ->build();

        $this->project_factory         = ProjectByUnixUnixNameFactory::buildWith($this->project);
        $this->project_linked_verifier = VerifyProjectIsAlreadyLinkedStub::withNeverLinked();
    }

    private function process(): void
    {
        $gitlab_tab  = GitLabLinkGroupTabPresenter::withActiveState($this->project);
        $another_tab = new AdminExternalPanePresenter('Another pane', 'url/to/another/pane', false);

        $external_tabs    = [$gitlab_tab, $another_tab];
        $event_dispatcher = EventDispatcherStub::withCallback(
            static function (GitAdminGetExternalPanePresenters $event) use ($external_tabs) {
                foreach ($external_tabs as $tab) {
                    $event->addExternalPanePresenter($tab);
                }
                return $event;
            }
        );

        $mirror_data_mapper = $this->createStub(Git_Mirror_MirrorDataMapper::class);
        $mirror_data_mapper->method('fetchAllForProject')->willReturn([]);

        $controller = new GitLabLinkGroupController(
            $this->project_factory,
            $event_dispatcher,
            JavascriptAssetGenericBuilder::build(),
            JavascriptAssetGenericBuilder::build(),
            $this->project_linked_verifier,
            $this->header_renderer,
            $mirror_data_mapper,
            $this->git_permission_manager,
            $this->template_renderer
        );

        $current_user = UserTestBuilder::buildWithDefaults();

        $request = new \HTTPRequest();
        $request->setCurrentUser($current_user);

        $controller->process(
            $request,
            LayoutBuilder::build(),
            ['project_name' => self::PROJECT_UNIX_NAME]
        );
    }

    public function testItThrowsWhenProjectIsNotFound(): void
    {
        $this->project_factory = ProjectByUnixUnixNameFactory::buildWithoutProject();

        $this->expectException(NotFoundException::class);
        $this->process();
    }

    public function testItThrowsWhenGitServiceIsNotUsed(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUnixName(self::PROJECT_UNIX_NAME)
            ->withUsedService('not_the_git_service')
            ->build();

        $this->project_factory = ProjectByUnixUnixNameFactory::buildWith($project);

        $this->expectException(NotFoundException::class);
        $this->process();
    }

    public function testItThrowsWhenUserIsNotAdministrator(): void
    {
        $this->git_permission_manager->method('userIsGitAdmin')->willReturn(false);

        $this->expectException(ForbiddenException::class);
        $this->process();
    }

    public function testItRendersTheGitlabGroupLinkWizard(): void
    {
        $this->git_permission_manager->method('userIsGitAdmin')->willReturn(true);
        $this->header_renderer->expects(self::once())->method('renderServiceAdministrationHeader');

        $this->process();

        self::assertTrue($this->template_renderer->has_rendered_something);
    }

    public function testItRendersTheLinkedGroupInformation(): void
    {
        $this->git_permission_manager->method('userIsGitAdmin')->willReturn(true);
        $this->project_linked_verifier = VerifyProjectIsAlreadyLinkedStub::withAlwaysLinked();
        $this->header_renderer->expects(self::once())->method('renderServiceAdministrationHeader');

        $this->process();

        self::assertTrue($this->template_renderer->has_rendered_something);
    }
}
