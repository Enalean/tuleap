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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
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

class ThreadsControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ThreadsDao
     */
    private $dao;
    /**
     * @var \ForumMLPlugin|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $plugin;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\TemplateRenderer
     */
    private $renderer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|IncludeAssets
     */
    private $include_assets;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ThreadsPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|System_Command
     */
    private $command;
    /**
     * @var ThreadsController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CurrentListBreadcrumbCollectionBuilder
     */
    private $breadcrumb_builder;

    protected function setUp(): void
    {
        $this->plugin             = Mockery::mock(\ForumMLPlugin::class);
        $this->project_manager    = Mockery::mock(\ProjectManager::class);
        $this->dao                = Mockery::mock(ThreadsDao::class);
        $this->renderer           = Mockery::mock(\TemplateRenderer::class);
        $this->include_assets     = Mockery::mock(IncludeAssets::class);
        $this->presenter_builder  = Mockery::mock(ThreadsPresenterBuilder::class);
        $this->command            = Mockery::mock(System_Command::class);
        $this->breadcrumb_builder = Mockery::mock(CurrentListBreadcrumbCollectionBuilder::class);

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
            ->shouldReceive('searchActiveList')
            ->with(123)
            ->andReturn([]);

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
            ->shouldReceive('searchActiveList')
            ->with(123)
            ->andReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 1,
                ]
            );

        $project = Mockery::mock(Project::class, ['getID' => 101]);
        $project
            ->shouldReceive('getService')
            ->with('mail')
            ->andReturnNull();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(101)
            ->andReturn($project);

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
            ->shouldReceive('searchActiveList')
            ->with(123)
            ->andReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 1,
                ]
            );

        $service = Mockery::mock(ServiceMailingList::class);

        $project = Mockery::mock(Project::class, ['getID' => 101]);
        $project
            ->shouldReceive('getService')
            ->with('mail')
            ->andReturn($service);

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(101)
            ->andReturn($project);

        $this->plugin
            ->shouldReceive('isAllowed')
            ->with(101)
            ->andReturnFalse();

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
            ->shouldReceive('searchActiveList')
            ->with(123)
            ->andReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 0,
                ]
            );

        $service = Mockery::mock(ServiceMailingList::class);

        $project = Mockery::mock(Project::class, ['getID' => 101]);
        $project
            ->shouldReceive('getService')
            ->with('mail')
            ->andReturn($service);

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(101)
            ->andReturn($project);

        $this->plugin
            ->shouldReceive('isAllowed')
            ->with(101)
            ->andReturnTrue();

        $user = Mockery::mock(\PFUser::class, ['isAnonymous' => true]);

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
            ->shouldReceive('searchActiveList')
            ->with(123)
            ->andReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 0,
                ]
            );

        $service = Mockery::mock(ServiceMailingList::class);

        $project = Mockery::mock(Project::class, ['getID' => 101]);
        $project
            ->shouldReceive('getService')
            ->with('mail')
            ->andReturn($service);

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(101)
            ->andReturn($project);

        $this->plugin
            ->shouldReceive('isAllowed')
            ->with(101)
            ->andReturnTrue();

        $user = Mockery::mock(\PFUser::class, ['isAnonymous' => false]);
        $user
            ->shouldReceive('isMember')
            ->with(101)
            ->andReturnFalse();

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
            ->shouldReceive('searchActiveList')
            ->with(123)
            ->andReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 0,
                ]
            );

        $service = Mockery::mock(ServiceMailingList::class);

        $project = Mockery::mock(Project::class, ['getID' => 101]);
        $project
            ->shouldReceive('getService')
            ->with('mail')
            ->andReturn($service);

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(101)
            ->andReturn($project);

        $this->plugin
            ->shouldReceive('isAllowed')
            ->with(101)
            ->andReturnTrue();

        $user = Mockery::mock(
            \PFUser::class,
            [
                'isAnonymous' => false,
                'getEmail'   => 'jdoe@example.com',
            ]
        );
        $user
            ->shouldReceive('isMember')
            ->with(101)
            ->andReturnTrue();

        $this->command
            ->shouldReceive('exec')
            ->with("/mailman/list_members 'foobar-devel'")
            ->andReturn(['neo@example.com']);

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
            ->shouldReceive('searchActiveList')
            ->with(123)
            ->andReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 0,
                ]
            );

        $service = Mockery::mock(ServiceMailingList::class);

        $project = Mockery::mock(Project::class, ['getID' => 101]);
        $project
            ->shouldReceive('getService')
            ->with('mail')
            ->andReturn($service);

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(101)
            ->andReturn($project);

        $this->plugin
            ->shouldReceive('isAllowed')
            ->with(101)
            ->andReturnTrue();

        $user = Mockery::mock(
            \PFUser::class,
            [
                'isAnonymous' => false,
                'getEmail'   => 'jdoe@example.com',
            ]
        );
        $user
            ->shouldReceive('isMember')
            ->with(101)
            ->andReturnTrue();

        $this->command
            ->shouldReceive('exec')
            ->with("/mailman/list_members 'foobar-devel'")
            ->andReturn(['neo@example.com', 'jdoe@example.com']);

        $this->presenter_builder
            ->shouldReceive('getThreadsPresenter')
            ->andReturn(
                new ThreadsPresenter('foobar-devel', 0, [], '/url', '', Mockery::spy(PaginationPresenter::class))
            );

        $this->include_assets
            ->shouldReceive('getPath')
            ->andReturn('/whatever');

        $this->include_assets
            ->shouldReceive('getFileUrl')
            ->with('new-thread.js')
            ->andReturn('new-thread.js');

        $service
            ->shouldReceive('displayMailingListHeaderWithAdditionalBreadcrumbs')
            ->once();
        $service
            ->shouldReceive('displayFooter')
            ->once();

        $this->renderer
            ->shouldReceive('renderToPage')
            ->once();

        $this->breadcrumb_builder
            ->shouldReceive('getCurrentListBreadcrumbCollectionFromRow')
            ->andReturn(Mockery::mock(BreadCrumbCollection::class));

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            ['id' => '123']
        );
    }

    public function testThreadsAreDisplayedWhenLoggedInUserTriesToAccessToAPublicList(): void
    {
        $this->dao
            ->shouldReceive('searchActiveList')
            ->with(123)
            ->andReturn(
                [
                    'list_name' => 'foobar-devel',
                    'group_id'  => 101,
                    'is_public' => 1,
                ]
            );

        $service = Mockery::mock(ServiceMailingList::class);

        $project = Mockery::mock(Project::class, ['getID' => 101]);
        $project
            ->shouldReceive('getService')
            ->with('mail')
            ->andReturn($service);

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(101)
            ->andReturn($project);

        $this->plugin
            ->shouldReceive('isAllowed')
            ->with(101)
            ->andReturnTrue();

        $user = Mockery::mock(
            \PFUser::class,
            [
                'isAnonymous' => false,
            ]
        );

        $this->presenter_builder
            ->shouldReceive('getThreadsPresenter')
            ->andReturn(
                new ThreadsPresenter('foobar-devel', 0, [], '/url', '', Mockery::spy(PaginationPresenter::class))
            );

        $this->include_assets
            ->shouldReceive('getPath')
            ->andReturn('/whatever');

        $this->include_assets
            ->shouldReceive('getFileUrl')
            ->with('new-thread.js')
            ->andReturn('new-thread.js');

        $service
            ->shouldReceive('displayMailingListHeaderWithAdditionalBreadcrumbs')
            ->once();
        $service
            ->shouldReceive('displayFooter')
            ->once();

        $this->renderer
            ->shouldReceive('renderToPage')
            ->once();

        $this->breadcrumb_builder
            ->shouldReceive('getCurrentListBreadcrumbCollectionFromRow')
            ->andReturn(Mockery::mock(BreadCrumbCollection::class));

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
}
