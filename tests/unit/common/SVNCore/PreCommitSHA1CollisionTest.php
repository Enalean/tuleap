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

namespace Tuleap\SVNCore;

use SVN_Hook_PreCommit;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class PreCommitSHA1CollisionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItAcceptsCommitThatDoesNotContainSHA1Collision(): void
    {
        $svn_hook = $this->getMockBuilder(\SVN_Hooks::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProjectFromRepositoryPath'])
            ->getMock();
        $svn_hook->method('getProjectFromRepositoryPath')->willReturn(ProjectTestBuilder::aProject()->build());
        $svnlook                 = $this->createMock(\SVN_Svnlook::class);
        $sha1_collision_detector = $this->createMock(\Tuleap\SVNCore\SHA1CollisionDetector::class);
        $pre_commit_hook         = new SVN_Hook_PreCommit(
            $svn_hook,
            $this->createMock(\SVN_CommitMessageValidator::class),
            $svnlook,
            $this->createMock(\SVN_Immutable_Tags_Handler::class),
            $sha1_collision_detector,
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );

        $svnlook->method('getTransactionPath')->willReturn(['D   trunk/f1', 'A   trunk/f2']);
        $svnlook->method('getContent')->willReturn(popen('', 'rb'));

        $sha1_collision_detector->expects(self::once())->method('isColliding')->willReturn(false);
        $pre_commit_hook->assertCommitDoesNotContainSHA1Collision('', '');
    }

    public function testItRejectsCommitContainingSHA1Collision(): void
    {
        $svn_hook = $this->getMockBuilder(\SVN_Hooks::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProjectFromRepositoryPath'])
            ->getMock();
        $svn_hook->method('getProjectFromRepositoryPath')->willReturn(ProjectTestBuilder::aProject()->build());
        $svnlook                 = $this->createMock(\SVN_Svnlook::class);
        $sha1_collision_detector = $this->createMock(\Tuleap\SVNCore\SHA1CollisionDetector::class);
        $pre_commit_hook         = new SVN_Hook_PreCommit(
            $svn_hook,
            $this->createMock(\SVN_CommitMessageValidator::class),
            $svnlook,
            $this->createMock(\SVN_Immutable_Tags_Handler::class),
            $sha1_collision_detector,
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );

        $svnlook->method('getTransactionPath')->willReturn(['A   trunk/f1']);
        $svnlook->method('getContent')->willReturn(popen('', 'rb'));
        $sha1_collision_detector->method('isColliding')->willReturn(true);

        $this->expectException(\Tuleap\SVNCore\SHA1CollisionException::class);
        $pre_commit_hook->assertCommitDoesNotContainSHA1Collision('', '');
    }
}
