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

namespace Tuleap\Git\Hook\Asynchronous;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const GIT_REPOSITORY_ID = 590;
    /**
     * @var \GitRepositoryFactory & \PHPUnit\Framework\MockObject\Stub
     */
    private $repository_factory;
    /**
     * @var \GitRepository & \PHPUnit\Framework\MockObject\Stub
     */
    private $repository;
    /**
     * @var \Project & \PHPUnit\Framework\MockObject\Stub
     */
    private $project;

    #[\Override]
    protected function setUp(): void
    {
        $this->project    = $this->createStub(\Project::class);
        $this->repository = $this->createStub(\GitRepository::class);
        $this->repository->method('getProjectId')->willReturn(1);
        $this->repository->method('getProject')->willReturn($this->project);
        $this->repository_factory = $this->createStub(\GitRepositoryFactory::class);
    }

    /**
     * @return Ok<\GitRepository> | Err<Fault>
     */
    private function getRepository(): Ok|Err
    {
        $user = UserTestBuilder::buildWithDefaults();

        return (new GitRepositoryRetriever($this->repository_factory))->getRepository(self::GIT_REPOSITORY_ID, $user);
    }

    public function testItReturnsAGitRepository(): void
    {
        $this->mockRepositoryIsFound();
        $this->mockUserCanReadRepository();
        $this->project->method('isActive')->willReturn(true);
        $this->project->method('isSuspended')->willReturn(false);

        $result = $this->getRepository();
        self::assertTrue(Result::isOk($result));
        self::assertSame($this->repository, $result->value);
    }

    public function testItReturnsFaultWhenRepositoryCantBeFound(): void
    {
        $this->repository_factory->method('getRepositoryById')->willReturn(null);

        $result = $this->getRepository();
        self::assertTrue(Result::isErr($result));
    }

    public function testItReturnsFaultWhenProjectIsSuspended(): void
    {
        $this->mockRepositoryIsFound();
        $this->mockUserCanReadRepository();
        $this->project->method('isActive')->willReturn(false);
        $this->project->method('isSuspended')->willReturn(true);

        $result = $this->getRepository();
        self::assertTrue(Result::isErr($result));
    }

    public function testItReturnsFaultWhenProjectIsDeleted(): void
    {
        $this->mockRepositoryIsFound();
        $this->mockUserCanReadRepository();
        $this->project->method('isActive')->willReturn(false);
        $this->project->method('isSuspended')->willReturn(false);

        $result = $this->getRepository();
        self::assertTrue(Result::isErr($result));
    }

    private function mockRepositoryIsFound(): void
    {
        $this->repository_factory->method('getRepositoryById')->willReturn($this->repository);
    }

    private function mockUserCanReadRepository(): void
    {
        $this->repository->method('userCanRead')->willReturn(true);
    }
}
