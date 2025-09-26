<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\CommitStatus;

use Git_Exec;
use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommitStatusCreatorTest extends TestCase
{
    private readonly MockObject&CommitStatusDAO $dao;
    private readonly CommitStatusCreator $commit_status_creator;
    private readonly GitRepository $repository;
    private readonly Git_Exec&MockObject $git_exec;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao = $this->createMock(CommitStatusDAO::class);

        $this->commit_status_creator = new CommitStatusCreator($this->dao);

        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->build();
        $this->git_exec   = $this->createMock(Git_Exec::class);
    }

    public function testCommitStatusIsCreated(): void
    {
        $this->git_exec->method('doesObjectExists')->willReturn(true);
        $this->git_exec->method('getObjectType')->willReturn('commit');

        $this->dao->expects($this->once())->method('create');

        $this->commit_status_creator->createCommitStatus(
            $this->repository,
            $this->git_exec,
            '38762cf7f55934b34d179ae6a4c80cadccbb7f0a',
            'success'
        );
    }

    public function testExistenceOfTheCommitReferenceIsVerified(): void
    {
        $this->git_exec->method('doesObjectExists')->willReturn(false);

        $this->expectException(CommitDoesNotExistException::class);

        $this->commit_status_creator->createCommitStatus(
            $this->repository,
            $this->git_exec,
            '38762cf7f55934b34d179ae6a4c80cadccbb7f0a',
            'success'
        );
    }

    public function testReferenceIsACommitIsVerified(): void
    {
        $this->git_exec->method('doesObjectExists')->willReturn(true);
        $this->git_exec->method('getObjectType')->willReturn('tag');

        $this->expectException(InvalidCommitReferenceException::class);

        $this->commit_status_creator->createCommitStatus(
            $this->repository,
            $this->git_exec,
            '10.2',
            'success'
        );
    }
}
