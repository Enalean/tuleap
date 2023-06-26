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

use PHPUnit\Framework\MockObject\MockObject;
use Planning_Milestone;

final class TaskboardPaneInfoBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TaskboardPaneInfoBuilder $builder;
    private MockObject&Planning_Milestone $milestone;
    private MockObject&MilestoneIsAllowedChecker $checker;

    protected function setUp(): void
    {
        $this->milestone = $this->createMock(Planning_Milestone::class);
        $this->checker   = $this->createMock(MilestoneIsAllowedChecker::class);

        $this->builder = new TaskboardPaneInfoBuilder($this->checker);
    }

    public function testItReturnsNullIfMilestoneIsNotAllowed(): void
    {
        $this->checker
            ->expects(self::once())
            ->method('checkMilestoneIsAllowed')
            ->with($this->milestone)
            ->willThrowException(new MilestoneIsNotAllowedException());

        self::assertNull($this->builder->getPaneForMilestone($this->milestone));
    }

    public function testItReturnsPaneInfo(): void
    {
        $this->checker
            ->expects(self::once())
            ->method('checkMilestoneIsAllowed')
            ->with($this->milestone);

        self::assertInstanceOf(TaskboardPaneInfo::class, $this->builder->getPaneForMilestone($this->milestone));
    }
}
