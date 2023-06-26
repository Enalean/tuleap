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
use PHPUnit\Framework\MockObject\MockObject;
use Planning_Milestone;
use PluginManager;
use taskboardPlugin;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class MilestoneIsAllowedCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&PluginManager $plugin_manager;
    private MockObject&taskboardPlugin $plugin;
    private MockObject&Cardwall_OnTop_Dao $dao;
    private MilestoneIsAllowedChecker $checker;
    private MockObject&Planning_Milestone $milestone;
    private MockObject&TaskboardUsage $usage;

    protected function setUp(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(102)->withPublicName('My project')->build();

        $this->milestone = $this->createMock(Planning_Milestone::class);
        $this->milestone->method('getProject')->willReturn($project);
        $this->milestone->method('getTrackerId')->willReturn(42);

        $this->plugin_manager = $this->createMock(PluginManager::class);
        $this->plugin         = $this->createMock(taskboardPlugin::class);
        $this->dao            = $this->createMock(Cardwall_OnTop_Dao::class);
        $this->usage          = $this->createMock(TaskboardUsage::class);

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
            ->expects(self::once())
            ->method('isPluginAllowedForProject')
            ->with($this->plugin, 102)
            ->willReturn(false);

        $this->expectException(MilestoneIsNotAllowedException::class);

        $this->checker->checkMilestoneIsAllowed($this->milestone);
    }

    public function testItRaisesExceptionIfCardwallOnTopIsNotEnabled(): void
    {
        $this->plugin_manager
            ->expects(self::once())
            ->method('isPluginAllowedForProject')
            ->with($this->plugin, 102)
            ->willReturn(true);

        $this->dao
            ->expects(self::once())
            ->method('isEnabled')
            ->with(42)
            ->willReturn(false);

        $this->expectException(MilestoneIsNotAllowedException::class);

        $this->checker->checkMilestoneIsAllowed($this->milestone);
    }

    public function testItRaisesExceptionIfTaskboardIsNotAllowed(): void
    {
        $this->plugin_manager
            ->expects(self::once())
            ->method('isPluginAllowedForProject')
            ->with($this->plugin, 102)
            ->willReturn(true);

        $this->dao
            ->expects(self::once())
            ->method('isEnabled')
            ->with(42)
            ->willReturn(true);

        $this->usage
            ->expects(self::once())
            ->method('isTaskboardAllowed')
            ->willReturn(false);

        $this->expectException(MilestoneIsNotAllowedException::class);

        $this->checker->checkMilestoneIsAllowed($this->milestone);
    }

    public function testItDoesNotRaisesExceptionIfTaskboardIsAllowed(): void
    {
        $this->plugin_manager
            ->expects(self::once())
            ->method('isPluginAllowedForProject')
            ->with($this->plugin, 102)
            ->willReturn(true);

        $this->dao
            ->expects(self::once())
            ->method('isEnabled')
            ->with(42)
            ->willReturn(true);

        $this->usage
            ->expects(self::once())
            ->method('isTaskboardAllowed')
            ->willReturn(true);

        $this->checker->checkMilestoneIsAllowed($this->milestone);
    }
}
