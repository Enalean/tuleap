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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class BuildStatusChangePermissionManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var BuildStatusChangePermissionManager
     */
    private $manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BuildStatusChangePermissionDAO
     */
    private $dao;
    /**
     * @var \GitRepository|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $repository;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->repository = \Mockery::mock(\GitRepository::class);
        $this->user       = \Mockery::mock(\PFUser::class);
        $this->project    = \Mockery::mock(\Project::class);
        $this->dao        = \Mockery::mock(BuildStatusChangePermissionDAO::class);

        $this->manager = new BuildStatusChangePermissionManager(
            $this->dao
        );

        $this->repository->shouldReceive('getId')->andReturn(5);
        $this->repository->shouldReceive('getProject')->andReturn($this->project);
        $this->user->shouldReceive('getId')->andReturn(155);
        $this->project->shouldReceive('getId')->andReturn(140);
    }

    public function testGivenARepositoryItReturnsTheAssociatedPermissions(): void
    {
        $this->dao->shouldReceive('searchBuildStatusChangePermissionsForRepository')
            ->with(5)
            ->andReturn('101,18,25');

        $permissions = $this->manager->getBuildStatusChangePermissions($this->repository);

        $this->assertSame(['101', '18', '25'], $permissions);
    }

    public function testItReturnsAnEmptyArrayWhenThereIsNoPermissionYetForTheGivenRepository(): void
    {
        $this->dao->shouldReceive('searchBuildStatusChangePermissionsForRepository')
            ->with(5)
            ->andReturn(null);

        $permissions = $this->manager->getBuildStatusChangePermissions($this->repository);

        $this->assertEmpty($permissions);
    }

    public function testItUpdatesThePermissions(): void
    {
        $this->dao->shouldReceive('updateBuildStatusChangePermissionsForRepository')
            ->with(5, '101,18,25')
            ->once();

        $this->manager->updateBuildStatusChangePermissions($this->repository, ['101', '18', '25']);
    }

    public function testItReturnsTrueWhenTheUserBelongsToAnAuthorizedGroup(): void
    {
        $this->dao->shouldReceive('searchBuildStatusChangePermissionsForRepository')
            ->with(5)
            ->andReturn('101,18,25');

        $this->user->shouldReceive('isMemberOfUGroup')->with('101', 140)->andReturn(false);
        $this->user->shouldReceive('isMemberOfUGroup')->with('18', 140)->andReturn(false);
        $this->user->shouldReceive('isMemberOfUGroup')->with('25', 140)->andReturn(true);

        $has_permission = $this->manager->canUserSetBuildStatusInRepository($this->user, $this->repository);

        $this->assertTrue($has_permission);
    }

    public function testItReturnsFalseOtherwise(): void
    {
        $this->dao->shouldReceive('searchBuildStatusChangePermissionsForRepository')
            ->with(5)
            ->andReturn('101,18,25');

        $this->user->shouldReceive('isMemberOfUGroup')->with('101', 140)->andReturn(false);
        $this->user->shouldReceive('isMemberOfUGroup')->with('18', 140)->andReturn(false);
        $this->user->shouldReceive('isMemberOfUGroup')->with('25', 140)->andReturn(false);

        $has_permission = $this->manager->canUserSetBuildStatusInRepository($this->user, $this->repository);

        $this->assertFalse($has_permission);
    }
}
