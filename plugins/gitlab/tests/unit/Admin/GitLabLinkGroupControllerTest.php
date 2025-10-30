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

declare(strict_types=1);

namespace Tuleap\Gitlab\Admin;

use Feedback;
use GitPermissionsManager;
use GitPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\GlobalAdmin\GlobalAdminTabsRenderer;
use Tuleap\Gitlab\Group\GitlabServerURIDeducer;
use Tuleap\Gitlab\Test\Builder\GroupLinkBuilder;
use Tuleap\Gitlab\Test\Stubs\CountIntegratedRepositoriesStub;
use Tuleap\Gitlab\Test\Stubs\RetrieveGroupLinkedToProjectStub;
use Tuleap\GlobalLanguageMock;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Project\ProjectByUnixNameFactory;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\JavascriptAssetGenericBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ProjectByUnixUnixNameFactory;
use Tuleap\Test\Stubs\TemplateRendererStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitLabLinkGroupControllerTest extends TestCase
{
    use GlobalLanguageMock;

    private const int PROJECT_ID           = 150;
    private const string PROJECT_UNIX_NAME = 'tuleap-gitlab';

    private MockObject&HeaderRenderer $header_renderer;
    private Stub&GitPermissionsManager $git_permission_manager;
    private TemplateRendererStub $template_renderer;

    private \Project $project;
    private ProjectByUnixNameFactory $project_factory;
    private RetrieveGroupLinkedToProjectStub $group_retriever;
    private \HTTPRequest $request;
    private LayoutInspector $inspector;

    #[\Override]
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

        $this->project_factory = ProjectByUnixUnixNameFactory::buildWith($this->project);
        $this->group_retriever = RetrieveGroupLinkedToProjectStub::withNoGroupLink();

        $this->request   = new \HTTPRequest();
        $this->inspector = new LayoutInspector();
    }

    private function process(): void
    {
        $controller = new GitLabLinkGroupController(
            $this->project_factory,
            JavascriptAssetGenericBuilder::build(),
            JavascriptAssetGenericBuilder::build(),
            $this->header_renderer,
            $this->git_permission_manager,
            new GlobalAdminTabsRenderer(new TemplateRendererStub(), EventDispatcherStub::withIdentityCallback()),
            $this->template_renderer,
            $this->group_retriever,
            CountIntegratedRepositoriesStub::withCount(4),
            new GitlabServerURIDeducer(HTTPFactoryBuilder::URIFactory())
        );

        $current_user = UserTestBuilder::buildWithDefaults();

        $this->request->setCurrentUser($current_user);

        $controller->process(
            $this->request,
            LayoutBuilder::buildWithInspector($this->inspector),
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
        $this->header_renderer->expects($this->once())->method('renderServiceAdministrationHeader');

        $this->process();

        self::assertTrue($this->template_renderer->has_rendered_something);
    }

    public function testItRendersTheLinkedGroupInformation(): void
    {
        $this->git_permission_manager->method('userIsGitAdmin')->willReturn(true);
        $this->group_retriever = RetrieveGroupLinkedToProjectStub::withGroupLink(
            GroupLinkBuilder::aGroupLink(99)->build()
        );
        $this->header_renderer->expects($this->once())->method('renderServiceAdministrationHeader');

        $this->process();

        self::assertTrue($this->template_renderer->has_rendered_something);
    }

    public function testItDisplaysTheFeedback(): void
    {
        $this->request->set('unlink_group', '1');

        $this->git_permission_manager->method('userIsGitAdmin')->willReturn(true);
        $this->header_renderer->expects($this->once())->method('renderServiceAdministrationHeader');

        $this->process();

        self::assertSame(
            [
                [
                    'level'   => Feedback::SUCCESS,
                    'message' => 'The GitLab group has been successfully unlinked.',
                ],
            ],
            $this->inspector->getFeedback()
        );
    }
}
