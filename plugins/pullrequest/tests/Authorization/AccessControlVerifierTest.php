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

namespace Tuleap\PullRequest\Authorization;

require_once __DIR__ . '/../bootstrap.php';

class AccessControlVerifierTest extends \TuleapTestCase
{
    /**
     * @var \Tuleap\Git\Permissions\FineGrainedRetriever
     */
    private $fine_grained_permissions;
    /**
     * @var \System_Command
     */
    private $system_command;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \GitRepository
     */
    private $repository;

    public function setUp()
    {
        parent::setUp();
        $this->fine_grained_permissions = mock('Tuleap\\Git\\Permissions\\FineGrainedRetriever');
        $this->system_command           = mock('System_Command');
        $this->user                     = mock('PFUser');
        $this->repository               = mock('GitRepository');
    }

    public function itVerifiesThatAUserCanWriteWhenFineGrainedPermissionsAreNotEnabled()
    {
        stub($this->fine_grained_permissions)->doesRepositoryUseFineGrainedPermissions()->returns(false);

        $access_control_verifier  = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        stub($this->user)->hasPermission()->returns(true);

        $this->system_command->expectNever('exec');

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, 'master');
        $this->assertTrue($can_write);
    }

    public function itVerifiesThatAUserCanWriteWhenFineGrainedPermissionsAreEnabled()
    {
        stub($this->fine_grained_permissions)->doesRepositoryUseFineGrainedPermissions()->returns(true);

        $access_control_verifier  = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        stub($this->user)->expectNever('hasPermission');
        $project = mock('Project');
        stub($this->repository)->getProject()->returns($project);

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, 'master');
        $this->assertTrue($can_write);
    }

    public function itVerifiesThatAUserCanNotWriteWhenFineGrainedPermissionsAreEnabledAndHeDoesNotHaveAccess()
    {
        stub($this->fine_grained_permissions)->doesRepositoryUseFineGrainedPermissions()->returns(true);
        stub($this->system_command)->exec()->throws(new \System_Command_CommandException('', array(), 1));

        $access_control_verifier  = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        stub($this->user)->expectNever('hasPermission');
        $project = mock('Project');
        stub($this->repository)->getProject()->returns($project);

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, 'master');
        $this->assertFalse($can_write);
    }
}
