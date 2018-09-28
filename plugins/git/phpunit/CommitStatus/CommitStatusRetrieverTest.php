<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\CommitStatus;

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class CommitStatusRetrieverTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testCommitStatusIsRetrieved()
    {
        $dao = \Mockery::mock(CommitStatusDAO::class);

        $commit_status_retriever = new CommitStatusRetriever($dao);

        $repository = \Mockery::mock(\GitRepository::class);

        $dao->shouldReceive('getLastCommitStatusByRepositoryIdAndCommitReferences')->andReturns([
            [
                'commit_reference' => '38762cf7f55934b34d179ae6a4c80cadccbb7f0a',
                'status'           => CommitStatusWithKnownStatus::STATUS_SUCCESS,
                'date'             => 1528892466
            ]
        ]);
        $repository->shouldReceive('getId');

        $commit_status = $commit_status_retriever->getLastCommitStatus(
            $repository,
            '38762cf7f55934b34d179ae6a4c80cadccbb7f0a'
        );

        $this->assertInstanceOf(CommitStatus::class, $commit_status);
    }

    public function testCommitStatusUnknownIsRetrievedWhenNoStatusExist()
    {
        $dao = \Mockery::mock(CommitStatusDAO::class);

        $commit_status_retriever = new CommitStatusRetriever($dao);

        $repository = \Mockery::mock(\GitRepository::class);

        $dao->shouldReceive('getLastCommitStatusByRepositoryIdAndCommitReferences')->andReturns([]);
        $repository->shouldReceive('getId');

        $commit_status = $commit_status_retriever->getLastCommitStatus(
            $repository,
            '38762cf7f55934b34d179ae6a4c80cadccbb7f0a'
        );

        $this->assertInstanceOf(CommitStatusUnknown::class, $commit_status);
    }

    public function testASetOfCommitStatusCanBeRetrieved()
    {
        $dao = \Mockery::mock(CommitStatusDAO::class);

        $commit_status_retriever = new CommitStatusRetriever($dao);

        $repository = \Mockery::mock(\GitRepository::class);

        $dao->shouldReceive('getLastCommitStatusByRepositoryIdAndCommitReferences')->andReturns([
            [
                'commit_reference' => '38762cf7f55934b34d179ae6a4c80cadccbb7f0a',
                'status'           => CommitStatusWithKnownStatus::STATUS_SUCCESS,
                'date'             => 1528892466
            ],
            [
                'commit_reference' => '23badb142cabe3e604ceb5fd5d243354e8e9f491',
                'status'           => CommitStatusWithKnownStatus::STATUS_FAILURE,
                'date'             => 1528898888
            ]
        ]);
        $repository->shouldReceive('getId');

        $commit_statuses = $commit_status_retriever->getLastCommitStatuses(
            $repository,
            ['38762cf7f55934b34d179ae6a4c80cadccbb7f0a', '23badb142cabe3e604ceb5fd5d243354e8e9f491']
        );

        $this->assertCount(2, $commit_statuses);
        $this->assertSame(1528892466, $commit_statuses[0]->getDate()->getTimestamp());
        $this->assertSame(CommitStatusWithKnownStatus::STATUS_SUCCESS_NAME, $commit_statuses[0]->getStatusName());
        $this->assertSame(1528898888, $commit_statuses[1]->getDate()->getTimestamp());
        $this->assertSame(CommitStatusWithKnownStatus::STATUS_FAILURE_NAME, $commit_statuses[1]->getStatusName());
    }
}
