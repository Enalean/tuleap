<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML\Threads;

use Project;
use Service;
use System_Command;
use Tuleap\ForgeConfigSandbox;
use Tuleap\ForumML\CurrentListBreadcrumbCollectionBuilder;
use Tuleap\ForumML\ListInfoFromVariablesProvider;
use Tuleap\ForumML\ThreadsDao;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\PaginationPresenter;
use Tuleap\MailingList\ServiceMailingList;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ThreadsControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ThreadsDao
     */
    private $dao;
    /**
     * @var \ForumMLPlugin&\PHPUnit\Framework\MockObject\MockObject
     */
    private $plugin;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\TemplateRenderer
     */
    private $renderer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&IncludeAssets
     */
    private $include_assets;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ThreadsPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&System_Command
     */
    private $command;
    private ThreadsController $controller;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CurrentListBreadcrumbCollectionBuilder
     */
    private $breadcrumb_builder;

    protected function setUp(): void
    {
        $this->plugin             = $this->createMock(\ForumMLPlugin::class);
        $this->project_manager    = $this->createMock(\ProjectManager::class);
        $this->dao                = $this->createMock(ThreadsDao::class);
        $this->renderer           = $this->createMock(\TemplateRenderer::class);
        $this->include_assets     = $this->createMock(IncludeAssets::class);
        $this->presenter_builder  = $this->createMock(ThreadsPresenterBuilder::class);
        $this->command            = $this->createMock(System_Command::class);
        $this->breadcrumb_builder = $this->createMock(CurrentListBreadcrumbCollectionBuilder::class);

        $this->controller = new ThreadsController(
            $this->renderer,
            $this->include_assets,
            $this->presenter_builder,
            $this->breadcrumb_builder,
            new ListInfoFromVariablesProvider(
                $this->plugin,
                $this->project_manager,
                $this->dao,
                $this->command,
            )
        );

        \ForgeConfig::set('mailman_bin_dir', '/mailman');
    }

    public function testNotFoundExceptionWhenListCannotBeFound(): void
    {
        $this->dao
            ->method('searchActiveList')
            ->with(123)
            ->willReturn([]);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            ['id' => '123']
        );
    }

    public function testForbiddenExceptionWhenProjectDoesNotUseMailingLists(): void
    {
        $this->dao
            ->method('searchActiveList')
            ->with(123)
            ->willReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 1,
                ]
            );

        $project = $this->buildProjectMock(null);

        $this->project_manager
            ->method('getProject')
            ->with(101)
            ->willReturn($project);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            ['id' => '123']
        );
    }

    public function testForbiddenExceptionWhenProjectIsNotAllowedToUseForumml(): void
    {
        $this->dao
            ->method('searchActiveList')
            ->with(123)
            ->willReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 1,
                ]
            );

        $service = $this->createMock(ServiceMailingList::class);

        $project = $this->buildProjectMock($service);

        $this->project_manager
            ->method('getProject')
            ->with(101)
            ->willReturn($project);

        $this->plugin
            ->method('isAllowed')
            ->with(101)
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            ['id' => '123']
        );
    }

    public function testForbiddenExceptionWhenAnonymousUserTriesToAccessToAPrivateList(): void
    {
        $this->dao
            ->method('searchActiveList')
            ->with(123)
            ->willReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 0,
                ]
            );

        $service = $this->createMock(ServiceMailingList::class);

        $project = $this->buildProjectMock($service);

        $this->project_manager
            ->method('getProject')
            ->with(101)
            ->willReturn($project);

        $this->plugin
            ->method('isAllowed')
            ->with(101)
            ->willReturn(true);

        $user = UserTestBuilder::anAnonymousUser()->build();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            ['id' => '123']
        );
    }

    public function testForbiddenExceptionWhenLoggedInUserTriesToAccessToAPrivateListInAProjectSheIsNotMemberOf(): void
    {
        $this->dao
            ->method('searchActiveList')
            ->with(123)
            ->willReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 0,
                ]
            );

        $service = $this->createMock(ServiceMailingList::class);

        $project = $this->buildProjectMock($service);

        $this->project_manager
            ->method('getProject')
            ->with(101)
            ->willReturn($project);

        $this->plugin
            ->method('isAllowed')
            ->with(101)
            ->willReturn(true);

        $user = $this->buildUserNotMemberOf();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            ['id' => '123']
        );
    }

    public function testForbiddenExceptionWhenLoggedInUserTriesToAccessToAPrivateListSheIsNotMemberOf(): void
    {
        $this->dao
            ->method('searchActiveList')
            ->with(123)
            ->willReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 0,
                ]
            );

        $service = $this->createMock(ServiceMailingList::class);

        $project = $this->buildProjectMock($service);

        $this->project_manager
            ->method('getProject')
            ->with(101)
            ->willReturn($project);

        $this->plugin
            ->method('isAllowed')
            ->with(101)
            ->willReturn(true);

        $user = $this->buildUserMemberOf();

        $this->command
            ->method('exec')
            ->with("/mailman/list_members 'foobar-devel'")
            ->willReturn(['neo@example.com']);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            ['id' => '123']
        );
    }

    public function testThreadsAreDisplayedWhenLoggedInUserTriesToAccessToAPrivateListSheIsMemberOf(): void
    {
        $this->dao
            ->method('searchActiveList')
            ->with(123)
            ->willReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 0,
                ]
            );

        $service = $this->createMock(ServiceMailingList::class);

        $project = $this->buildProjectMock($service);

        $this->project_manager
            ->method('getProject')
            ->with(101)
            ->willReturn($project);

        $this->plugin
            ->method('isAllowed')
            ->with(101)
            ->willReturn(true);

        $user = $this->buildUserMemberOf();

        $this->command
            ->method('exec')
            ->with("/mailman/list_members 'foobar-devel'")
            ->willReturn(['neo@example.com', 'jdoe@example.com']);

        $this->presenter_builder
            ->method('getThreadsPresenter')
            ->willReturn(
                new ThreadsPresenter('foobar-devel', 0, [], '/url', '', $this->createMock(PaginationPresenter::class))
            );

        $this->include_assets
            ->method('getPath')
            ->willReturn('/whatever');

        $this->include_assets
            ->method('getFileUrl')
            ->with('new-thread.js')
            ->willReturn('new-thread.js');

        $service
            ->expects(self::once())
            ->method('displayMailingListHeaderWithAdditionalBreadcrumbs');
        $service
            ->expects(self::once())
            ->method('displayFooter');

        $this->renderer
            ->expects(self::once())
            ->method('renderToPage');

        $this->breadcrumb_builder
            ->method('getCurrentListBreadcrumbCollectionFromRow')
            ->willReturn($this->createMock(BreadCrumbCollection::class));

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            ['id' => '123']
        );
    }

    public function testThreadsAreDisplayedWhenLoggedInUserTriesToAccessToAPublicList(): void
    {
        $this->dao
            ->method('searchActiveList')
            ->with(123)
            ->willReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 1,
                ]
            );

        $service = $this->createMock(ServiceMailingList::class);

        $project = $this->buildProjectMock($service);

        $this->project_manager
            ->method('getProject')
            ->with(101)
            ->willReturn($project);

        $this->plugin
            ->method('isAllowed')
            ->with(101)
            ->willReturn(true);

        $user = UserTestBuilder::aUser()->build();

        $this->presenter_builder
            ->method('getThreadsPresenter')
            ->willReturn(
                new ThreadsPresenter('foobar-devel', 0, [], '/url', '', $this->createMock(PaginationPresenter::class))
            );

        $this->include_assets
            ->method('getPath')
            ->willReturn('/whatever');

        $this->include_assets
            ->method('getFileUrl')
            ->with('new-thread.js')
            ->willReturn('new-thread.js');

        $service
            ->expects(self::once())
            ->method('displayMailingListHeaderWithAdditionalBreadcrumbs');
        $service
            ->expects(self::once())
            ->method('displayFooter');

        $this->renderer
            ->expects(self::once())
            ->method('renderToPage');

        $this->breadcrumb_builder
            ->method('getCurrentListBreadcrumbCollectionFromRow')
            ->willReturn($this->createMock(BreadCrumbCollection::class));

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            ['id' => '123']
        );
    }

    public function testItBuildsUrl(): void
    {
        self::assertEquals(
            '/plugins/forumml/list/123/threads',
            ThreadsController::getUrl(123),
        );
    }

    public function testItBuildsSearchUrl(): void
    {
        self::assertEquals(
            '/plugins/forumml/list/123/threads?search=hello+world',
            ThreadsController::getSearchUrl(123, 'hello world'),
        );
    }

    private function buildProjectMock(?Service $service): Project
    {
        $project = $this->createMock(Project::class);
        $project
            ->method('getID')
            ->willReturn(101);
        $project
            ->method('getService')
            ->with('mail')
            ->willReturn($service);
        return $project;
    }

    private function buildUserMemberOf(): \PFUser
    {
        $user = $this->createMock(\PFUser::class);
        $user
            ->method('isAnonymous')
            ->willReturn(false);
        $user
            ->method('getEmail')
            ->willReturn('jdoe@example.com');
        $user
            ->method('isMember')
            ->with(101)
            ->willReturn(true);
        return $user;
    }

    private function buildUserNotMemberOf(): \PFUser
    {
        $user = $this->createMock(\PFUser::class);
        $user
            ->method('isAnonymous')
            ->willReturn(false);
        $user
            ->method('isMember')
            ->with(101)
            ->willReturn(false);
        return $user;
    }
}
