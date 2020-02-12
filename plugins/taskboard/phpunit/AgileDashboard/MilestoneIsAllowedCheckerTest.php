<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\AgileDashboard;

use Cardwall_OnTop_Dao;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning_Milestone;
use PluginManager;
use Project;
use taskboardPlugin;

class MilestoneIsAllowedCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PluginManager
     */
    private $plugin_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|taskboardPlugin
     */
    private $plugin;
    /**
     * @var Cardwall_OnTop_Dao|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $dao;
    /**
     * @var MilestoneIsAllowedChecker
     */
    private $checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_Milestone
     */
    private $milestone;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TaskboardUsage
     */
    private $usage;

    protected function setUp(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(102);
        $project->shouldReceive('getPublicName')->andReturn('My project');

        $this->milestone = Mockery::mock(Planning_Milestone::class);
        $this->milestone->shouldReceive('getProject')->andReturn($project);
        $this->milestone->shouldReceive('getTrackerId')->andReturn(42);

        $this->plugin_manager = Mockery::mock(PluginManager::class);
        $this->plugin         = Mockery::mock(taskboardPlugin::class);
        $this->dao            = Mockery::mock(Cardwall_OnTop_Dao::class);
        $this->usage          = Mockery::mock(TaskboardUsage::class);

        $this->checker = new MilestoneIsAllowedChecker(
            $this->dao,
            $this->usage,
            $this->plugin_manager,
            $this->plugin
        );
    }

    public function testItRaisesExceptionIfProjectIsNotAllowedToUseThePlugin(): void
    {
        $this->plugin_manager
            ->shouldReceive('isPluginAllowedForProject')
            ->with($this->plugin, 102)
            ->once()
            ->andReturnFalse();

        $this->expectException(MilestoneIsNotAllowedException::class);

        $this->checker->checkMilestoneIsAllowed($this->milestone);
    }

    public function testItRaisesExceptionIfCardwallOnTopIsNotEnabled(): void
    {
        $this->plugin_manager
            ->shouldReceive('isPluginAllowedForProject')
            ->with($this->plugin, 102)
            ->once()
            ->andReturnTrue();

        $this->dao
            ->shouldReceive('isEnabled')
            ->with(42)
            ->once()
            ->andReturnFalse();

        $this->expectException(MilestoneIsNotAllowedException::class);

        $this->checker->checkMilestoneIsAllowed($this->milestone);
    }

    public function testItRaisesExceptionIfTaskboardIsNotAllowed(): void
    {
        $this->plugin_manager
            ->shouldReceive('isPluginAllowedForProject')
            ->with($this->plugin, 102)
            ->once()
            ->andReturnTrue();

        $this->dao
            ->shouldReceive('isEnabled')
            ->with(42)
            ->once()
            ->andReturnTrue();

        $this->usage
            ->shouldReceive('isTaskboardAllowed')
            ->once()
            ->andReturnFalse();

        $this->expectException(MilestoneIsNotAllowedException::class);

        $this->checker->checkMilestoneIsAllowed($this->milestone);
    }

    public function testItDoesNotRaisesExceptionIfTaskboardIsAllowed(): void
    {
        $this->plugin_manager
            ->shouldReceive('isPluginAllowedForProject')
            ->with($this->plugin, 102)
            ->once()
            ->andReturnTrue();

        $this->dao
            ->shouldReceive('isEnabled')
            ->with(42)
            ->once()
            ->andReturnTrue();

        $this->usage
            ->shouldReceive('isTaskboardAllowed')
            ->once()
            ->andReturnTrue();

        $this->checker->checkMilestoneIsAllowed($this->milestone);
    }
}
