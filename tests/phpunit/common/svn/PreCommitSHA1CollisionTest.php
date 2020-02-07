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

namespace Tuleap\SvnCore;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SVN_Hook_PreCommit;

class PreCommitSHA1CollisionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItAcceptsCommitThatDoesNotContainSHA1Collision(): void
    {
        $svn_hook                = \Mockery::spy(\SVN_Hooks::class)->shouldAllowMockingProtectedMethods();
        $svn_hook->shouldReceive('getProjectFromRepositoryPath')->andReturns(\Mockery::spy(\Project::class, ['getID' => false, 'getUnixName' => false, 'isPublic' => false]));
        $svnlook                 = \Mockery::spy(\SVN_Svnlook::class);
        $sha1_collision_detector = \Mockery::spy(\Tuleap\Svn\SHA1CollisionDetector::class);
        $pre_commit_hook         = new SVN_Hook_PreCommit(
            $svn_hook,
            \Mockery::spy(\SVN_CommitMessageValidator::class),
            $svnlook,
            \Mockery::spy(\SVN_Immutable_Tags_Handler::class),
            $sha1_collision_detector,
            \Mockery::spy(\Psr\Log\LoggerInterface::class)
        );

        $svnlook->shouldReceive('getTransactionPath')->andReturns(array('D   trunk/f1', 'A   trunk/f2'));
        $svnlook->shouldReceive('getContent')->andReturns(popen('', 'rb'));

        $sha1_collision_detector->shouldReceive('isColliding')->once()->andReturns(false);
        $pre_commit_hook->assertCommitDoesNotContainSHA1Collision('', '');
    }

    public function testItRejectsCommitContainingSHA1Collision(): void
    {
        $svn_hook                = \Mockery::spy(\SVN_Hooks::class)->shouldAllowMockingProtectedMethods();
        $svn_hook->shouldReceive('getProjectFromRepositoryPath')->andReturns(\Mockery::spy(\Project::class, ['getID' => false, 'getUnixName' => false, 'isPublic' => false]));
        $svnlook                 = \Mockery::spy(\SVN_Svnlook::class);
        $sha1_collision_detector = \Mockery::spy(\Tuleap\Svn\SHA1CollisionDetector::class);
        $pre_commit_hook         = new SVN_Hook_PreCommit(
            $svn_hook,
            \Mockery::spy(\SVN_CommitMessageValidator::class),
            $svnlook,
            \Mockery::spy(\SVN_Immutable_Tags_Handler::class),
            $sha1_collision_detector,
            \Mockery::spy(\Psr\Log\LoggerInterface::class)
        );

        $svnlook->shouldReceive('getTransactionPath')->andReturns(array('A   trunk/f1'));
        $svnlook->shouldReceive('getContent')->andReturns(popen('', 'rb'));
        $sha1_collision_detector->shouldReceive('isColliding')->andReturns(true);

        $this->expectException(\Tuleap\Svn\SHA1CollisionException::class);
        $pre_commit_hook->assertCommitDoesNotContainSHA1Collision('', '');
    }
}
