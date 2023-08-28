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
use PlanningFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class PlanningUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
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
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningDao
     */
    private $planning_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PlanningPermissionsManager
     */
    private $permissions_manager;

    protected function setUp(): void
    {
        $this->planning_factory                  = Mockery::mock(PlanningFactory::class);
        $this->artifacts_in_explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);
        $this->planning_dao                      = Mockery::mock(PlanningDao::class);
        $this->permissions_manager               = Mockery::mock(\PlanningPermissionsManager::class);

        $this->planning_updater = new PlanningUpdater(
            $this->planning_factory,
            $this->artifacts_in_explicit_backlog_dao,
            $this->planning_dao,
            $this->permissions_manager,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testItUpdatesStandardPlanning(): void
    {
        $user                = UserTestBuilder::aUser()->build();
        $project             = new \Project(['group_id' => '102']);
        $updated_planning_id = 10;
        $planning_parameters = Mockery::mock(\PlanningParameters::class);

        $this->planning_dao->shouldReceive('updatePlanning')
            ->once()
            ->with($updated_planning_id, $planning_parameters);
        $this->permissions_manager->shouldReceive('savePlanningPermissionForUgroups')->once();

        $planning = PlanningBuilder::aPlanning(102)->withId(20)->build();
        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive(
            'removeNoMoreSelectableItemsFromExplicitBacklogOfProject'
        );

        $this->planning_updater->update($user, $project, $updated_planning_id, $planning_parameters);
    }

    public function testItUpdatesExplicitBacklogPlanning(): void
    {
        $user                = UserTestBuilder::aUser()->build();
        $project             = new \Project(['group_id' => '102']);
        $updated_planning_id = 10;
        $planning_parameters = Mockery::mock(\PlanningParameters::class);

        $this->planning_dao->shouldReceive('updatePlanning')
            ->once()
            ->with($updated_planning_id, $planning_parameters);
        $this->permissions_manager->shouldReceive('savePlanningPermissionForUgroups')->once();

        $planning = PlanningBuilder::aPlanning(102)->withId($updated_planning_id)->build();
        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);

        $this->artifacts_in_explicit_backlog_dao->shouldReceive(
            'removeNoMoreSelectableItemsFromExplicitBacklogOfProject'
        )->once();

        $this->planning_updater->update($user, $project, $updated_planning_id, $planning_parameters);
    }
}
