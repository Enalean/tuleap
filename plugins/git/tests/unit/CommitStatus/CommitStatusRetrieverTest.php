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

use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommitStatusRetrieverTest extends TestCase
{
    private readonly CommitStatusDAO&MockObject $dao;
    private readonly CommitStatusRetriever $commit_status_retriever;
    private readonly GitRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao = $this->createMock(CommitStatusDAO::class);

        $this->commit_status_retriever = new CommitStatusRetriever($this->dao);

        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->build();
    }

    public function testCommitStatusIsRetrieved(): void
    {
        $this->dao->method('getLastCommitStatusByRepositoryIdAndCommitReferences')->willReturn([
            [
                'commit_reference' => '38762cf7f55934b34d179ae6a4c80cadccbb7f0a',
                'status'           => CommitStatusWithKnownStatus::STATUS_SUCCESS,
                'date'             => 1528892466,
            ],
        ]);

        $commit_status = $this->commit_status_retriever->getLastCommitStatus(
            $this->repository,
            '38762cf7f55934b34d179ae6a4c80cadccbb7f0a'
        );

        self::assertInstanceOf(CommitStatus::class, $commit_status);
    }

    public function testCommitStatusUnknownIsRetrievedWhenNoStatusExist(): void
    {
        $this->dao->method('getLastCommitStatusByRepositoryIdAndCommitReferences')->willReturn([]);

        $commit_status = $this->commit_status_retriever->getLastCommitStatus(
            $this->repository,
            '38762cf7f55934b34d179ae6a4c80cadccbb7f0a'
        );

        self::assertInstanceOf(CommitStatusUnknown::class, $commit_status);
    }

    public function testASetOfCommitStatusCanBeRetrieved(): void
    {
        $this->dao->method('getLastCommitStatusByRepositoryIdAndCommitReferences')->willReturn([
            [
                'commit_reference' => '38762cf7f55934b34d179ae6a4c80cadccbb7f0a',
                'status'           => CommitStatusWithKnownStatus::STATUS_SUCCESS,
                'date'             => 1528892466,
            ],
            [
                'commit_reference' => '23badb142cabe3e604ceb5fd5d243354e8e9f491',
                'status'           => CommitStatusWithKnownStatus::STATUS_FAILURE,
                'date'             => 1528898888,
            ],
        ]);

        $commit_statuses = $this->commit_status_retriever->getLastCommitStatuses(
            $this->repository,
            ['38762cf7f55934b34d179ae6a4c80cadccbb7f0a', '23badb142cabe3e604ceb5fd5d243354e8e9f491']
        );

        self::assertCount(2, $commit_statuses);
        self::assertSame(1528892466, $commit_statuses[0]->getDate()->getTimestamp());
        self::assertSame(CommitStatusWithKnownStatus::STATUS_SUCCESS_NAME, $commit_statuses[0]->getStatusName());
        self::assertSame(1528898888, $commit_statuses[1]->getDate()->getTimestamp());
        self::assertSame(CommitStatusWithKnownStatus::STATUS_FAILURE_NAME, $commit_statuses[1]->getStatusName());
    }
}
