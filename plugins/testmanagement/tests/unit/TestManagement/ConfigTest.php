<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\TestManagement;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TrackerFactory;

final class ConfigTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Config
     */
    private $config;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Dao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao             = \Mockery::mock(Dao::class);
        $this->tracker_factory = \Mockery::mock(TrackerFactory::class);
        $this->config          = new Config($this->dao, $this->tracker_factory);
    }

    public function testItReturnsFalseIfTrackerIdIsNotFoundInProperty(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $this->dao->shouldReceive('searchByProjectId')->withArgs([101])->andReturn(\TestHelper::arrayToDar([]));
        $this->assertFalse($this->config->getCampaignTrackerId($project));
    }

    public function testItReturnsFalseIfTrackerIsDeleted(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $properties = [
            'project_id' => 101,
            'campaign_tracker_id' => 10,
            'test_definition_tracker_id' => 11,
            'test_execution_tracker_id' => 12,
            'issue_tracker_id' => 13
        ];
        $dar = \TestHelper::arrayToDar($properties);
        $this->dao->shouldReceive('searchByProjectId')->withArgs([101])->andReturn($dar);

        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('isActive')->once()->andReturnFalse();
        $this->tracker_factory->shouldReceive('getTrackerById')->withArgs([10])->andReturn($tracker);

        $this->assertFalse($this->config->getCampaignTrackerId($project));
    }

    public function testItReturnsTheTrackerId(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $properties = [
            'project_id' => 101,
            'campaign_tracker_id' => 10,
            'test_definition_tracker_id' => 11,
            'test_execution_tracker_id' => 12,
            'issue_tracker_id' => 13
        ];
        $dar = \TestHelper::arrayToDar($properties);
        $this->dao->shouldReceive('searchByProjectId')->withArgs([101])->andReturn($dar);

        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('isActive')->once()->andReturnTrue();
        $this->tracker_factory->shouldReceive('getTrackerById')->withArgs([10])->andReturn($tracker);

        $this->assertEquals(10, $this->config->getCampaignTrackerId($project));
    }
}
