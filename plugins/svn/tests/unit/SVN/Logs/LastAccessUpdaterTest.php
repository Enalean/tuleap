<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Tuleap\SVN\Commit\CommitInfo;
use Tuleap\SVN\Repository\Repository;

final class LastAccessUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItUpdatesTheLastCommitDate(): void
    {
        $dao                 = $this->createMock(LastAccessDao::class);
        $last_access_updater = new LastAccessUpdater($dao);

        $repository = $this->createMock(Repository::class);
        $repository->method('getId')->willReturn(10);
        $commit_info = $this->createMock(CommitInfo::class);
        $commit_info->method('getDate')->willReturn('2017-06-06 11:59:45 +0000 (Tue, 06 Jun 2017)');

        $dao->expects(self::once())->method('updateLastCommitDate')->with(10, 1496750385);

        $last_access_updater->updateLastCommitDate($repository, $commit_info);
    }

    public function testItThrowsAnExceptionWhenTheCommitDateIsNotReadable(): void
    {
        $dao                 = $this->createMock(LastAccessDao::class);
        $last_access_updater = new LastAccessUpdater($dao);

        $repository = $this->createMock(Repository::class);
        $repository->method('getId')->willReturn(10);
        $commit_info = $this->createMock(CommitInfo::class);
        $commit_info->method('getDate')->willReturn('This is not a valid commit date');

        $this->expectException(CannotGetCommitDateException::class);
        $dao->expects(self::never())->method('updateLastCommitDate');

        $last_access_updater->updateLastCommitDate($repository, $commit_info);
    }
}
