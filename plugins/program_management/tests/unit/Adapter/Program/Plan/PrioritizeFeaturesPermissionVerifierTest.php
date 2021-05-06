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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project_AccessException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class PrioritizeFeaturesPermissionVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CanPrioritizeFeaturesDAO
     */
    private $dao;
    /**
     * @var PrioritizeFeaturesPermissionVerifier
     */
    private $verifier;

    protected function setUp(): void
    {
        $project_manager = \Mockery::mock(\ProjectManager::class);
        $project_manager->shouldReceive('getProject')->andReturn(ProjectTestBuilder::aProject()->build());

        $this->project_access_checker = \Mockery::mock(ProjectAccessChecker::class);
        $this->dao                    = \Mockery::mock(CanPrioritizeFeaturesDAO::class);

        $this->verifier = new PrioritizeFeaturesPermissionVerifier(
            $project_manager,
            $this->project_access_checker,
            $this->dao
        );
    }

    public function testUsersCanPrioritizeFeaturesWhenTheyAreInTheAppropriateUserGroup(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->dao->shouldReceive('searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID')->andReturn([4]);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->andReturn(false);
        $user->shouldReceive('isMemberOfUGroup')->andReturn(true);

        self::assertTrue($this->verifier->canUserPrioritizeFeatures(ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 102, $user), $user));
    }

    public function testUsersCanPrioritizeFeaturesWhenTheyAreProjectAdmin(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->andReturn(true);

        self::assertTrue($this->verifier->canUserPrioritizeFeatures(ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 102, $user), $user));
    }

    public function testUsersCannotPrioritizeFeaturesWhenTheyCanAccessTheProjectButAreNotPartOfTheAuthorizedUserGroups(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->dao->shouldReceive('searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID')->andReturn([4]);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->andReturn(false);
        $user->shouldReceive('isMemberOfUGroup')->andReturn(false);

        self::assertFalse($this->verifier->canUserPrioritizeFeatures(ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 102, $user), $user));
    }

    public function testUsersCannotPrioritizeFeaturesWhenTheyCannotAccessTheProject(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andThrow(\Mockery::mock(Project_AccessException::class));

        self::assertFalse($this->verifier->canUserPrioritizeFeatures(ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 102, UserTestBuilder::aUser()->build()), UserTestBuilder::aUser()->build()));
    }
}
