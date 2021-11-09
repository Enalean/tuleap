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

use Tuleap\ProgramManagement\Adapter\Permissions\WorkflowUserPermissionBypass;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveFullProject;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectUgroupsCanPrioritizeItemsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

final class PrioritizeFeaturesPermissionVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramIdentifier $program_identifier;
    private UserIdentifier $user_identifier;
    private CheckProjectAccessStub $access_checker;
    private RetrieveUserStub $user_retriever;
    private RetrieveFullProject $retrieve_full_project;

    protected function setUp(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('getId')->willReturn('101');
        $this->user_retriever        = RetrieveUserStub::buildMockedMemberOfUGroupUser($user);
        $this->access_checker        = CheckProjectAccessStub::withValidAccess();
        $this->user_identifier       = UserIdentifierStub::buildGenericUser();
        $this->program_identifier    = ProgramIdentifierBuilder::buildWithId(102);
        $this->retrieve_full_project = RetrieveFullProjectStub::withProject(ProjectTestBuilder::aProject()->build());
    }

    private function getVerifier(): PrioritizeFeaturesPermissionVerifier
    {
        return new PrioritizeFeaturesPermissionVerifier(
            $this->retrieve_full_project,
            $this->access_checker,
            RetrieveProjectUgroupsCanPrioritizeItemsStub::buildWithIds(4),
            $this->user_retriever
        );
    }

    public function testReturnsTrueWhenUserIsInTheAppropriateUserGroup(): void
    {
        self::assertTrue(
            $this->getVerifier()->canUserPrioritizeFeatures($this->program_identifier, $this->user_identifier, null)
        );
    }

    public function testReturnsTrueWhenUserIsProjectAdmin(): void
    {
        $user                 = $this->createMock(\PFUser::class);
        $this->user_retriever = RetrieveUserStub::buildMockedAdminUser($user);
        self::assertTrue(
            $this->getVerifier()->canUserPrioritizeFeatures($this->program_identifier, $this->user_identifier, null)
        );
    }

    public function testReturnsFalseWhenUserCanAccessTheProjectButIsNotPartOfTheAuthorizedUserGroups(): void
    {
        $user                 = $this->createMock(\PFUser::class);
        $this->user_retriever = RetrieveUserStub::buildMockedRegularUser($user);
        self::assertFalse(
            $this->getVerifier()->canUserPrioritizeFeatures($this->program_identifier, $this->user_identifier, null)
        );
    }

    public function testReturnsFalseWhenUserCannotAccessTheProject(): void
    {
        $this->access_checker = CheckProjectAccessStub::withNotValidProject();
        self::assertFalse(
            $this->getVerifier()->canUserPrioritizeFeatures($this->program_identifier, $this->user_identifier, null)
        );
    }

    public function testReturnsTrueWhenBypassIsGiven(): void
    {
        self::assertTrue(
            $this->getVerifier()->canUserPrioritizeFeatures(
                $this->program_identifier,
                $this->user_identifier,
                new WorkflowUserPermissionBypass()
            )
        );
    }
}
