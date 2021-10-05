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

namespace Tuleap\ProgramManagement\Domain\Team\RootPlanning;

use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent;
use Tuleap\ProgramManagement\Adapter\Events\RootPlanningEditionEventProxy;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;

final class RootPlanningEditionHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private VerifyIsTeamStub $team_verifier;
    private RootPlanningEditionEvent $event;
    private RootPlanningEditionEventProxy $event_proxy;

    protected function setUp(): void
    {
        $this->event         = new RootPlanningEditionEvent(
            new \Project(['group_id' => '110', 'group_name' => 'A project', 'unix_group_name' => 'a_project']),
            new \Planning(50, 'Release Planning', 110, '', '')
        );
        $this->team_verifier = VerifyIsTeamStub::withValidTeam();
        $this->event_proxy   = RootPlanningEditionEventProxy::buildFromEvent($this->event);
    }

    private function getHandler(): RootPlanningEditionHandler
    {
        return new RootPlanningEditionHandler($this->team_verifier);
    }

    public function testHandleProhibitsMilestoneTrackerUpdateForTeamProjects(): void
    {
        $this->getHandler()->handle($this->event_proxy);

        $this->assertNotNull($this->event->getMilestoneTrackerModificationBan());
    }

    public function testHandleAllowsMilestoneTrackerUpdateForAllOtherProjects(): void
    {
        $this->team_verifier = VerifyIsTeamStub::withNotValidTeam();

        $this->getHandler()->handle($this->event_proxy);

        $this->assertNull($this->event->getMilestoneTrackerModificationBan());
    }
}
