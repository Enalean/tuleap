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

use PHPUnit\Framework\MockObject\MockObject;
use PlanningFactory;
use PlanningParameters;
use PlanningPermissionsManager;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanningUpdaterTest extends TestCase
{
    private PlanningUpdater $planning_updater;
    private ArtifactsInExplicitBacklogDao&MockObject $artifacts_in_explicit_backlog_dao;
    private PlanningFactory&MockObject $planning_factory;
    private PlanningDao&MockObject $planning_dao;
    private PlanningPermissionsManager&MockObject $permissions_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->planning_factory                  = $this->createMock(PlanningFactory::class);
        $this->artifacts_in_explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);
        $this->planning_dao                      = $this->createMock(PlanningDao::class);
        $this->permissions_manager               = $this->createMock(PlanningPermissionsManager::class);

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
        $project             = ProjectTestBuilder::aProject()->withId(102)->build();
        $updated_planning_id = 10;
        $planning_parameters = $this->createMock(PlanningParameters::class);

        $this->planning_dao->expects($this->once())->method('updatePlanning')
            ->with($updated_planning_id, $planning_parameters);
        $this->permissions_manager->expects($this->once())->method('savePlanningPermissionForUgroups');

        $planning = PlanningBuilder::aPlanning(102)->withId(20)->build();
        $this->planning_factory->method('getRootPlanning')->willReturn($planning);

        $this->artifacts_in_explicit_backlog_dao->expects($this->never())->method('removeNoMoreSelectableItemsFromExplicitBacklogOfProject');

        $this->planning_updater->update($user, $project, $updated_planning_id, $planning_parameters);
    }

    public function testItUpdatesExplicitBacklogPlanning(): void
    {
        $user                = UserTestBuilder::aUser()->build();
        $project             = ProjectTestBuilder::aProject()->withId(102)->build();
        $updated_planning_id = 10;
        $planning_parameters = $this->createMock(PlanningParameters::class);

        $this->planning_dao->expects($this->once())->method('updatePlanning')
            ->with($updated_planning_id, $planning_parameters);
        $this->permissions_manager->expects($this->once())->method('savePlanningPermissionForUgroups');

        $planning = PlanningBuilder::aPlanning(102)->withId($updated_planning_id)->build();
        $this->planning_factory->method('getRootPlanning')->willReturn($planning);

        $this->artifacts_in_explicit_backlog_dao->expects($this->once())->method(
            'removeNoMoreSelectableItemsFromExplicitBacklogOfProject'
        );

        $this->planning_updater->update($user, $project, $updated_planning_id, $planning_parameters);
    }
}
