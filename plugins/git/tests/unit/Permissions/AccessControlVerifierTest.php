<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Git\Permissions;

use GitRepository;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use System_Command;
use System_Command_CommandException;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AccessControlVerifierTest extends TestCase
{
    private FineGrainedRetriever&MockObject $fine_grained_permissions;
    private System_Command&MockObject $system_command;
    private PFUser&MockObject $user;
    private GitRepository $repository;

    public function setUp(): void
    {
        $this->fine_grained_permissions = $this->createMock(FineGrainedRetriever::class);
        $this->system_command           = $this->createMock(System_Command::class);
        $this->user                     = $this->createMock(PFUser::class);
        $this->repository               = GitRepositoryTestBuilder::aProjectRepository()
            ->withId(1)->inProject(ProjectTestBuilder::aProject()->withUnixName('projectname')->build())
            ->withName('Repository Name')->build();
    }

    public function testAUserCanWriteWhenFineGrainedPermissionsAreNotEnabled(): void
    {
        $this->fine_grained_permissions->method('doesRepositoryUseFineGrainedPermissions')->willReturn(false);

        $access_control_verifier = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        $this->user->method('hasPermission')->willReturn(true);

        $this->system_command->expects($this->never())->method('exec');

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, 'master');
        self::assertTrue($can_write);
    }

    public function testAUserCanWriteWhenFineGrainedPermissionsAreEnabled(): void
    {
        $this->fine_grained_permissions->method('doesRepositoryUseFineGrainedPermissions')->willReturn(true);
        $this->system_command->method('exec');

        $access_control_verifier = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        $this->user->expects($this->never())->method('hasPermission');
        $this->user->method('getUserName')->willReturn('username');

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, 'master');
        self::assertTrue($can_write);
    }

    public function testAUserCanNotWriteWhenFineGrainedPermissionsAreEnabledAndHeDoesNotHaveAccess(): void
    {
        $this->fine_grained_permissions->method('doesRepositoryUseFineGrainedPermissions')->willReturn(true);
        $this->system_command->method('exec')->willThrowException(new System_Command_CommandException('', [], 1));

        $access_control_verifier = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        $this->user->expects($this->never())->method('hasPermission');
        $this->user->method('getUserName')->willReturn('username');

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, 'master');
        self::assertFalse($can_write);
    }

    public function testEmptyReferenceWhenFineGrainedPermissionsAreEnabledAreNotAccepted(): void
    {
        $this->fine_grained_permissions->method('doesRepositoryUseFineGrainedPermissions')->willReturn(true);

        $access_control_verifier = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        $this->user->expects($this->never())->method('hasPermission');

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, '');
        self::assertFalse($can_write);
    }

    public function testCheckingIfAUserCanWriteToARepositoryMigratedToGerritFallbackToAVerificationHandledByGitolite(): void
    {
        $this->fine_grained_permissions->method('doesRepositoryUseFineGrainedPermissions')->willReturn(false);
        $this->system_command->method('exec')->willThrowException(new System_Command_CommandException('', [], 1));

        $access_control_verifier = new AccessControlVerifier($this->fine_grained_permissions, $this->system_command);

        $this->user->expects($this->never())->method('hasPermission');
        $this->user->method('getUserName')->willReturn('not_replication_user');
        $this->repository->setRemoteServerId(1);

        $can_write = $access_control_verifier->canWrite($this->user, $this->repository, 'master');
        self::assertFalse($can_write);
    }
}
