<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Git\Tests\Stub\DefaultBranch\DefaultBranchUpdateExecutorStub;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitBackendGitoliteUserAccessRightsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Git_Backend_Gitolite
     */
    private $backend;
    /**
     * @var PFUser&\Mockery\LegacyMockInterface
     */
    private $user;
    /**
     * @var GitRepository&\Mockery\MockInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $driver        = \Mockery::spy(\Git_GitoliteDriver::class);
        $this->backend = new Git_Backend_Gitolite($driver, \Mockery::spy(GitoliteAccessURLGenerator::class), new DefaultBranchUpdateExecutorStub(), \Mockery::spy(\Psr\Log\LoggerInterface::class));

        $this->user       = \Mockery::spy(\PFUser::class);
        $this->repository = \Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturns(1);
        $this->repository->shouldReceive('getProjectId')->andReturns(101);
    }

    public function testItReturnsTrueIfUserIsProjectAdmin(): void
    {
        $this->user->shouldReceive('isMember')->with(101, 'A')->andReturns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsTrueIfUserHasReadAccess(): void
    {
        $this->user->shouldReceive('hasPermission')->with(Git::PERM_READ, 1, 101)->andReturns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsTrueIfUserHasReadAccessAndRepositoryIsMigratedToGerrit(): void
    {
        $this->user->shouldReceive('hasPermission')->with(Git::PERM_READ, 1, 101)->andReturns(true);
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsTrueIfUserHasWriteAccess(): void
    {
        $this->user->shouldReceive('hasPermission')->with(Git::PERM_WRITE, 1, 101)->andReturns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsFalseIfUserHasWriteAccessAndRepositoryIsMigratedToGerrit(): void
    {
        $this->user->shouldReceive('hasPermission')->with(Git::PERM_WRITE, 1, 101)->andReturns(true);
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(true);

        $this->assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsTrueIfUserHasRewindAccess(): void
    {
        $this->user->shouldReceive('hasPermission')->with(Git::PERM_WPLUS, 1, 101)->andReturns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsFalseIfUserHasRewindAccessAndRepositoryIsMigratedToGerrit(): void
    {
        $this->user->shouldReceive('hasPermission')->with(Git::PERM_WPLUS, 1, 101)->andReturns(true);
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(true);

        $this->assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsFalseIfUserHasNoPermissions(): void
    {
        $this->assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }
}
