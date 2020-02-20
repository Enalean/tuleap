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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Rest_Exception_InvalidTokenException;
use UserManager;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsAllowedChecker;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsNotAllowedException;

final class ColumnsGetterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ColumnsGetter */
    private $columns_getter;
    /** @var M\LegacyMockInterface|M\MockInterface|UserManager */
    private $user_manager;
    /** @var M\LegacyMockInterface|M\MockInterface|\Planning_MilestoneFactory */
    private $milestone_factory;
    /** @var M\LegacyMockInterface|M\MockInterface|MilestoneIsAllowedChecker */
    private $milestone_checker;
    /** @var \Cardwall_OnTop_ColumnDao|M\LegacyMockInterface|M\MockInterface */
    private $column_dao;

    protected function setUp(): void
    {
        $this->user_manager      = M::mock(UserManager::class);
        $this->milestone_factory = M::mock(\Planning_MilestoneFactory::class);
        $this->milestone_checker = M::mock(MilestoneIsAllowedChecker::class);
        $this->column_dao        = M::mock(\Cardwall_OnTop_ColumnDao::class);
        $this->columns_getter    = new ColumnsGetter(
            $this->user_manager,
            $this->milestone_factory,
            $this->milestone_checker,
            $this->column_dao
        );
    }

    public function testGetColumnsThrowsWhenCurrentUserIsNotAuthenticated(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andThrow(new Rest_Exception_InvalidTokenException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(401);
        $this->columns_getter->getColumns(18);
    }

    public function testGetColumnsThrowsWhenMilestoneCantBeFound(): void
    {
        $current_user = $this->mockCurrentUser();
        $this->milestone_factory->shouldReceive('getBareMilestoneByArtifactId')
            ->with($current_user, 18)
            ->once()
            ->andReturnNull();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->columns_getter->getColumns(18);
    }

    public function testGetColumnsThrowsWhenMilestoneIsNotAllowed(): void
    {
        $current_user = $this->mockCurrentUser();
        $milestone    = M::mock(\Planning_ArtifactMilestone::class);
        $this->milestone_factory->shouldReceive('getBareMilestoneByArtifactId')
            ->with($current_user, 18)
            ->once()
            ->andReturn($milestone);
        $this->milestone_checker->shouldReceive('checkMilestoneIsAllowed')
            ->with($milestone)
            ->once()
            ->andThrow(MilestoneIsNotAllowedException::class);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->columns_getter->getColumns(18);
    }

    public function testGetColumnsReturnsEmptyArray(): void
    {
        $current_user = $this->mockCurrentUser();
        $milestone    = $this->mockMilestone($current_user);
        $planning     = M::mock(\Planning::class);
        $planning->shouldReceive('getPlanningTrackerId')
            ->once()
            ->andReturn(98);
        $milestone->shouldReceive('getPlanning')
            ->once()
            ->andReturn($planning);
        $this->column_dao->shouldReceive('searchColumnsByTrackerId')
            ->once()
            ->andReturn([]);

        $result = $this->columns_getter->getColumns(18);
        $this->assertEmpty($result);
    }

    public function testGetColumnsReturnsColumnsRepresentations(): void
    {
        $current_user = $this->mockCurrentUser();
        $milestone    = $this->mockMilestone($current_user);
        $planning     = M::mock(\Planning::class);
        $planning->shouldReceive('getPlanningTrackerId')
            ->once()
            ->andReturn(98);
        $milestone->shouldReceive('getPlanning')
            ->once()
            ->andReturn($planning);
        $this->column_dao->shouldReceive('searchColumnsByTrackerId')
            ->once()
            ->andReturn(
                [
                    ['id' => 26, 'label' => 'Todo', 'tlp_color_name' => 'fiesta-red', 'bg_red' => null, 'bg_green' => null, 'bg_blue' => null],
                    ['id' => 27, 'label' => 'On Going', 'tlp_color_name' => 'acid-green', 'bg_red' => null, 'bg_green' => null, 'bg_blue' => null],
                ]
            );

        $result = $this->columns_getter->getColumns(18);
        $this->assertSame(2, count($result));
        $first_column  = $result[0];
        $second_column = $result[1];
        $this->assertSame(26, $first_column->id);
        $this->assertSame('Todo', $first_column->label);
        $this->assertSame('fiesta-red', $first_column->header_color);
        $this->assertSame(27, $second_column->id);
        $this->assertSame('On Going', $second_column->label);
        $this->assertSame('acid-green', $second_column->header_color);
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|\PFUser
     */
    private function mockCurrentUser()
    {
        $current_user = M::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($current_user);
        return $current_user;
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|\Planning_ArtifactMilestone
     */
    private function mockMilestone(\PFUser $current_user)
    {
        $milestone = M::mock(\Planning_ArtifactMilestone::class);
        $this->milestone_factory->shouldReceive('getBareMilestoneByArtifactId')
            ->with($current_user, 18)
            ->once()
            ->andReturn($milestone);
        $this->milestone_checker->shouldReceive('checkMilestoneIsAllowed')
            ->with($milestone)
            ->once();
        return $milestone;
    }
}
