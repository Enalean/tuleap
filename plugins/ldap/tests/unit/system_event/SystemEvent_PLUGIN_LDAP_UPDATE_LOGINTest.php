<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\LDAP\SystemEvent;

use PHPUnit\Framework\MockObject\MockObject;
use SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN;
use Tuleap\Test\Builders\ProjectTestBuilder;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class SystemEvent_PLUGIN_LDAP_UPDATE_LOGINTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \UserManager&MockObject $um;
    private \BackendSVN&MockObject $backend;
    private \LDAP_ProjectManager&MockObject $ldap_project_manager;
    private SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN $system_event;
    private \ProjectManager&MockObject $project_manager;
    private \Project $prj1;
    private \Project $prj2;
    private \Project $prj3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->um                   = $this->createMock(\UserManager::class);
        $this->project_manager      = $this->createMock(\ProjectManager::class);
        $this->backend              = $this->createMock(\BackendSVN::class);
        $this->ldap_project_manager = $this->createMock(\LDAP_ProjectManager::class);
        $this->system_event         = new SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN(
            1,
            '',
            '',
            '101::102',
            1,
            "NONE",
            '',
            '',
            '',
            '',
        );

        $this->system_event->injectDependencies($this->um, $this->backend, $this->project_manager, $this->ldap_project_manager);

        $user1 = $this->createMock(\PFUser::class);
        $user1->method('getAllProjects')->willReturn([201, 202]);
        $user1->method('isActive')->willReturn(true);
        $user2 = $this->createMock(\PFUser::class);
        $user2->method('getAllProjects')->willReturn([202, 203]);
        $user2->method('isActive')->willReturn(true);

        $this->um->method('getUserById')->willReturnMap([
            ['101', $user1],
            ['102', $user2],
        ]);

        $this->prj1 = ProjectTestBuilder::aProject()->withId(201)->build();
        $this->prj2 = ProjectTestBuilder::aProject()->withId(202)->build();
        $this->prj3 = ProjectTestBuilder::aProject()->withId(203)->build();

        $this->project_manager->method('getProject')->willReturnMap([
            [201, $this->prj1],
            [202, $this->prj2],
            [203, $this->prj3],
        ]);
    }

    public function testUpdateShouldUpdateAllProjects(): void
    {
        $this->backend->expects(self::exactly(3))->method('updateProjectSVNAccessFile')->willReturnOnConsecutiveCalls(
            $this->prj1,
            $this->prj2,
            $this->prj3,
        );

        $this->ldap_project_manager->method('hasSVNLDAPAuth')->willReturn(true);

        $this->system_event->process();
    }

    public function testItSkipsProjectsThatAreNotManagedByLdap(): void
    {
        $this->backend->expects(self::never())->method('updateProjectSVNAccessFile');
        $this->ldap_project_manager->method('hasSVNLDAPAuth')->willReturn(false);
        $this->system_event->process();
    }

    public function testItSkipsProjectsBasedOnProjectId(): void
    {
        $this->ldap_project_manager->method('hasSVNLDAPAuth')->willReturnMap([
            [201, false],
            [202, false],
            [203, false],
        ]);

        $this->backend->expects(self::never())->method('updateProjectSVNAccessFile');

        $this->system_event->process();
    }
}
