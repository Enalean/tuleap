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

class TaskboardPaneInfoBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TaskboardPaneInfoBuilder
     */
    private $builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_Milestone
     */
    private $milestone;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MilestoneIsAllowedChecker
     */
    private $checker;

    protected function setUp(): void
    {
        $this->milestone = Mockery::mock(Planning_Milestone::class);

        $this->checker = Mockery::mock(MilestoneIsAllowedChecker::class);

        $this->builder = new TaskboardPaneInfoBuilder($this->checker);
    }

    public function testItReturnsNullIfMilestoneIsNotAllowed(): void
    {
        $this->checker
            ->shouldReceive('checkMilestoneIsAllowed')
            ->with($this->milestone)
            ->once()
            ->andThrow(MilestoneIsNotAllowedException::class);

        $this->assertNull($this->builder->getPaneForMilestone($this->milestone));
    }

    public function testItReturnsPaneInfo(): void
    {
        $this->checker
            ->shouldReceive('checkMilestoneIsAllowed')
            ->with($this->milestone)
            ->once();

        $this->assertInstanceOf(TaskboardPaneInfo::class, $this->builder->getPaneForMilestone($this->milestone));
    }
}
