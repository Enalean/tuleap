<?php
/**
 *  Copyright (c) Maximaster, 2020. All rights reserved
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\Permission\FollowUp\PrivateComments;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use function Symfony\Component\String\u;

final class PermissionsOnPrivateCommentCheckerTest extends TestCase
{
    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser */
    private $user;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker */
    private $tracker;

    /** @var \Mockery\MockInterface|Project */
    private $project;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PermissionsOnPrivateCommentChecker */
    private $premission_private_comment_checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerPrivateCommentsDao
     */
    private $private_comment_dao;

    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturns(120);
        $this->user->shouldReceive('isMemberOfUGroup')->andReturns(false);
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->user->shouldReceive('isMember')->with(12)->andReturns(true);

        $this->project = \Mockery::spy(\Project::class);
        $this->project->shouldReceive('getID')->andReturns(120);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->project->shouldReceive('isActive')->andReturns(true);

        $this->tracker = \Mockery::spy(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturns(666);
        $this->tracker->shouldReceive('getGroupId')->andReturns(222);
        $this->tracker->shouldReceive('getProject')->andReturns($this->project);

        $this->premission_private_comment_checker = \Mockery::mock(PermissionsOnPrivateCommentChecker::class);
        $this->private_comment_dao                = \Mockery::spy(TrackerPrivateCommentsDao::class);

        $this->premission_private_comment_checker->shouldReceive()->andReturn($this->private_comment_dao);
        $this->premission_private_comment_checker->shouldReceive('getInstance')
            ->andReturn(PermissionsOnPrivateCommentChecker::getInstance());
    }

    function testItUniqSingleton(): void
    {
        $this->premission_private_comment_checker->shouldReceive('getInstance')
            ->andReturn(PermissionsOnPrivateCommentChecker::getInstance());

        $fCall = $this->premission_private_comment_checker::getInstance();
        $sCall = $this->premission_private_comment_checker::getInstance();

        $this->assertInstanceOf(PermissionsOnPrivateCommentChecker::class, $fCall );
        $this->assertSame($fCall, $sCall);
    }

    function TestItgetPrivateCommentsGroups(): void
    {
        $this->private_comment_dao->shouldReceive('getAccessUgroupsByTrackerId')
            ->andReturn([
                [
                    'id' => 125,
                    'tracker_id' => 2,
                    'ugroup_id' => 1
                ],
                [
                    'id' => 134,
                    'tracker_id' => 2,
                    'ugroup_id' => 3
                ]
            ]);
    }
}
