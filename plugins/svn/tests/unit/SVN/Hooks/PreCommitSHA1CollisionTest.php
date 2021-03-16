<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Hooks;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Commit\CommitInfo;
use Tuleap\SVN\Commit\CommitInfoEnhancer;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\Repository;
use Tuleap\Svn\SHA1CollisionException;

class PreCommitSHA1CollisionTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tuleap\SVN\Commit\Svnlook
     */
    public $svnlook;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tuleap\Svn\SHA1CollisionDetector
     */
    public $sha1_collision_detector;
    /**
     * @var PreCommit
     */
    public $pre_commit_hook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svnlook = Mockery::spy(\Tuleap\SVN\Commit\Svnlook::class);
        $this->svnlook->shouldReceive('getMessageFromTransaction')->andReturn(["COMMIT MSG"]);
        $this->sha1_collision_detector = Mockery::spy(\Tuleap\Svn\SHA1CollisionDetector::class);

        $repository = Mockery::mock(Repository::class);

        $this->pre_commit_hook = new PreCommit(
            'path/to/repo',
            $repository,
            new CommitInfoEnhancer($this->svnlook, new CommitInfo()),
            Mockery::spy(\Tuleap\SVN\Admin\ImmutableTagFactory::class, ['getByRepositoryId' => ImmutableTag::buildEmptyImmutableTag($repository)]),
            $this->svnlook,
            $this->sha1_collision_detector,
            Mockery::spy(\Psr\Log\LoggerInterface::class),
            Mockery::spy(\Tuleap\SVN\Repository\HookConfigRetriever::class, ['getHookConfig' => new HookConfig($repository, [])]),
            \ReferenceManager::instance(),
        );
    }

    public function testItAcceptsCommitThatDoesNotContainSHA1Collision(): void
    {
        $this->svnlook->shouldReceive('getTransactionPath')->andReturn(['D   trunk/f1', 'A   trunk/f2']);
        $this->svnlook->shouldReceive('getContent')->andReturn(popen('', 'rb'));

        $this->sha1_collision_detector->shouldReceive('isColliding')->once()->andReturn(false);
        $this->pre_commit_hook->assertCommitIsValid();
    }

    public function testItRejectsCommitContainingSHA1Collision(): void
    {
        $this->svnlook->shouldReceive('getTransactionPath')->andReturn(['A   trunk/f1']);
        $this->svnlook->shouldReceive('getContent')->andReturn(popen('', 'rb'));

        $this->sha1_collision_detector->shouldReceive('isColliding')->once()->andReturn(true);

        $this->expectException(SHA1CollisionException::class);
        $this->pre_commit_hook->assertCommitIsValid();
    }
}
