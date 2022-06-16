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

namespace Tuleap\ForumML;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use System_Command;
use Tuleap\ForgeConfigSandbox;
use Tuleap\MailingList\ServiceMailingList;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class ListInfoFromVariablesProviderTest extends TestCase
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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|System_Command
     */
    private $command;
    /**
     * @var ListInfoFromVariablesProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->plugin          = Mockery::mock(\ForumMLPlugin::class);
        $this->project_manager = Mockery::mock(\ProjectManager::class);
        $this->dao             = Mockery::mock(ThreadsDao::class);
        $this->command         = Mockery::mock(System_Command::class);

        $this->provider = new ListInfoFromVariablesProvider(
            $this->plugin,
            $this->project_manager,
            $this->dao,
            $this->command,
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

        $this->provider->getListInfoFromVariables(
            HTTPRequestBuilder::get()->build(),
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

        $this->provider->getListInfoFromVariables(
            HTTPRequestBuilder::get()->build(),
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

        $this->provider->getListInfoFromVariables(
            HTTPRequestBuilder::get()->build(),
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

        $this->provider->getListInfoFromVariables(
            HTTPRequestBuilder::get()->withUser($user)->build(),
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

        $this->provider->getListInfoFromVariables(
            HTTPRequestBuilder::get()->withUser($user)->build(),
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

        $this->provider->getListInfoFromVariables(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            ['id' => '123']
        );
    }

    public function testItReturnsListInfoWhenLoggedInUserTriesToAccessToAPrivateListSheIsMemberOf(): void
    {
        $list_row = [
            'list_name' => 'foobar-devel',
            'group_id'  => 101,
            'is_public' => 0,
        ];
        $this->dao
            ->shouldReceive('searchActiveList')
            ->with(123)
            ->andReturn($list_row);

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

        $list_info = $this->provider->getListInfoFromVariables(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            ['id' => '123']
        );

        self::assertEquals(123, $list_info->getListId());
        self::assertEquals('foobar-devel', $list_info->getListName());
        self::assertEquals($project, $list_info->getProject());
        self::assertEquals($service, $list_info->getService());
        self::assertEquals($list_row, $list_info->getListRow());
    }

    public function testItReturnsListInfoThreadsAreDisplayedWhenLoggedInUserTriesToAccessToAPublicList(): void
    {
        $list_row = [
            'list_name' => 'foobar-devel',
            'group_id'  => 101,
            'is_public' => 1,
        ];
        $this->dao
            ->shouldReceive('searchActiveList')
            ->with(123)
            ->andReturn($list_row);

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

        $list_info = $this->provider->getListInfoFromVariables(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            ['id' => '123']
        );

        self::assertEquals(123, $list_info->getListId());
        self::assertEquals('foobar-devel', $list_info->getListName());
        self::assertEquals($project, $list_info->getProject());
        self::assertEquals($service, $list_info->getService());
        self::assertEquals($list_row, $list_info->getListRow());
    }
}
