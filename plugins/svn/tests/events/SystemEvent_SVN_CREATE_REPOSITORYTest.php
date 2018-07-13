<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Svn\EventRepository;

use Mockery;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\SVNRepositoryCreationException;
use Tuleap\Svn\SVNRepositoryLayoutInitializationException;

require_once __DIR__ . '/../bootstrap.php';


class SystemEvent_SVN_CREATE_REPOSITORYTest extends \TuleapTestCase // @codingStandardsIgnoreLine
{
    public function setUp()
    {
        $this->user_manager                = Mockery::spy(\UserManager::class);
        $this->backend_svn                 = Mockery::spy(\BackendSVN::class);
        $this->access_file_history_creator = Mockery::spy(\Tuleap\Svn\AccessControl\AccessFileHistoryCreator::class);
        $this->repository_manager          = Mockery::spy(\Tuleap\Svn\Repository\RepositoryManager::class);
    }


    public function itRetrievesParameters()
    {
        $parameters            = array(
            'system_path' => '/var/lib/tuleap/svn_plugin/101/test',
            'project_id'  => 101,
            'name'        => 'project1/stuff'
        );
        $serialized_parameters = SystemEvent_SVN_CREATE_REPOSITORY::serializeParameters($parameters);

        $system_event = new SystemEvent_SVN_CREATE_REPOSITORY(
            1,
            'Type',
            \SystemEvent::OWNER_ROOT,
            $serialized_parameters,
            \SystemEvent::PRIORITY_HIGH,
            \SystemEvent::STATUS_NEW,
            '2017-07-26 12:00:00',
            '0000-00-00 00:00:00',
            '0000-00-00 00:00:00',
            'Log'
        );

        $this->assertEqual(array_values($parameters), $system_event->getParametersAsArray());
    }

    public function itRetrievesParametersInStandardFormat()
    {
        $parameters                            = array(
            'system_path' => '/var/lib/tuleap/svn_plugin/101/test',
            'project_id'  => 101,
            'name'        => 'project1/stuff'
        );
        $serialized_parameters_standard_format = implode(\SystemEvent::PARAMETER_SEPARATOR, $parameters);

        $system_event = new SystemEvent_SVN_CREATE_REPOSITORY(
            1,
            'Type',
            \SystemEvent::OWNER_ROOT,
            $serialized_parameters_standard_format,
            \SystemEvent::PRIORITY_HIGH,
            \SystemEvent::STATUS_NEW,
            '2017-07-26 12:00:00',
            '0000-00-00 00:00:00',
            '0000-00-00 00:00:00',
            'Log'
        );

        $this->assertEqual(array_values($parameters), $system_event->getParametersAsArray());
    }

    public function itMarksTheEventAsDoneWhenTheRepositoryIsSuccessfullyCreated()
    {
        $system_event = Mockery::mock(\Tuleap\Svn\EventRepository\SystemEvent_SVN_CREATE_REPOSITORY::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->user_manager->shouldReceive('getUserById')->andReturn(Mockery::spy(\PFUser::class));
        $this->backend_svn->shouldReceive('createRepositorySVN')->andReturn(true);
        $this->access_file_history_creator->shouldReceive('useAVersion')->andReturn(true);
        $this->repository_manager->shouldReceive('getRepositoryById')->andReturn(Mockery::spy(Repository::class));

        $system_event->injectDependencies(
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->backend_svn,
            Mockery::spy(\BackendSystem::class),
            Mockery::spy(\Tuleap\Svn\Migration\RepositoryCopier::class)
        );

        $system_event->shouldReceive('done')->once();
        $system_event->shouldReceive('getRequiredParameter')->andReturn([]);

        $system_event->process();
    }

    public function itGeneratesAnErrorIfTheRepositoryCanNotBeCreated()
    {
        $system_event = Mockery::mock(\Tuleap\Svn\EventRepository\SystemEvent_SVN_CREATE_REPOSITORY::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->user_manager->shouldReceive('getUserById')->andReturn(Mockery::spy(\PFUser::class));
        $this->backend_svn->shouldReceive('createRepositorySVN')->andThrow(new SVNRepositoryCreationException());

        $system_event->injectDependencies(
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->backend_svn,
            Mockery::spy(\BackendSystem::class),
            Mockery::spy(\Tuleap\Svn\Migration\RepositoryCopier::class)
        );

        $system_event->shouldReceive('error')->once();
        $system_event->shouldReceive('done')->never();
        $system_event->shouldReceive('getRequiredParameter')->andReturn([]);

        $system_event->process();
    }

    public function itGeneratesAWarningIfTheDirectoryLayoutCanNotBeCreated()
    {
        $system_event = Mockery::mock(\Tuleap\Svn\EventRepository\SystemEvent_SVN_CREATE_REPOSITORY::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->user_manager->shouldReceive('getUserById')->andReturn(Mockery::spy(\PFUser::class));
        $this->backend_svn->shouldReceive('createRepositorySVN')->andThrow(new SVNRepositoryLayoutInitializationException());

        $system_event->injectDependencies(
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->backend_svn,
            Mockery::spy(\BackendSystem::class),
            Mockery::spy(\Tuleap\Svn\Migration\RepositoryCopier::class)
        );

        $system_event->shouldReceive('warning')->once();
        $system_event->shouldReceive('done')->never();
        $system_event->shouldReceive('getRequiredParameter')->andReturn([]);

        $system_event->process();
    }
}
