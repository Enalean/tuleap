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

namespace Tuleap\Taskboard\REST\v1\Columns;

use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use Rest_Exception_InvalidTokenException;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsAllowedChecker;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsNotAllowedException;

final class ColumnsGetterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ColumnsGetter $columns_getter;
    private UserManager&MockObject $user_manager;
    private \Planning_MilestoneFactory&MockObject $milestone_factory;
    private MilestoneIsAllowedChecker&MockObject $milestone_checker;
    private \Cardwall_OnTop_ColumnDao&MockObject $column_dao;

    protected function setUp(): void
    {
        $this->user_manager      = $this->createMock(UserManager::class);
        $this->milestone_factory = $this->createMock(\Planning_MilestoneFactory::class);
        $this->milestone_checker = $this->createMock(MilestoneIsAllowedChecker::class);
        $this->column_dao        = $this->createMock(\Cardwall_OnTop_ColumnDao::class);
        $this->columns_getter    = new ColumnsGetter(
            $this->user_manager,
            $this->milestone_factory,
            $this->milestone_checker,
            $this->column_dao
        );
    }

    public function testGetColumnsThrowsWhenCurrentUserIsNotAuthenticated(): void
    {
        $this->user_manager->method('getCurrentUser')->willThrowException(new Rest_Exception_InvalidTokenException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(401);
        $this->columns_getter->getColumns(18);
    }

    public function testGetColumnsThrowsWhenMilestoneCantBeFound(): void
    {
        $current_user = $this->buildCurrentUser();
        $this->milestone_factory->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($current_user, 18)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->columns_getter->getColumns(18);
    }

    public function testGetColumnsThrowsWhenMilestoneIsNotAllowed(): void
    {
        $current_user = $this->buildCurrentUser();
        $milestone    = $this->createMock(\Planning_ArtifactMilestone::class);
        $this->milestone_factory->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($current_user, 18)
            ->willReturn($milestone);
        $this->milestone_checker->expects(self::once())
            ->method('checkMilestoneIsAllowed')
            ->with($milestone)
            ->willThrowException(new MilestoneIsNotAllowedException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->columns_getter->getColumns(18);
    }

    public function testGetColumnsReturnsEmptyArray(): void
    {
        $current_user = $this->buildCurrentUser();
        $milestone    = $this->mockMilestone($current_user);
        $planning     = $this->createMock(\Planning::class);
        $planning->expects(self::once())
            ->method('getPlanningTrackerId')
            ->willReturn(98);
        $milestone->expects(self::once())
            ->method('getPlanning')
            ->willReturn($planning);
        $this->column_dao->expects(self::once())
            ->method('searchColumnsByTrackerId')
            ->willReturn([]);

        $result = $this->columns_getter->getColumns(18);
        self::assertEmpty($result);
    }

    public function testGetColumnsReturnsColumnsRepresentations(): void
    {
        $current_user = $this->buildCurrentUser();
        $milestone    = $this->mockMilestone($current_user);
        $planning     = $this->createMock(\Planning::class);
        $planning->expects(self::once())
            ->method('getPlanningTrackerId')
            ->willReturn(98);
        $milestone->expects(self::once())
            ->method('getPlanning')
            ->willReturn($planning);
        $this->column_dao->expects(self::once())
            ->method('searchColumnsByTrackerId')
            ->willReturn(
                [
                    ['id' => 26, 'label' => 'Todo', 'tlp_color_name' => 'fiesta-red', 'bg_red' => null, 'bg_green' => null, 'bg_blue' => null],
                    ['id' => 27, 'label' => 'On Going', 'tlp_color_name' => 'acid-green', 'bg_red' => null, 'bg_green' => null, 'bg_blue' => null],
                ]
            );

        $result = $this->columns_getter->getColumns(18);
        self::assertSame(2, count($result));
        $first_column  = $result[0];
        $second_column = $result[1];
        self::assertSame(26, $first_column->id);
        self::assertSame('Todo', $first_column->label);
        self::assertSame('fiesta-red', $first_column->header_color);
        self::assertSame(27, $second_column->id);
        self::assertSame('On Going', $second_column->label);
        self::assertSame('acid-green', $second_column->header_color);
    }

    private function buildCurrentUser(): \PFUser
    {
        $current_user = UserTestBuilder::aUser()->build();
        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($current_user);

        return $current_user;
    }

    private function mockMilestone(\PFUser $current_user): \Planning_ArtifactMilestone&MockObject
    {
        $milestone = $this->createMock(\Planning_ArtifactMilestone::class);
        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($current_user, 18)
            ->willReturn($milestone);
        $this->milestone_checker
            ->expects(self::once())
            ->method('checkMilestoneIsAllowed')
            ->with($milestone);

        return $milestone;
    }
}
