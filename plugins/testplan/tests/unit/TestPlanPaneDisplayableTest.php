<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestPlan;

use AgileDashboardPlugin;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use testmanagementPlugin;
use Tracker;
use TrackerFactory;
use Tuleap\TestManagement\Config;

final class TestPlanPaneDisplayableTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var LegacyMockInterface|MockInterface|Config
     */
    private $testmanagement_config;
    /**
     * @var TestPlanPaneDisplayable
     */
    private $testplan_pane_displayable;
    /**
     * @var LegacyMockInterface|MockInterface|TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->testmanagement_config = Mockery::mock(Config::class);
        $this->tracker_factory       = Mockery::mock(TrackerFactory::class);

        $this->testplan_pane_displayable = new TestPlanPaneDisplayable(
            $this->testmanagement_config,
            $this->tracker_factory
        );
    }

    public function testPaneCanBeDisplayed(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->andReturn(true);
        $project->shouldReceive('usesService')->with(testmanagementPlugin::SERVICE_SHORTNAME)->andReturn(true);
        $this->testmanagement_config->shouldReceive('isConfigNeeded')->andReturn(false);
        $this->testmanagement_config->shouldReceive('getTestDefinitionTrackerId')->andReturn(123);

        $tracker = Mockery::mock(Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($tracker);

        $user = Mockery::mock(PFUser::class);
        $tracker->shouldReceive('userCanView')->andReturnTrue();

        $this->assertTrue($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenUserCannotViewTheTracker(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->andReturn(true);
        $project->shouldReceive('usesService')->with(testmanagementPlugin::SERVICE_SHORTNAME)->andReturn(true);
        $this->testmanagement_config->shouldReceive('isConfigNeeded')->andReturn(false);
        $this->testmanagement_config->shouldReceive('getTestDefinitionTrackerId')->andReturn(123);

        $tracker = Mockery::mock(Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($tracker);

        $user = Mockery::mock(PFUser::class);
        $tracker->shouldReceive('userCanView')->andReturnFalse();

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenTrackerDoesNotExist(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->andReturn(true);
        $project->shouldReceive('usesService')->with(testmanagementPlugin::SERVICE_SHORTNAME)->andReturn(true);
        $this->testmanagement_config->shouldReceive('isConfigNeeded')->andReturn(false);
        $this->testmanagement_config->shouldReceive('getTestDefinitionTrackerId')->andReturn(123);

        $this->tracker_factory->shouldReceive('getTrackerById')->andReturnNull();

        $user = Mockery::mock(PFUser::class);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenConfigIsNotNeededButThereIsNoTestDefinitionTrackerIdHonestlyThisIsAWeirdSituation(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->andReturn(true);
        $project->shouldReceive('usesService')->with(testmanagementPlugin::SERVICE_SHORTNAME)->andReturn(true);
        $this->testmanagement_config->shouldReceive('isConfigNeeded')->andReturn(false);
        $this->testmanagement_config->shouldReceive('getTestDefinitionTrackerId')->andReturnNull();

        $user = Mockery::mock(PFUser::class);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenTheAgileDashboardServiceIsNotUsed(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->andReturn(false);
        $project->shouldReceive('usesService')->with(testmanagementPlugin::SERVICE_SHORTNAME)->andReturn(true);

        $user = Mockery::mock(PFUser::class);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenTheTestManagementServiceIsNotUsed(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->andReturn(true);
        $project->shouldReceive('usesService')->with(testmanagementPlugin::SERVICE_SHORTNAME)->andReturn(false);

        $user = Mockery::mock(PFUser::class);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenTheTestManagementServiceIsMisconfigured(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->andReturn(true);
        $project->shouldReceive('usesService')->with(testmanagementPlugin::SERVICE_SHORTNAME)->andReturn(true);

        $user = Mockery::mock(PFUser::class);
        $this->testmanagement_config->shouldReceive('isConfigNeeded')->andReturn(true);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }
}
