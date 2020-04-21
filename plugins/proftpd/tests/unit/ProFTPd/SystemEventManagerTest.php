<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once dirname(__FILE__) . '/../bootstrap.php';

class ProFTPd_SystemEventManagerTest extends \PHPUnit\Framework\TestCase
{

    public $system_event_manager;

    /** @var Proftpd_SystemEventManager */
    public $proftpd_system_event_manager;

    public function setUp(): void
    {
        parent::setUp();
        $this->system_event_manager = $this->getMockBuilder('SystemEventManager')->disableOriginalConstructor()->getMock();
        $this->proftpd_system_event_manager = new Tuleap\ProFTPd\SystemEventManager(
            $this->system_event_manager,
            $this->getMockBuilder('Backend')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('Tuleap\ProFTPd\Admin\PermissionsManager')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('ProjectManager')->disableOriginalConstructor()->getMock(),
            dirname(__FILE__) . '/_fixtures'
        );
    }

    public function testItCreatesRepositoryCreateEvent()
    {
        $project_unix_name = 'test';
        $this->system_event_manager
            ->expects($this->once())
            ->method('createEvent')
                ->with(
                    Tuleap\ProFTPd\SystemEvent\PROFTPD_DIRECTORY_CREATE::NAME,
                    $project_unix_name,
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::OWNER_ROOT
                );

        $this->proftpd_system_event_manager->queueDirectoryCreate($project_unix_name);
    }

    public function testItDoesntQueueDirectoryCreationIfAlreadyExists()
    {
        $this->system_event_manager
            ->expects($this->never())
            ->method('createEvent');

        $this->proftpd_system_event_manager->queueDirectoryCreate('project_name');
    }

    public function testItQueuesUpdateACLEvent()
    {
        $project_unix_name = 'test';
        $this->system_event_manager
            ->expects($this->once())
            ->method('createEvent')
                ->with(
                    Tuleap\ProFTPd\SystemEvent\PROFTPD_UPDATE_ACL::NAME,
                    $project_unix_name,
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::OWNER_ROOT
                );

        $this->proftpd_system_event_manager->queueACLUpdate($project_unix_name);
    }
}
