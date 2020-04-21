<?php
/**
 * Copyright (c) Enalean, 2017-2019. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\Tracker\Report;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

class TrackerReportExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $tracker_id_1;
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var \Tracker
     */
    private $tracker_1;
    /**
     * @var TrackerReportExtractor
     */
    private $extractor;
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;

    public function setUp(): void
    {
        parent::setUp();

        $globals = array_merge([], $GLOBALS);

        $this->tracker_factory = \Mockery::spy(\TrackerFactory::class);

        $this->extractor = new TrackerReportExtractor($this->tracker_factory);

        $this->project      = \Mockery::spy(\Project::class);
        $this->tracker_id_1 = 1;
        $this->tracker_1 = \Mockery::spy(\Tracker::class);
        $this->tracker_1->shouldReceive('getId')->andReturn($this->tracker_id_1);

        $GLOBALS = $globals;
    }

    public function testItDoesNotExtractTrackerUserCanNotView()
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with($this->tracker_id_1)->andReturn($this->tracker_1);
        $this->tracker_1->shouldReceive('userCanView')->andReturn(false);
        $this->tracker_1->shouldReceive('isDeleted')->andReturn(false);
        $this->tracker_1->shouldReceive('getProject')->andReturn($this->project);
        $this->project->shouldReceive('isActive')->andReturn(true);

        $expected_result = array();
        $this->assertEquals(
            $expected_result,
            $this->extractor->extractTrackers(array($this->tracker_id_1))
        );
    }

    public function testItDoesNotExtractDeletedTrackers()
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with($this->tracker_id_1)->andReturn($this->tracker_1);
        $this->tracker_1->shouldReceive('userCanView')->andReturn(true);
        $this->tracker_1->shouldReceive('isDeleted')->andReturn(true);

        $expected_result = array();
        $this->assertEquals(
            $expected_result,
            $this->extractor->extractTrackers(array($this->tracker_id_1))
        );
    }

    public function testItDoesNotExtractTrackerOfNonActiveProjects()
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with($this->tracker_id_1)->andReturn($this->tracker_1);
        $this->tracker_1->shouldReceive('userCanView')->andReturn(true);
        $this->tracker_1->shouldReceive('isDeleted')->andReturn(false);
        $this->tracker_1->shouldReceive('getProject')->andReturn($this->project);
        $this->project->shouldReceive('isActive')->andReturn(false);

        $expected_result = array();
        $this->assertEquals(
            $expected_result,
            $this->extractor->extractTrackers(array($this->tracker_id_1))
        );
    }

    public function testItThrowAnExceptionWhenTrackerIsNotFound()
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with($this->tracker_id_1)->andReturn(null);

        $this->expectException('Tuleap\Tracker\Report\TrackerNotFoundException');
        $this->extractor->extractTrackers(array($this->tracker_id_1));
    }

    public function testItExtractsTrackers()
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with($this->tracker_id_1)->andReturn($this->tracker_1);
        $this->tracker_1->shouldReceive('userCanView')->andReturn(true);
        $this->tracker_1->shouldReceive('isDeleted')->andReturn(false);
        $this->tracker_1->shouldReceive('getProject')->andReturn($this->project);
        $this->project->shouldReceive('isActive')->andReturn(true);

        $expected_result = array($this->tracker_1);
        $this->assertEquals(
            $expected_result,
            $this->extractor->extractTrackers(array($this->tracker_id_1))
        );
    }
}
