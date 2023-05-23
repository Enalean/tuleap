<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\RecentlyVisited;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class VisitRecorderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface&RecentlyVisitedDao
     */
    private $dao;
    /**
     * @var VisitRecorder
     */
    private $visit_recorder;

    public function setUp(): void
    {
        $this->dao            = \Mockery::mock(RecentlyVisitedDao::class);
        $this->visit_recorder = new VisitRecorder($this->dao);
    }

    public function testVisitOfAnAuthenticatedUserIsSaved(): void
    {
        $this->dao->shouldReceive('save')->once();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturn(false);
        $user->shouldReceive('getId')->andReturn(102);
        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(1003);

        $this->visit_recorder->record($user, $artifact);
    }

    public function testVisitOfAnAnonymousUserIsNotSaved(): void
    {
        $this->dao->shouldNotReceive('save');

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturn(true);

        $this->visit_recorder->record($user, \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class));
    }
}
