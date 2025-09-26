<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Git\CIBuilds;

use GitRepository;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BuildStatusChangePermissionManagerTest extends TestCase
{
    private readonly BuildStatusChangePermissionManager $manager;
    private readonly MockObject&BuildStatusChangePermissionDAO $dao;
    private readonly GitRepository $repository;
    private readonly PFUser $user;
    private readonly Project $project;

    #[\Override]
    protected function setUp(): void
    {
        $this->project    = ProjectTestBuilder::aProject()->withId(140)->build();
        $this->user       = UserTestBuilder::anActiveUser()->withId(155)
            ->withUserGroupMembership($this->project, 101, false)
            ->withUserGroupMembership($this->project, 18, false)
            ->withUserGroupMembership($this->project, 25, true)
            ->build();
        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->inProject($this->project)->withId(5)->build(
        );
        $this->dao        = $this->createMock(BuildStatusChangePermissionDAO::class);

        $this->manager = new BuildStatusChangePermissionManager(
            $this->dao
        );
    }

    public function testGivenARepositoryItReturnsTheAssociatedPermissions(): void
    {
        $this->dao->method('searchBuildStatusChangePermissionsForRepository')
            ->with(5)
            ->willReturn('101,18,25');

        $permissions = $this->manager->getBuildStatusChangePermissions($this->repository);

        self::assertSame(['101', '18', '25'], $permissions);
    }

    public function testItReturnsAnEmptyArrayWhenThereIsNoPermissionYetForTheGivenRepository(): void
    {
        $this->dao->method('searchBuildStatusChangePermissionsForRepository')
            ->with(5)
            ->willReturn(null);

        $permissions = $this->manager->getBuildStatusChangePermissions($this->repository);

        self::assertEmpty($permissions);
    }

    public function testItUpdatesThePermissions(): void
    {
        $this->dao->expects($this->once())
            ->method('updateBuildStatusChangePermissionsForRepository')
            ->with(5, '101,18,25');

        $this->manager->updateBuildStatusChangePermissions($this->repository, ['101', '18', '25']);
    }

    public function testItReturnsTrueWhenTheUserBelongsToAnAuthorizedGroup(): void
    {
        $this->dao->method('searchBuildStatusChangePermissionsForRepository')
            ->with(5)
            ->willReturn('101,18,25');

        $has_permission = $this->manager->canUserSetBuildStatusInRepository($this->user, $this->repository);

        self::assertTrue($has_permission);
    }

    public function testItReturnsFalseOtherwise(): void
    {
        $this->dao->method('searchBuildStatusChangePermissionsForRepository')
            ->with(5)
            ->willReturn('101,18,25');

        $user = UserTestBuilder::anActiveUser()->withId(155)
                ->withUserGroupMembership($this->project, 101, false)
                    ->withUserGroupMembership($this->project, 18, false)
                        ->withUserGroupMembership($this->project, 25, false)
                            ->build();

        $has_permission = $this->manager->canUserSetBuildStatusInRepository($user, $this->repository);

        self::assertFalse($has_permission);
    }
}
