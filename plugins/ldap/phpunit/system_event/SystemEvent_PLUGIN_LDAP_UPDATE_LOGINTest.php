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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN;
use Tuleap\GlobalSVNPollution;

require_once __DIR__ . '/../bootstrap.php';

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class SystemEvent_PLUGIN_LDAP_UPDATE_LOGINTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalSVNPollution;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $um;
    /**
     * @var \BackendSVN|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $backend;
    /**
     * @var \LDAP_ProjectManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $ldap_project_manager;

    private $system_event;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $prj1;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $prj2;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $prj3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->um = \Mockery::spy(\UserManager::class);
        $this->project_manager = \Mockery::spy(\ProjectManager::class);
        $this->backend = \Mockery::spy(\BackendSVN::class);
        $this->ldap_project_manager = \Mockery::spy(\LDAP_ProjectManager::class);
        $this->system_event = new SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN(
            null,
            null,
            null,
            '101::102',
            null,
            null,
            null,
            null,
            null,
            null
        );

        $this->system_event->injectDependencies($this->um, $this->backend, $this->project_manager, $this->ldap_project_manager);

        $user1 = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getAllProjects')->andReturns(array(201, 202));
        $user1->shouldReceive('isActive')->andReturns(true);
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getAllProjects')->andReturns(array(202, 203));
        $user2->shouldReceive('isActive')->andReturns(true);
        $this->um->shouldReceive('getUserById')->with('101')->andReturns($user1);
        $this->um->shouldReceive('getUserById')->with('102')->andReturns($user2);

        $this->prj1 = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(201)->getMock();
        $this->prj2 = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(202)->getMock();
        $this->prj3 = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(203)->getMock();
        $this->project_manager->shouldReceive('getProject')->with(201)->andReturns($this->prj1);
        $this->project_manager->shouldReceive('getProject')->with(202)->andReturns($this->prj2);
        $this->project_manager->shouldReceive('getProject')->with(203)->andReturns($this->prj3);
    }

    public function testUpdateShouldUpdateAllProjects(): void
    {
        $this->backend->shouldReceive('updateProjectSVNAccessFile')->times(3);
        $this->backend->shouldReceive('updateProjectSVNAccessFile')->with($this->prj1)->ordered();
        $this->backend->shouldReceive('updateProjectSVNAccessFile')->with($this->prj2)->ordered();
        $this->backend->shouldReceive('updateProjectSVNAccessFile')->with($this->prj3)->ordered();

        $this->ldap_project_manager->shouldReceive('hasSVNLDAPAuth')->andReturns(true);

        $this->system_event->process();
    }

    public function testItSkipsProjectsThatAreNotManagedByLdap(): void
    {
        $this->backend->shouldReceive('updateProjectSVNAccessFile')->never();
        $this->ldap_project_manager->shouldReceive('hasSVNLDAPAuth')->andReturns(false);
        $this->system_event->process();
    }

    public function testItSkipsProjectsBasedOnProjectId(): void
    {
        $this->ldap_project_manager->shouldReceive('hasSVNLDAPAuth')->times(3);
        $this->ldap_project_manager->shouldReceive('hasSVNLDAPAuth')->with(201)->ordered();
        $this->ldap_project_manager->shouldReceive('hasSVNLDAPAuth')->with(202)->ordered();
        $this->ldap_project_manager->shouldReceive('hasSVNLDAPAuth')->with(203)->ordered();

        $this->system_event->process();
    }
}
