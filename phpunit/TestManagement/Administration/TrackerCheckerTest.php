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

namespace Tuleap\TestManagement\Administration;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;

class TrackerCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Tracker */
    private $campaign_tracker;
    /** @var Tracker */
    private $definition_tracker;
    /** @var Tracker */
    private $execution_tracker;
    /** @var Tracker */
    private $issue_tracker;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var TrackerChecker
     */
    private $tracker_checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaign_tracker = Mockery::mock(Tracker::class);
        $this->campaign_tracker->shouldReceive('getId')->andReturn(1);
        $this->definition_tracker = Mockery::mock(Tracker::class);
        $this->definition_tracker->shouldReceive('getId')->andReturn(2);
        $this->execution_tracker = Mockery::mock(Tracker::class);
        $this->execution_tracker->shouldReceive('getId')->andReturn(3);
        $this->issue_tracker = Mockery::mock(Tracker::class);
        $this->issue_tracker->shouldReceive('getId')->andReturn(4);

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn(101);

        $tracker_factory = Mockery::mock(\TrackerFactory::class);
        $tracker_factory->shouldReceive('getTrackersByGroupId')->with(101)->andReturn([
            $this->campaign_tracker,
            $this->definition_tracker,
            $this->execution_tracker,
            $this->issue_tracker
        ]);

        $this->tracker_checker = new TrackerChecker($tracker_factory);
    }

    public function testItDoesNotThrowExceptionIfProvidedTrackerIdIsInProject()
    {
        $submitted_id = 1;
        $this->tracker_checker->checkTrackerIsInProject($this->project, $submitted_id);

        $this->addToAssertionCount(1);
    }

    public function testItThrowsAnExceptionIfProvidedTrackerIdIsInProject()
    {
        $submitted_id = 5;

        $this->expectException(TrackerNotInProjectException::class);

        $this->tracker_checker->checkTrackerIsInProject($this->project, $submitted_id);
    }
}
