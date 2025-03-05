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

declare(strict_types=1);

namespace Tuleap\Git;

use Git;
use Git_Backend_Gitolite;
use Git_GitoliteDriver;
use GitRepository;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\DefaultBranch\DefaultBranchUpdateExecutorStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitBackendGitoliteUserAccessRightsTest extends TestCase
{
    private Git_Backend_Gitolite $backend;
    private PFUser&MockObject $user;
    private GitRepository $repository;

    protected function setUp(): void
    {
        $driver        = $this->createMock(Git_GitoliteDriver::class);
        $this->backend = new Git_Backend_Gitolite(
            $driver,
            $this->createMock(GitoliteAccessURLGenerator::class),
            new DefaultBranchUpdateExecutorStub(),
            new NullLogger(),
        );

        $this->user       = $this->createMock(PFUser::class);
        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId(1)
            ->inProject(ProjectTestBuilder::aProject()->build())->build();
    }

    public function testItReturnsTrueIfUserIsProjectAdmin(): void
    {
        $this->user->method('isMember')->with(101, 'A')->willReturn(true);

        self::assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsTrueIfUserHasReadAccess(): void
    {
        $this->user->method('isMember')->willReturn(false);
        $this->user->method('hasPermission')->with(Git::PERM_READ, 1, 101)->willReturn(true);

        self::assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsTrueIfUserHasReadAccessAndRepositoryIsMigratedToGerrit(): void
    {
        $this->user->method('isMember')->willReturn(false);
        $this->user->method('hasPermission')->with(Git::PERM_READ, 1, 101)->willReturn(true);
        $this->repository->setRemoteServerId(1);

        self::assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsTrueIfUserHasWriteAccess(): void
    {
        $this->user->method('isMember')->willReturn(false);
        $this->user->method('hasPermission')->willReturnCallback(static fn(string $permission) => match ($permission) {
            Git::PERM_READ  => false,
            Git::PERM_WRITE => true,
        });

        self::assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsFalseIfUserHasWriteAccessAndRepositoryIsMigratedToGerrit(): void
    {
        $this->user->method('isMember');
        $this->user->method('hasPermission')->willReturnCallback(static fn(string $permission) => match ($permission) {
            Git::PERM_READ  => false,
            Git::PERM_WRITE => true,
        });
        $this->repository->setRemoteServerId(1);

        self::assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsTrueIfUserHasRewindAccess(): void
    {
        $this->user->method('isMember');
        $this->user->method('hasPermission')->willReturnCallback(static fn(string $permission) => match ($permission) {
            Git::PERM_READ,
            Git::PERM_WRITE => false,
            Git::PERM_WPLUS => true,
        });

        self::assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsFalseIfUserHasRewindAccessAndRepositoryIsMigratedToGerrit(): void
    {
        $this->user->method('isMember');
        $this->user->method('hasPermission')->willReturnCallback(static fn(string $permission) => match ($permission) {
            Git::PERM_READ,
            Git::PERM_WRITE => false,
            Git::PERM_WPLUS => true,
        });
        $this->repository->setRemoteServerId(1);

        self::assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }

    public function testItReturnsFalseIfUserHasNoPermissions(): void
    {
        $this->user->method('isMember')->willReturn(false);
        $this->user->method('hasPermission')->willReturn(false);
        self::assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }
}
