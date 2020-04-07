<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalResponseMock;

require_once 'bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    protected function setup(): void
    {
        $system_event_manager = Mockery::mock(SystemEventManager::class);
        $sys_dao = Mockery::mock(SystemEventDao::class);
        $sys_dao->shouldReceive('searchWithParam')->andReturn([]);
        $system_event_manager->shouldReceive('_getDao')->andReturn($sys_dao);

        SystemEventManager::setInstance($system_event_manager);
    }

    protected function tearDown(): void
    {
        SystemEventManager::clearInstance();

        parent::tearDown();
    }

    public function testTheDelRouteExecutesDeleteRepositoryWithTheIndexView(): void
    {
        $usermanager = \Mockery::spy(\UserManager::class);
        $request     = new Codendi_Request(['repo_id' => 1]);

        $git = \Mockery::mock(\Git::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git->setRequest($request);
        $git->setUserManager($usermanager);
        $git->setAction('del');
        $git->setPermittedActions(array('del'));

        $repository = \Mockery::spy(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturn(1);

        $factory    = \Mockery::spy(\GitRepositoryFactory::class)->shouldReceive('getRepositoryById')->andReturns($repository)->getMock();
        $git->setFactory($factory);

        $git->shouldReceive('addAction')->with('deleteRepository', Mockery::any())->once();
        $git->shouldReceive('definePermittedActions')->once();
        $git->shouldReceive('addView')->with('index')->once();

        $git->request();
    }

    public function testDispatchToForkRepositoriesIfRequestsPersonal(): void
    {
        $git = \Mockery::mock(\Git::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $request = new Codendi_Request(array('choose_destination' => 'personal'));
        $git->setRequest($request);
        $git->shouldReceive('_doDispatchForkRepositories')->once();

        $factory = \Mockery::spy(\GitRepositoryFactory::class);
        $git->setFactory($factory);

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isMember')->andReturns(true);
        $git->user = $user;

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, null);
    }

    public function testDispatchToForkRepositoriesIfRequestsPersonalAndNonMember(): void
    {
        $git = \Mockery::mock(\Git::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $request = new Codendi_Request(array('choose_destination' => 'personal'));
        $git->setRequest($request);
        $git->shouldReceive('_doDispatchForkRepositories')->never();

        $factory = \Mockery::spy(\GitRepositoryFactory::class);
        $git->setFactory($factory);

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isMember')->andReturns(false);
        $git->user = $user;

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, null);
    }

    public function testDispatchToForkCrossProjectIfRequestsProject(): void
    {
        $git = \Mockery::mock(\Git::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $request = new Codendi_Request(array('choose_destination' => 'project'));
        $git->setRequest($request);

        $factory = \Mockery::spy(\GitRepositoryFactory::class);
        $git->setFactory($factory);

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isMember')->andReturns(true);
        $git->user = $user;

        $git->shouldReceive('_doDispatchForkCrossProject')->once();
        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, null);
    }
}
