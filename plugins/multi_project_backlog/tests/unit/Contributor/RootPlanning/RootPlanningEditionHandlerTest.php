<?php
/*
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

namespace Tuleap\MultiProjectBacklog\Contributor\RootPlanning;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent;
use Tuleap\MultiProjectBacklog\Contributor\ContributorDao;

final class RootPlanningEditionHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var RootPlanningEditionHandler
     */
    private $handler;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ContributorDao
     */
    private $contributor_dao;

    protected function setUp(): void
    {
        $this->contributor_dao = M::mock(ContributorDao::class);
        $this->handler         = new RootPlanningEditionHandler($this->contributor_dao);
    }

    public function testHandleProhibitsMilestoneTrackerUpdateForContributorProjects(): void
    {
        $this->contributor_dao->shouldReceive('isProjectAContributorProject')
            ->with(110)
            ->andReturnTrue();

        $event = new RootPlanningEditionEvent(
            new \Project(['group_id' => '110']),
            new \Planning(50, 'Release Planning', 110, '', '')
        );
        $this->handler->handle($event);

        $this->assertNotNull($event->getMilestoneTrackerModificationBan());
    }

    public function testHandleAllowsMilestoneTrackerUpdateForAllOtherProjects(): void
    {
        $this->contributor_dao->shouldReceive('isProjectAContributorProject')
            ->with(112)
            ->andReturnFalse();

        $event = new RootPlanningEditionEvent(
            new \Project(['group_id' => '112']),
            new \Planning(50, 'Release Planning', 112, '', '')
        );
        $this->handler->handle($event);

        $this->assertNull($event->getMilestoneTrackerModificationBan());
    }
}
