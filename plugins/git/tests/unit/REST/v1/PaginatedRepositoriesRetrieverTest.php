<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Git\REST\v1;

use GitRepository;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PaginatedRepositoriesRetrieverTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject|GitDao $dao;
    private \PHPUnit\Framework\MockObject\MockObject|\GitRepositoryFactory $factory;
    private \Tuleap\Git\Permissions\AccessControlVerifier|\PHPUnit\Framework\MockObject\MockObject $access_control_verifier;
    private PaginatedRepositoriesRetriever $retriever;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                     = $this->createMock(\GitDao::class);
        $this->access_control_verifier = $this->createMock(\Tuleap\Git\Permissions\AccessControlVerifier::class);
        $this->factory                 = $this->createMock(\GitRepositoryFactory::class);

        $this->retriever = new PaginatedRepositoriesRetriever($this->dao, $this->factory, $this->access_control_verifier);
    }

    public function testGetPaginatedRepositoriesUserCanSeeReturnsNothing(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()->build();

        $this->dao
            ->method('getPaginatedOpenRepositories')
            ->willReturn([]);
        $this->dao
            ->method('foundRows')
            ->willReturn(0);

        self::assertEmpty(
            $this->retriever->getPaginatedRepositoriesUserCanSee(
                $project,
                $user,
                '',
                0,
                'push_date',
                10,
                0,
                $total_number_repositories
            )
        );
        self::assertEquals(0, $total_number_repositories);
    }

    public function testGetPaginatedRepositoriesUserCanSeeReturnRepositoriesUserCanSee(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()->build();

        $this->dao
            ->method('getPaginatedOpenRepositories')
            ->willReturn([
                ['repository_id' => 101],
                ['repository_id' => 102],
            ]);
        $this->dao
            ->method('foundRows')
            ->willReturn(2);

        $readable_repository = $this->createMock(GitRepository::class);
        $readable_repository->method('userCanRead')->willReturn(true);
        $unreadable_repository = $this->createMock(GitRepository::class);
        $unreadable_repository->method('userCanRead')->willReturn(false);
        $this->factory
            ->method('instanciateFromRow')
            ->willReturnMap([
                [['repository_id' => 101], $readable_repository],
                [['repository_id' => 102], $unreadable_repository],
            ]);

        self::assertEquals(
            [$readable_repository],
            $this->retriever->getPaginatedRepositoriesUserCanSee(
                $project,
                $user,
                '',
                0,
                'push_date',
                10,
                0,
                $total_number_repositories
            )
        );
        self::assertEquals(2, $total_number_repositories);
    }

    public function testGetPaginatedRepositoriesUserCanCreateGivenBranchReturnNothing(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()->build();

        $this->dao
            ->method('getPaginatedOpenRepositories')
            ->willReturn([]);
        $this->dao
            ->method('foundRows')
            ->willReturn(0);

        self::assertEmpty(
            $this->retriever->getPaginatedRepositoriesUserCanCreateGivenBranch(
                $project,
                $user,
                '',
                0,
                'tuleap-123-slug-branch-name',
                'push_date',
                10,
                0,
                $total_number_repositories
            )
        );
        self::assertEquals(0, $total_number_repositories);
    }

    public function testGetPaginatedRepositoriesUserCanCreateGivenBranchReturnRepositoriesWhereBranchCanBeCreated(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()->build();

        $this->dao
            ->method('getPaginatedOpenRepositories')
            ->willReturn([
                ['repository_id' => 101],
                ['repository_id' => 102],
            ]);
        $this->dao
            ->method('foundRows')
            ->willReturn(2);

        $a_repository       = $this->createMock(GitRepository::class);
        $another_repository = $this->createMock(GitRepository::class);
        $this->factory
            ->method('instanciateFromRow')
            ->willReturnMap([
                [['repository_id' => 101], $a_repository],
                [['repository_id' => 102], $another_repository],
            ]);

        $this->access_control_verifier
            ->method('canWrite')
            ->willReturnMap([
                [$user, $a_repository, 'tuleap-123-slug-branch-name', true],
                [$user, $another_repository, 'tuleap-123-slug-branch-name', false],
            ]);

        self::assertEquals(
            [$a_repository],
            $this->retriever->getPaginatedRepositoriesUserCanCreateGivenBranch(
                $project,
                $user,
                '',
                0,
                'tuleap-123-slug-branch-name',
                'push_date',
                10,
                0,
                $total_number_repositories
            )
        );
        self::assertEquals(2, $total_number_repositories);
    }
}
