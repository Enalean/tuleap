<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tuleap\Svn\SVNRepositoryCreationException;
use Tuleap\Svn\SVNRepositoryLayoutInitializationException;

require_once __DIR__ . '/../bootstrap.php';


class SystemEvent_SVN_CREATE_REPOSITORYTest extends \TuleapTestCase
{
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
        $system_event = partial_mock(
            'Tuleap\\Svn\\EventRepository\\SystemEvent_SVN_CREATE_REPOSITORY',
            array('done', 'getRequiredParameter')
        );

        $system_event->injectDependencies(
            mock('Tuleap\\Svn\\AccessControl\\AccessFileHistoryCreator'),
            mock('Tuleap\\Svn\\Repository\\RepositoryManager'),
            mock('UserManager'),
            mock('BackendSVN'),
            mock('BackendSystem')
        );

        $system_event->expectOnce('done');

        $system_event->process();
    }

    public function itGeneratesAnErrorIfTheRepositoryCanNotBeCreated()
    {
        $system_event = partial_mock(
            'Tuleap\\Svn\\EventRepository\\SystemEvent_SVN_CREATE_REPOSITORY',
            array('error', 'done', 'getRequiredParameter')
        );

        $backend_svn    = mock('BackendSVN');
        $backend_svn->throwOn('createRepositorySVN', new SVNRepositoryCreationException());
        $system_event->injectDependencies(
            mock('Tuleap\\Svn\\AccessControl\\AccessFileHistoryCreator'),
            mock('Tuleap\\Svn\\Repository\\RepositoryManager'),
            mock('UserManager'),
            $backend_svn,
            mock('BackendSystem')
        );

        $system_event->expectOnce('error');
        $system_event->expectNever('done');

        $system_event->process();
    }

    public function itGeneratesAWarningIfTheDirectoryLayoutCanNotBeCreated()
    {
        $system_event = partial_mock(
            'Tuleap\\Svn\\EventRepository\\SystemEvent_SVN_CREATE_REPOSITORY',
            array('warning', 'done', 'getRequiredParameter')
        );

        $backend_svn    = mock('BackendSVN');
        $backend_svn->throwOn('createRepositorySVN', new SVNRepositoryLayoutInitializationException());
        $system_event->injectDependencies(
            mock('Tuleap\\Svn\\AccessControl\\AccessFileHistoryCreator'),
            mock('Tuleap\\Svn\\Repository\\RepositoryManager'),
            mock('UserManager'),
            $backend_svn,
            mock('BackendSystem')
        );

        $system_event->expectOnce('warning');
        $system_event->expectNever('done');

        $system_event->process();
    }
}
