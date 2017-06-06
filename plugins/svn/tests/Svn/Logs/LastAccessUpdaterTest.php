<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Svn\Logs;

require_once __DIR__ .'/../../bootstrap.php';

class LastAccessUpdaterTest extends \TuleapTestCase
{
    public function itUpdatesTheLastCommitDate()
    {
        $dao                 = mock('Tuleap\\Svn\\Logs\\LastAccessDao');
        $last_access_updater = new LastAccessUpdater($dao);

        $repository  = mock('Tuleap\\Svn\\Repository\\Repository');
        $commit_info = mock('Tuleap\\Svn\\Commit\\CommitInfo');
        stub($commit_info)->getDate()->returns('2017-06-06 11:59:45 +0000 (Tue, 06 Jun 2017)');

        expect($dao)->updateLastCommitDate('*', 1496750385)->once();

        $last_access_updater->updateLastCommitDate($repository, $commit_info);
    }

    public function itThrowsAnExceptionWhenTheCommitDateIsNotReadable()
    {
        $dao                 = mock('Tuleap\\Svn\\Logs\\LastAccessDao');
        $last_access_updater = new LastAccessUpdater($dao);

        $repository  = mock('Tuleap\\Svn\\Repository\\Repository');
        $commit_info = mock('Tuleap\\Svn\\Commit\\CommitInfo');
        stub($commit_info)->getDate()->returns('This is not a valid commit date');

        $this->expectException('Tuleap\\Svn\\Logs\\CannotGetCommitDateException');
        expect($dao)->updateLastCommitDate()->never();

        $last_access_updater->updateLastCommitDate($repository, $commit_info);
    }
}
