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

namespace Tuleap\SvnCore;

use SVN_Hook_PreCommit;

class PreCommitSHA1CollisionTest extends \TuleapTestCase
{
    public function itAcceptsCommitThatDoesNotContainSHA1Collision()
    {
        $svnlook                 = mock('SVN_Svnlook');
        $sha1_collision_detector = mock('Tuleap\\Svn\\SHA1CollisionDetector');
        $pre_commit_hook         = new SVN_Hook_PreCommit(
            mock('SVN_Hooks'),
            mock('SVN_CommitMessageValidator'),
            $svnlook,
            mock('SVN_Immutable_Tags_Handler'),
            $sha1_collision_detector,
            mock('Logger')
        );

        stub($svnlook)->getTransactionPath()->returns(array('D   trunk/f1', 'A   trunk/f2'));
        stub($svnlook)->getContent()->returns(popen('', 'rb'));
        stub($sha1_collision_detector)->isColliding()->returns(false);

        $sha1_collision_detector->expectOnce('isColliding');
        $pre_commit_hook->assertCommitDoesNotContainSHA1Collision('', '');
    }

    public function itRejectsCommitContainingSHA1Collision()
    {
        $svnlook                 = mock('SVN_Svnlook');
        $sha1_collision_detector = mock('Tuleap\\Svn\\SHA1CollisionDetector');
        $pre_commit_hook         = new SVN_Hook_PreCommit(
            mock('SVN_Hooks'),
            mock('SVN_CommitMessageValidator'),
            $svnlook,
            mock('SVN_Immutable_Tags_Handler'),
            $sha1_collision_detector,
            mock('Logger')
        );

        stub($svnlook)->getTransactionPath()->returns(array('A   trunk/f1'));
        stub($svnlook)->getContent()->returns(popen('', 'rb'));
        stub($sha1_collision_detector)->isColliding()->returns(true);

        $this->expectException('Tuleap\\Svn\\SHA1CollisionException');
        $pre_commit_hook->assertCommitDoesNotContainSHA1Collision('', '');
    }
}
