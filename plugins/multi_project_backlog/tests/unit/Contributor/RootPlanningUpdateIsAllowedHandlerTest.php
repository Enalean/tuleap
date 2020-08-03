<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Contributor;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningUpdateIsAllowedEvent;

final class RootPlanningUpdateIsAllowedHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var RootPlanningUpdateIsAllowedHandler
     */
    private $handler;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ContributorDao
     */
    private $contributor_dao;

    protected function setUp(): void
    {
        $this->contributor_dao = M::mock(ContributorDao::class);
        $this->handler         = new RootPlanningUpdateIsAllowedHandler($this->contributor_dao);
    }

    /**
     * @dataProvider dataProviderTrackerIds
     */
    public function testHandleDisallowsRootPlanningUpdateForContributorProjectsWhenTheMilestoneTrackerHasChanged(
        ?int $original_milestone_tracker_id,
        ?string $updated_milestone_tracker_id
    ): void {
        $this->contributor_dao->shouldReceive('isProjectAContributorProject')
            ->with(110)
            ->andReturnTrue();

        $event = new RootPlanningUpdateIsAllowedEvent(
            new \Project(['group_id' => '110']),
            new \Planning(50, 'Release Planning', '110', '', '', [], $original_milestone_tracker_id),
            \PlanningParameters::fromArray(['planning_tracker_id' => $updated_milestone_tracker_id])
        );
        $this->handler->handle($event);

        $this->assertFalse($event->isUpdateAllowed());
    }

    public function dataProviderTrackerIds(): array
    {
        return [
            "Original null" => [null, '109'],
            "Updated null"  => [109, null],
            "Both defined"  => [109, '207']
        ];
    }

    public function testHandleAllowsRootPlanningUpdateForContributorProjectsWhenTheMilestoneTrackerDidNotChange(): void
    {
        $this->contributor_dao->shouldReceive('isProjectAContributorProject')
            ->with(110)
            ->andReturnTrue();

        $event = new RootPlanningUpdateIsAllowedEvent(
            new \Project(['group_id' => '110']),
            new \Planning(50, 'Release Planning', '110', '', '', [], 109),
            \PlanningParameters::fromArray(['planning_tracker_id' => '109'])
        );
        $this->handler->handle($event);

        $this->assertTrue($event->isUpdateAllowed());
    }

    public function testHandleAllowsRootPlanningUpdateForAllOtherProjects(): void
    {
        $this->contributor_dao->shouldReceive('isProjectAContributorProject')
            ->with(112)
            ->andReturnFalse();

        $event = new RootPlanningUpdateIsAllowedEvent(
            new \Project(['group_id' => '112']),
            new \Planning(50, 'Release Planning', '112', '', '', [], '109'),
            \PlanningParameters::fromArray(['name' => 'Renamed Release Planning'])
        );
        $this->handler->handle($event);

        $this->assertTrue($event->isUpdateAllowed());
    }
}
