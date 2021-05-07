<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
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

use Tuleap\ProFTPd\SystemEvent\PROFTPD_DIRECTORY_CREATE;

class SystemEvent_PROFTPD_DIRECTORY_CREATETest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    /** @var SystemEvent_PROFTPD_DIRECTORY_CREATE */
    private $event;
    /** @var String */
    private $path;
    /** @var String */
    private $group_unix_name;
    /** @var String */
    private $ftp_directory;
    private $backend;
    private $acl_updater;

    public function setUp(): void
    {
        parent::setUp();
        $this->event       = $this->createPartialMock(PROFTPD_DIRECTORY_CREATE::class, ['done']);
        $this->backend     = $this->getMockBuilder('Backend')->disableOriginalConstructor()->getMock();
        $this->acl_updater = $this->getMockBuilder('Tuleap\ProFTPd\Admin\ACLUpdater')->disableOriginalConstructor()->getMock();

        $this->group_unix_name = "group_name";
        $this->ftp_directory   = '/var/tmp';
        $this->path            = $this->ftp_directory . "/" . $this->group_unix_name;

        ForgeConfig::set('sys_http_user', 'someuser');

        $this->event->setParameters($this->group_unix_name);
        $this->event->injectDependencies($this->backend, $this->acl_updater, $this->ftp_directory);
    }

    public function tearDown(): void
    {
        rmdir($this->path);
    }

    public function testItCreatesDirectory(): void
    {
        $this->backend->method('changeOwnerGroupMode');
        $this->acl_updater->method('recursivelyApplyACL');
        $this->event->expects(self::once())->method('done');
        $this->event->process();
        $this->assertTrue(file_exists($this->path));
    }

    public function testItSetsPermissionsOnDirectory(): void
    {
        $this->acl_updater->method('recursivelyApplyACL');
        $this->event->expects(self::once())->method('done');
        $this->backend->expects($this->once())->method('changeOwnerGroupMode')->with(
            $this->path,
            "dummy",
            $this->group_unix_name,
            0700
        );

        $this->event->process();
    }

    public function testItSetsACLOnDirectory(): void
    {
        $this->event->expects(self::once())->method('done');
        $this->backend->method('changeOwnerGroupMode');
        $this->acl_updater->expects($this->once())->method('recursivelyApplyACL')->with($this->path, ForgeConfig::get('sys_http_user'), '', '');

        $this->event->process();
    }
}
