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
use PFUser;
use Project;
use testmanagementPlugin;
use Tracker;
use TrackerFactory;
use Tuleap\TestManagement\Config;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TestPlanPaneDisplayableTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Config
     */
    private mixed $testmanagement_config;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerFactory
     */
    private mixed $tracker_factory;
    private TestPlanPaneDisplayable $testplan_pane_displayable;

    protected function setUp(): void
    {
        $this->testmanagement_config = $this->createMock(Config::class);
        $this->tracker_factory       = $this->createMock(TrackerFactory::class);

        $this->testplan_pane_displayable = new TestPlanPaneDisplayable(
            $this->testmanagement_config,
            $this->tracker_factory
        );
    }

    public function testPaneCanBeDisplayed(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('usesService')->willReturnMap([
            [AgileDashboardPlugin::PLUGIN_SHORTNAME, true],
            [testmanagementPlugin::SERVICE_SHORTNAME, true],
        ]);
        $this->testmanagement_config->method('isConfigNeeded')->willReturn(false);
        $this->testmanagement_config->method('getTestDefinitionTrackerId')->willReturn(123);

        $tracker = $this->createMock(Tracker::class);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);

        $user = $this->createMock(PFUser::class);
        $tracker->method('userCanView')->willReturn(true);

        $this->assertTrue($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenUserCannotViewTheTracker(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('usesService')->willReturnMap([
            [AgileDashboardPlugin::PLUGIN_SHORTNAME, true],
            [testmanagementPlugin::SERVICE_SHORTNAME, true],
        ]);
        $this->testmanagement_config->method('isConfigNeeded')->willReturn(false);
        $this->testmanagement_config->method('getTestDefinitionTrackerId')->willReturn(123);

        $tracker = $this->createMock(Tracker::class);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);

        $user = $this->createMock(PFUser::class);
        $tracker->method('userCanView')->willReturn(false);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenTrackerDoesNotExist(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('usesService')->willReturnMap([
            [AgileDashboardPlugin::PLUGIN_SHORTNAME, true],
            [testmanagementPlugin::SERVICE_SHORTNAME, true],
        ]);
        $this->testmanagement_config->method('isConfigNeeded')->willReturn(false);
        $this->testmanagement_config->method('getTestDefinitionTrackerId')->willReturn(123);

        $this->tracker_factory->method('getTrackerById')->willReturn(null);

        $user = $this->createMock(PFUser::class);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenConfigIsNotNeededButThereIsNoTestDefinitionTrackerIdHonestlyThisIsAWeirdSituation(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('usesService')->willReturnMap([
            [AgileDashboardPlugin::PLUGIN_SHORTNAME, true],
            [testmanagementPlugin::SERVICE_SHORTNAME, true],
        ]);
        $this->testmanagement_config->method('isConfigNeeded')->willReturn(false);
        $this->testmanagement_config->method('getTestDefinitionTrackerId')->willReturn(null);

        $user = $this->createMock(PFUser::class);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenTheAgileDashboardServiceIsNotUsed(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('usesService')->willReturnMap([
            [AgileDashboardPlugin::PLUGIN_SHORTNAME, false],
            [testmanagementPlugin::SERVICE_SHORTNAME, true],
        ]);

        $user = $this->createMock(PFUser::class);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenTheTestManagementServiceIsNotUsed(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('usesService')->willReturnMap([
            [AgileDashboardPlugin::PLUGIN_SHORTNAME, true],
            [testmanagementPlugin::SERVICE_SHORTNAME, false],
        ]);

        $user = $this->createMock(PFUser::class);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }

    public function testPaneCanNotBeDisplayedWhenTheTestManagementServiceIsMisconfigured(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('usesService')->willReturnMap([
            [AgileDashboardPlugin::PLUGIN_SHORTNAME, true],
            [testmanagementPlugin::SERVICE_SHORTNAME, true],
        ]);

        $user = $this->createMock(PFUser::class);
        $this->testmanagement_config->method('isConfigNeeded')->willReturn(true);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user));
    }
}
