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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use testmanagementPlugin;
use Tuleap\TestManagement\Config;

final class TestPlanPaneDisplayableTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Config
     */
    private $testmanagement_config;
    /**
     * @var TestPlanPaneDisplayable
     */
    private $testplan_pane_displayable;

    protected function setUp(): void
    {
        $this->testmanagement_config     = \Mockery::mock(Config::class);
        $this->testplan_pane_displayable = new TestPlanPaneDisplayable($this->testmanagement_config);
    }

    public function testPaneCanBeDisplayed(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->andReturn(true);
        $project->shouldReceive('usesService')->with(testmanagementPlugin::SERVICE_SHORTNAME)->andReturn(true);
        $this->testmanagement_config->shouldReceive('isConfigNeeded')->andReturn(false);

        $this->assertTrue($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project));
    }

    public function testPaneCanNotBeDisplayedWhenTheAgileDashboardServiceIsNotUsed(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->andReturn(false);
        $project->shouldReceive('usesService')->with(testmanagementPlugin::SERVICE_SHORTNAME)->andReturn(true);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project));
    }

    public function testPaneCanNotBeDisplayedWhenTheTestManagementServiceIsNotUsed(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->andReturn(true);
        $project->shouldReceive('usesService')->with(testmanagementPlugin::SERVICE_SHORTNAME)->andReturn(false);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project));
    }

    public function testPaneCanNotBeDisplayedWhenTheTestManagementServiceIsMisconfigured(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->andReturn(true);
        $project->shouldReceive('usesService')->with(testmanagementPlugin::SERVICE_SHORTNAME)->andReturn(true);
        $this->testmanagement_config->shouldReceive('isConfigNeeded')->andReturn(true);

        $this->assertFalse($this->testplan_pane_displayable->isTestPlanPaneDisplayable($project));
    }
}
