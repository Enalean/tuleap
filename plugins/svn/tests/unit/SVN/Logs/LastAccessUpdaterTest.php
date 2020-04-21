<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Logs;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\SVN\Commit\CommitInfo;
use Tuleap\SVN\Repository\Repository;

require_once __DIR__ . '/../../bootstrap.php';

class LastAccessUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItUpdatesTheLastCommitDate()
    {
        $dao                 = Mockery::mock(LastAccessDao::class);
        $last_access_updater = new LastAccessUpdater($dao);

        $repository  = Mockery::mock(Repository::class);
        $repository->shouldReceive('getId')->andReturn(10);
        $commit_info = Mockery::mock(CommitInfo::class);
        $commit_info->shouldReceive('getDate')->andReturn('2017-06-06 11:59:45 +0000 (Tue, 06 Jun 2017)');

        $dao->shouldReceive('updateLastCommitDate')->withArgs([10, 1496750385])->once();

        $last_access_updater->updateLastCommitDate($repository, $commit_info);
    }

    public function testItThrowsAnExceptionWhenTheCommitDateIsNotReadable()
    {
        $dao                 = Mockery::mock(LastAccessDao::class);
        $last_access_updater = new LastAccessUpdater($dao);

        $repository  = Mockery::mock(Repository::class);
        $repository->shouldReceive('getId')->andReturn(10);
        $commit_info = Mockery::mock(CommitInfo::class);
        $commit_info->shouldReceive('getDate')->andReturn('This is not a valid commit date');

        $this->expectException(CannotGetCommitDateException::class);
        $dao->shouldReceive('updateLastCommitDate')->never();

        $last_access_updater->updateLastCommitDate($repository, $commit_info);
    }
}
