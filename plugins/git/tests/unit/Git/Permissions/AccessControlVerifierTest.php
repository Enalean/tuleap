<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\Permissions;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AccessControlVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    public function setUp(): void
    {
        parent::setUp();
        $this->fine_grained_permissions = \Mockery::mock(FineGrainedRetriever::class);
        $this->system_command           = \Mockery::mock(\System_Command::class);
        $this->user                     = \Mockery::mock(\PFUser::class);
        $this->repository               = \Mockery::mock(\GitRepository::class);
    }

    public function testAUserCanWriteWhenFineGrainedPermissionsAreNotEnabled()
    {
        $this->fine_grained_permissions->shouldReceive('doesRepositoryUseFineGrainedPermissions')->andReturns(false);

        $access_control_verifier  = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        $this->user->shouldReceive('hasPermission')->andReturns(true);

        $this->system_command->shouldReceive('exec')->never();

        $this->repository->shouldReceive('getId')->andReturns(1);
        $this->repository->shouldReceive('getProjectId')->andReturns(100);

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, 'master');
        $this->assertTrue($can_write);
    }

    public function testAUserCanWriteWhenFineGrainedPermissionsAreEnabled()
    {
        $this->fine_grained_permissions->shouldReceive('doesRepositoryUseFineGrainedPermissions')->andReturns(true);
        $this->system_command->shouldReceive('exec');

        $access_control_verifier = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        $this->user->shouldReceive('hasPermission')->never();
        $this->user->shouldReceive('getUserName')->andReturns('username');
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('projectname');
        $this->repository->shouldReceive('getProject')->andReturns($project);
        $this->repository->shouldReceive('getFullName')->andReturns('Repository Name');

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, 'master');
        $this->assertTrue($can_write);
    }

    public function testAUserCanNotWriteWhenFineGrainedPermissionsAreEnabledAndHeDoesNotHaveAccess()
    {
        $this->fine_grained_permissions->shouldReceive('doesRepositoryUseFineGrainedPermissions')->andReturns(true);
        $this->system_command->shouldReceive('exec')->andThrow(new \System_Command_CommandException('', array(), 1));

        $access_control_verifier = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        $this->user->shouldReceive('hasPermission')->never();
        $this->user->shouldReceive('getUserName')->andReturns('username');
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('projectname');
        $this->repository->shouldReceive('getProject')->andReturns($project);
        $this->repository->shouldReceive('getFullName')->andReturns('Repository Name');

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, 'master');
        $this->assertFalse($can_write);
    }

    public function testEmptyReferenceWhenFineGrainedPermissionsAreEnabledAreNotAccepted()
    {
        $this->fine_grained_permissions->shouldReceive('doesRepositoryUseFineGrainedPermissions')->andReturns(true);

        $access_control_verifier = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        $this->user->shouldReceive('hasPermission')->never();

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, '');
        $this->assertFalse($can_write);
    }
}
