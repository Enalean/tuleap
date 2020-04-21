<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use PlanningFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;

class PlanningUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PlanningUpdater
     */
    private $planning_updater;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planning_factory                  = Mockery::mock(PlanningFactory::class);
        $this->artifacts_in_explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);

        $this->planning_updater = new PlanningUpdater(
            $this->planning_factory,
            $this->artifacts_in_explicit_backlog_dao
        );
    }

    public function testItUpdatesStandardPlanning(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);
        $planning_parameters = Mockery::mock(\PlanningParameters::class);

        $this->planning_factory->shouldReceive('updatePlanning')
            ->once()
            ->withArgs([10, 102, $planning_parameters]);

        $planning = Mockery::mock(\Planning::class);
        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);
        $planning->shouldReceive('getId')->andReturn(20);

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('removeNoMoreSelectableItemsFromExplicitBacklogOfProject')->never();

        $this->planning_updater->update($user, $project, 10, $planning_parameters);
    }

    public function testItUpdatesExplicitBacklogPlanning(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);
        $planning_parameters = Mockery::mock(\PlanningParameters::class);

        $this->planning_factory->shouldReceive('updatePlanning')
            ->once()
            ->withArgs([10, 102, $planning_parameters]);

        $planning = Mockery::mock(\Planning::class);
        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);
        $planning->shouldReceive('getId')->andReturn(10);

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('removeNoMoreSelectableItemsFromExplicitBacklogOfProject')->once();

        $this->planning_updater->update($user, $project, 10, $planning_parameters);
    }
}
