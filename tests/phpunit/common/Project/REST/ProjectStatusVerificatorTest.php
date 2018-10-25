<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Project\REST;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\REST\ProjectStatusVerificator;

class ProjectStatusVerificatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProjectStatusVerificator
     */
    private $verificator;

    protected function setUp()
    {
        $this->verificator = ProjectStatusVerificator::build();
    }

    public function testEverybodyCanAccessANotSuspendedProject()
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('isSuspended')->andReturn(false);

        $this->verificator->checkProjectStatusAllowsAllUsersToAccessIt($project);
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 403
     * @expectedExceptionMessage This project is suspended
     */
    public function testNobodyCanAccessASuspendedProject()
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('isSuspended')->andReturn(true);

        $this->verificator->checkProjectStatusAllowsAllUsersToAccessIt($project);
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 403
     * @expectedExceptionMessage This project is suspended
     */
    public function testRegularUsersCantAccessASuspendedProject()
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('isSuspended')->andReturn(true);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(false);

        $this->verificator->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $project
        );
    }

    public function testSiteAdminUsersCanAccessASuspendedProject()
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('isSuspended')->andReturn(true);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(true);

        $this->verificator->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $project
        );
    }
}
