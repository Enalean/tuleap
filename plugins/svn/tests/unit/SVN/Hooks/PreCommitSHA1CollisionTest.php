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

use Psr\Log\NullLogger;
use Tuleap\SVN\Commit\CollidingSHA1Validator;
use Tuleap\SVN\Commit\CommitMessageValidator;
use Tuleap\SVNCore\Repository;
use Tuleap\SVNCore\SHA1CollisionException;

final class PreCommitSHA1CollisionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\SVN\Commit\Svnlook
     */
    public $svnlook;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\SVNCore\SHA1CollisionDetector
     */
    public $sha1_collision_detector;
    public PreCommit $pre_commit_hook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svnlook = $this->getMockBuilder(\Tuleap\SVN\Commit\Svnlook::class)
            ->onlyMethods(['getMessageFromTransaction', 'getTransactionPath', 'getContent'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->svnlook->method('getMessageFromTransaction')->willReturn(["COMMIT MSG"]);

        $this->sha1_collision_detector = $this->createMock(\Tuleap\SVNCore\SHA1CollisionDetector::class);

        $commit_message_validator = $this->createMock(CommitMessageValidator::class);
        $commit_message_validator->method('assertCommitMessageIsValid');

        $this->pre_commit_hook = new PreCommit(
            $this->svnlook,
            new NullLogger(),
            $commit_message_validator,
            new CollidingSHA1Validator($this->svnlook, $this->sha1_collision_detector)
        );
    }

    public function testItAcceptsCommitThatDoesNotContainSHA1Collision(): void
    {
        $this->svnlook->method('getTransactionPath')->willReturn(['D   trunk/f1', 'A   trunk/f2']);
        $this->svnlook->method('getContent')->willReturn(popen('', 'rb'));

        $this->sha1_collision_detector->expects(self::once())->method('isColliding')->willReturn(false);
        $this->pre_commit_hook->assertCommitIsValid($this->createMock(Repository::class), 'r1-1');
    }

    public function testItRejectsCommitContainingSHA1Collision(): void
    {
        $this->svnlook->method('getTransactionPath')->willReturn(['A   trunk/f1']);
        $this->svnlook->method('getContent')->willReturn(popen('', 'rb'));

        $this->sha1_collision_detector->expects(self::once())->method('isColliding')->willReturn(true);

        $this->expectException(SHA1CollisionException::class);
        $this->pre_commit_hook->assertCommitIsValid($this->createMock(Repository::class), 'r1-1');
    }
}
