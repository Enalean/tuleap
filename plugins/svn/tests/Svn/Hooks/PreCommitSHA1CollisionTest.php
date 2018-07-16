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

namespace Tuleap\Svn\Hooks;

use Mockery;
use Tuleap\Svn\Repository\Repository;
use TuleapTestCase;

require_once __DIR__ .'/../../bootstrap.php';

class PreCommitSHA1CollisionTest extends TuleapTestCase
{
    public function itAcceptsCommitThatDoesNotContainSHA1Collision()
    {
        $svnlook                 = Mockery::spy(\Tuleap\Svn\Commit\SVNLook::class);
        $sha1_collision_detector = Mockery::spy(\Tuleap\Svn\SHA1CollisionDetector::class);
        $repository_manager      = Mockery::spy(\Tuleap\Svn\Repository\RepositoryManager::class);
        $repository_manager->shouldReceive('getRepositoryFromSystemPath')->with('path/to/repo')->andReturn(Mockery::spy(Repository::class));

        $pre_commit_hook = new PreCommit(
            'path/to/repo',
            '',
            $repository_manager,
            Mockery::spy(\Tuleap\Svn\Commit\CommitInfoEnhancer::class),
            Mockery::spy(\Tuleap\Svn\Admin\ImmutableTagFactory::class),
            $svnlook,
            $sha1_collision_detector,
            Mockery::spy(\Logger::class),
            Mockery::spy(\Tuleap\Svn\Repository\HookConfigRetriever::class)
        );

        stub($svnlook)->getTransactionPath()->returns(array('D   trunk/f1', 'A   trunk/f2'));
        stub($svnlook)->getContent()->returns(popen('', 'rb'));

        $sha1_collision_detector->shouldReceive('isColliding')->once()->andReturn(false);
        $pre_commit_hook->assertCommitDoesNotContainSHA1Collision();
    }

    public function itRejectsCommitContainingSHA1Collision()
    {
        $svnlook                 = Mockery::spy(\Tuleap\Svn\Commit\SVNLook::class);
        $sha1_collision_detector = Mockery::spy(\Tuleap\Svn\SHA1CollisionDetector::class);
        $repository_manager      = Mockery::spy(\Tuleap\Svn\Repository\RepositoryManager::class);
        $repository_manager->shouldReceive('getRepositoryFromSystemPath')->with('path/to/repo')->andReturn(Mockery::spy(Repository::class));

        $pre_commit_hook  = new PreCommit(
            'path/to/repo',
            '',
            $repository_manager,
            Mockery::spy(\Tuleap\Svn\Commit\CommitInfoEnhancer::class),
            Mockery::spy(\Tuleap\Svn\Admin\ImmutableTagFactory::class),
            $svnlook,
            $sha1_collision_detector,
            Mockery::spy(\Logger::class),
            Mockery::spy(\Tuleap\Svn\Repository\HookConfigRetriever::class)
        );

        stub($svnlook)->getTransactionPath()->returns(array('A   trunk/f1'));
        stub($svnlook)->getContent()->returns(popen('', 'rb'));

        $sha1_collision_detector->shouldReceive('isColliding')->once()->andReturn(true);

        $this->expectException('Tuleap\\Svn\\SHA1CollisionException');
        $pre_commit_hook->assertCommitDoesNotContainSHA1Collision();
    }
}
