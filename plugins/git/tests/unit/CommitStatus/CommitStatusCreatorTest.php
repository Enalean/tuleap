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

class CommitStatusCreatorTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testCommitStatusIsCreated()
    {
        $dao = \Mockery::mock(CommitStatusDAO::class);

        $commit_status_creator = new CommitStatusCreator($dao);

        $repository = \Mockery::mock(\GitRepository::class);
        $git_exec   = \Mockery::mock(\Git_Exec::class);

        $git_exec->shouldReceive('doesObjectExists')->andReturns(true);
        $git_exec->shouldReceive('getObjectType')->andReturns('commit');
        $repository->shouldReceive('getId');

        $dao->shouldReceive('create')->once();

        $commit_status_creator->createCommitStatus(
            $repository,
            $git_exec,
            '38762cf7f55934b34d179ae6a4c80cadccbb7f0a',
            'success'
        );
    }

    public function testExistenceOfTheCommitReferenceIsVerified()
    {
        $dao = \Mockery::mock(CommitStatusDAO::class);

        $commit_status_creator = new CommitStatusCreator($dao);

        $repository = \Mockery::mock(\GitRepository::class);
        $git_exec   = \Mockery::mock(\Git_Exec::class);

        $git_exec->shouldReceive('doesObjectExists')->andReturns(false);

        $this->expectException(CommitDoesNotExistException::class);

        $commit_status_creator->createCommitStatus(
            $repository,
            $git_exec,
            '38762cf7f55934b34d179ae6a4c80cadccbb7f0a',
            'success'
        );
    }

    public function testReferenceIsACommitIsVerified()
    {
        $dao = \Mockery::mock(CommitStatusDAO::class);

        $commit_status_creator = new CommitStatusCreator($dao);

        $repository = \Mockery::mock(\GitRepository::class);
        $git_exec   = \Mockery::mock(\Git_Exec::class);

        $git_exec->shouldReceive('doesObjectExists')->andReturns(true);
        $git_exec->shouldReceive('getObjectType')->andReturns('tag');

        $this->expectException(InvalidCommitReferenceException::class);

        $commit_status_creator->createCommitStatus(
            $repository,
            $git_exec,
            '10.2',
            'success'
        );
    }
}
