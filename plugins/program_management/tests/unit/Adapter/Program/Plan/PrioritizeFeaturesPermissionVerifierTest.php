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
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserIsProgramAdmin;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectUgroupsCanPrioritizeItemsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserIsProgramAdminStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PrioritizeFeaturesPermissionVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID                     = 602;
    private const PROJECT_ADMIN_USER_GROUP_ID = 4;
    private ProgramIdentifier $program_identifier;
    private UserIdentifier $user_identifier;
    private CheckProjectAccessStub $access_checker;
    private RetrieveUserStub $user_retriever;
    private RetrieveFullProject $retrieve_full_project;
    private VerifyUserIsProgramAdmin $verify_user_is_program_admin;
    private \Project $project;

    protected function setUp(): void
    {
        $this->user_retriever               = RetrieveUserStub::withUser(UserTestBuilder::buildWithId(self::USER_ID));
        $this->access_checker               = CheckProjectAccessStub::withValidAccess();
        $this->user_identifier              = UserIdentifierStub::withId(self::USER_ID);
        $this->program_identifier           = ProgramIdentifierBuilder::buildWithId(102);
        $this->project                      = ProjectTestBuilder::aProject()->withId(102)->build();
        $this->retrieve_full_project        = RetrieveFullProjectStub::withProject($this->project);
        $this->verify_user_is_program_admin = VerifyUserIsProgramAdminStub::withNotAdmin();
    }

    private function getVerifier(): PrioritizeFeaturesPermissionVerifier
    {
        return new PrioritizeFeaturesPermissionVerifier(
            $this->retrieve_full_project,
            $this->access_checker,
            RetrieveProjectUgroupsCanPrioritizeItemsStub::buildWithIds(self::PROJECT_ADMIN_USER_GROUP_ID),
            $this->user_retriever,
            $this->verify_user_is_program_admin
        );
    }

    public function testReturnsTrueWhenUserIsInTheAppropriateUserGroup(): void
    {
        $user                 = UserTestBuilder::aUser()
            ->withId(self::USER_ID)
            ->withUserGroupMembership($this->project, self::PROJECT_ADMIN_USER_GROUP_ID, true)
            ->build();
        $this->user_retriever = RetrieveUserStub::withUser($user);
        self::assertTrue(
            $this->getVerifier()->canUserPrioritizeFeatures($this->program_identifier, $this->user_identifier, null)
        );
    }

    public function testReturnsTrueWhenUserIsProjectAdmin(): void
    {
        $this->verify_user_is_program_admin = VerifyUserIsProgramAdminStub::withProgramAdminUser();
        self::assertTrue(
            $this->getVerifier()->canUserPrioritizeFeatures($this->program_identifier, $this->user_identifier, null)
        );
    }

    public function testReturnsFalseWhenUserCanAccessTheProjectButIsNotPartOfTheAuthorizedUserGroups(): void
    {
        $user                 = UserTestBuilder::aUser()
            ->withId(self::USER_ID)
            ->withUserGroupMembership($this->project, self::PROJECT_ADMIN_USER_GROUP_ID, false)
            ->build();
        $this->user_retriever = RetrieveUserStub::withUser($user);
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
