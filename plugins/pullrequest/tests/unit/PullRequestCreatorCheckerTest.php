<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use GitRepository;
use Tuleap\PullRequest\Exception\PullRequestAlreadyExistsException;
use Tuleap\PullRequest\Exception\PullRequestAnonymousUserException;
use Tuleap\PullRequest\Exception\PullRequestCannotBeCreatedException;
use Tuleap\PullRequest\Exception\PullRequestRepositoryMigratedOnGerritException;
use Tuleap\PullRequest\Exception\PullRequestTargetException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PullRequestCreatorCheckerTest extends TestCase
{
    private PullRequestCreatorChecker $creator_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Dao
     */
    private $dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao             = $this->createMock(Dao::class);
        $this->creator_checker = new PullRequestCreatorChecker(
            $this->dao
        );
    }

    public function testItDoesNotThrowAnExceptionIfPullRequestCanBeCreated(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $this->dao->expects($this->once())
            ->method('isPullRequestWithSameBranchesAndSourceReferenceAlreadyExisting')
            ->willReturn(false);

        $this->creator_checker->checkIfPullRequestCanBeCreated(
            $user,
            $this->buildRepository(),
            'dev',
            'abc123',
            $this->buildRepository(),
            'main',
        );
    }

    public function testItDoesNotThrowAnExceptionIfPullRequestCanBeCreatedWithForkedRepository(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $this->dao->expects($this->once())
            ->method('isPullRequestWithSameBranchesAndSourceReferenceAlreadyExisting')
            ->willReturn(false);

        $this->creator_checker->checkIfPullRequestCanBeCreated(
            $user,
            $this->buildForkedRepository(),
            'dev',
            'abc123',
            $this->buildRepository(),
            'main',
        );
    }

    public function testItThrowsAnExceptionIfPullRequestCreatorIsAnonymous(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();

        $this->expectException(PullRequestAnonymousUserException::class);

        $this->creator_checker->checkIfPullRequestCanBeCreated(
            $user,
            $this->buildRepository(),
            'dev',
            'abc123',
            $this->buildRepository(),
            'main',
        );
    }

    public function testItThrowsAnExceptionIfRepositoriesAreNotTheSameOrAFork(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $this->expectException(PullRequestTargetException::class);

        $this->creator_checker->checkIfPullRequestCanBeCreated(
            $user,
            $this->buildAnotherRepositoryNotAFork(),
            'dev',
            'abc123',
            $this->buildRepository(),
            'main',
        );
    }

    public function testItThrowsAnExceptionIfDestinationRepositoryIsMigratedOnGerrit(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $this->expectException(PullRequestRepositoryMigratedOnGerritException::class);

        $this->creator_checker->checkIfPullRequestCanBeCreated(
            $user,
            $this->buildRepository(),
            'dev',
            'abc123',
            $this->buildRepositoryMigratedOnGerrit(),
            'main',
        );
    }

    public function testItThrowsAnExceptionIfPullRequestWillBeCreatedOnTheSameBranchInTheSameRepository(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $this->expectException(PullRequestCannotBeCreatedException::class);

        $this->creator_checker->checkIfPullRequestCanBeCreated(
            $user,
            $this->buildRepository(),
            'dev',
            'abc123',
            $this->buildRepository(),
            'dev',
        );
    }

    public function testItThrowsAnExceptionIfTheSamePullRequestWithSameBranchesAndSourceReferenceAlreadyExists(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $this->dao->expects($this->once())
            ->method('isPullRequestWithSameBranchesAndSourceReferenceAlreadyExisting')
            ->willReturn(true);

        $this->expectException(PullRequestAlreadyExistsException::class);

        $this->creator_checker->checkIfPullRequestCanBeCreated(
            $user,
            $this->buildRepository(),
            'dev',
            'abc123',
            $this->buildRepository(),
            'main',
        );
    }

    private function buildRepository(): GitRepository
    {
        $git_repository = new GitRepository();
        $git_repository->setId(1);

        return $git_repository;
    }

    private function buildForkedRepository(): GitRepository
    {
        $git_repository = new GitRepository();
        $git_repository->setId(2);
        $git_repository->setParentId(1);

        return $git_repository;
    }

    private function buildRepositoryMigratedOnGerrit(): GitRepository
    {
        $git_repository = new GitRepository();
        $git_repository->setId(1);
        $git_repository->setRemoteServerId(1);

        return $git_repository;
    }

    private function buildAnotherRepositoryNotAFork(): GitRepository
    {
        $git_repository = new GitRepository();
        $git_repository->setId(2);

        return $git_repository;
    }
}
