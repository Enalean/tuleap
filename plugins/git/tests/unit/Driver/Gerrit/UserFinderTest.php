<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Driver\Gerrit;

use Git;
use Git_Driver_Gerrit_UserFinder;
use GitRepository;
use PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserFinderTest extends TestCase
{
    private Git_Driver_Gerrit_UserFinder $user_finder;
    private PermissionsManager&MockObject $permissions_manager;
    private GitRepository $repository;

    protected function setUp(): void
    {
        $this->permissions_manager = $this->createMock(PermissionsManager::class);
        $this->user_finder         = new Git_Driver_Gerrit_UserFinder($this->permissions_manager);
        $this->repository          = GitRepositoryTestBuilder::aProjectRepository()
            ->withId(5)
            ->inProject(ProjectTestBuilder::aProject()->withId(666)->build())
            ->build();
    }

    public function testItReturnsFalseForSpecialAdminPerms(): void
    {
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::SPECIAL_PERM_ADMIN, $this->repository);
        self::assertFalse($allowed);
    }

    public function testItReturnsFalseIfRegisteredUsersGroupIsNotContainedInTheAllowedOnes(): void
    {
        $this->permissions_manager->method('getAuthorizedUgroups')->willReturn([
            ['ugroup_id' => ProjectUGroup::PROJECT_MEMBERS],
            ['ugroup_id' => ProjectUGroup::PROJECT_ADMIN],
        ]);
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository);
        self::assertFalse($allowed);
    }

    public function testItReturnsTrueIfRegisteredUsersGroupIsContainedInTheAllowedOnes(): void
    {
        $this->permissions_manager->method('getAuthorizedUgroups')->willReturn([
            ['ugroup_id' => ProjectUGroup::PROJECT_MEMBERS],
            ['ugroup_id' => ProjectUGroup::REGISTERED],
        ]);
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository);
        self::assertTrue($allowed);
    }

    public function testItReturnsTrueIfAllUsersAreContainedInTheAllowedOnes(): void
    {
        $this->permissions_manager->method('getAuthorizedUgroups')->willReturn([
            ['ugroup_id' => ProjectUGroup::ANONYMOUS],
        ]);
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository);
        self::assertTrue($allowed);
    }
}
