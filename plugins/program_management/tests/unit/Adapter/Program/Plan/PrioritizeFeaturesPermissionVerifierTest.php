<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RetrieveProjectUgroupsCanPrioritizeItems;
use Tuleap\ProgramManagement\Domain\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveProject;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Stub\RetrieveProjectStub;
use Tuleap\ProgramManagement\Stub\RetrieveProjectUgroupsCanPrioritizeItemsStub;
use Tuleap\ProgramManagement\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Stub\UserPermissionsStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

final class PrioritizeFeaturesPermissionVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PrioritizeFeaturesPermissionVerifier $verifier;
    private RetrieveProject $retrieve_project;
    private RetrieveProjectUgroupsCanPrioritizeItems $retrieve_ugroups;
    private ProgramIdentifier $program_identifier;
    private UserIdentifier $user_identifier;
    /**
     * @var \PFUser|\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;

    protected function setUp(): void
    {
        $this->retrieve_project = RetrieveProjectStub::withValidProjects(ProjectTestBuilder::aProject()->build());
        $this->retrieve_ugroups = RetrieveProjectUgroupsCanPrioritizeItemsStub::buildWithIds(4);
        $this->user             = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(101);
        $this->user_identifier    = UserIdentifierStub::buildGenericUser();
        $this->program_identifier = ProgramIdentifier::fromId(
            BuildProgramStub::stubValidProgram(),
            102,
            $this->user_identifier
        );
    }

    public function testUsersCanPrioritizeFeaturesWhenTheyAreInTheAppropriateUserGroup(): void
    {
        $this->verifier = new PrioritizeFeaturesPermissionVerifier(
            $this->retrieve_project,
            CheckProjectAccessStub::withValidAccess(),
            $this->retrieve_ugroups,
            RetrieveUserStub::buildMockedMemberOfUGroupUser($this->user)
        );

        self::assertTrue(
            $this->verifier->canUserPrioritizeFeatures($this->program_identifier, UserPermissionsStub::aRegularUser(), $this->user_identifier)
        );
    }

    public function testUsersCanPrioritizeFeaturesWhenTheyArePlatformAdmin(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(true);
        $this->verifier = new PrioritizeFeaturesPermissionVerifier(
            $this->retrieve_project,
            CheckProjectAccessStub::withValidAccess(),
            $this->retrieve_ugroups,
            RetrieveUserStub::buildMockedAdminUser($user)
        );

        self::assertTrue(
            $this->verifier->canUserPrioritizeFeatures(
                $this->program_identifier,
                UserPermissionsStub::aPlatformAdmin(),
                $this->user_identifier
            )
        );
    }

    public function testUsersCanPrioritizeFeaturesWhenTheyAreProjectAdmin(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(true);
        $this->verifier = new PrioritizeFeaturesPermissionVerifier(
            $this->retrieve_project,
            CheckProjectAccessStub::withValidAccess(),
            $this->retrieve_ugroups,
            RetrieveUserStub::buildMockedAdminUser($user)
        );

        self::assertTrue(
            $this->verifier->canUserPrioritizeFeatures(
                $this->program_identifier,
                UserPermissionsStub::aProjectAdmin(),
                $this->user_identifier
            )
        );
    }

    public function testUsersCannotPrioritizeFeaturesWhenTheyCanAccessTheProjectButAreNotPartOfTheAuthorizedUserGroups(): void
    {
        $this->verifier = new PrioritizeFeaturesPermissionVerifier(
            $this->retrieve_project,
            CheckProjectAccessStub::withValidAccess(),
            $this->retrieve_ugroups,
            RetrieveUserStub::buildMockedRegularUser($this->createMock(\PFUser::class))
        );

        self::assertFalse(
            $this->verifier->canUserPrioritizeFeatures($this->program_identifier, UserPermissionsStub::aRegularUser(), $this->user_identifier)
        );
    }

    public function testUsersCannotPrioritizeFeaturesWhenTheyCannotAccessTheProject(): void
    {
        $this->verifier = new PrioritizeFeaturesPermissionVerifier(
            $this->retrieve_project,
            CheckProjectAccessStub::withNotValidProject(),
            $this->retrieve_ugroups,
            RetrieveUserStub::buildMockedRegularUser($this->createMock(\PFUser::class))
        );

        self::assertFalse(
            $this->verifier->canUserPrioritizeFeatures($this->program_identifier, UserPermissionsStub::aRegularUser(), $this->user_identifier)
        );
    }
}
