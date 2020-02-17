<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Tracker\Creation;

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TrackerManager;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class TrackerCreationPermissionCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TrackerManager | m\MockInterface
     */
    private $tracker_manager;

    protected function setUp(): void
    {
        $this->tracker_manager = m::mock(\TrackerManager::class);
    }

    public function testItThrowsANotFoundExceptionWhenTrackerServiceIsNotActivatedInGivenProject(): void
    {
        $project = m::mock(\Project::class);
        $project->shouldReceive('usesService')
            ->with('plugin_tracker')
            ->andReturn(false)
            ->once();

        $this->expectException(NotFoundException::class);

        $checker = new TrackerCreationPermissionChecker($this->tracker_manager);
        $checker->checkANewTrackerCanBeCreated($project, m::mock(\PFUser::class));
    }

    public function testItThrowsAForbiddenExceptionWhenUserCantCreateTrackers(): void
    {
        $project = m::mock(\Project::class);
        $project->shouldReceive('usesService')
            ->with('plugin_tracker')
            ->andReturn(true)
            ->once();

        $project->shouldReceive('getID')->andReturn(104)->once();

        $user = m::mock(\PFUser::class);

        $this->tracker_manager
            ->shouldReceive('userCanCreateTracker')
            ->with(104, $user)
            ->andReturn(false)
            ->once();

        $this->expectException(ForbiddenException::class);

        $checker = new TrackerCreationPermissionChecker($this->tracker_manager);
        $checker->checkANewTrackerCanBeCreated($project, $user);
    }
}
