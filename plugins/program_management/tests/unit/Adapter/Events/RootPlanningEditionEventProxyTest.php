<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent as CoreEvent;
use Tuleap\Test\PHPUnit\TestCase;

final class RootPlanningEditionEventProxyTest extends TestCase
{
    private CoreEvent $event;

    protected function setUp(): void
    {
        $this->event = new \Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent(
            new \Project(['group_id' => '110', 'group_name' => 'A project', 'unix_group_name' => 'a_project']),
            new \Planning(50, 'Release Planning', 110, '', '')
        );
    }

    public function testItBuildFromEvent(): void
    {
        $event_proxy = RootPlanningEditionEventProxy::buildFromEvent($this->event);
        self::assertSame((int) $this->event->getProject()->getID(), $event_proxy->getProjectIdentifier()->getId());
    }

    public function testItProhibitMilestoneTrackerModification(): void
    {
        $event_proxy = RootPlanningEditionEventProxy::buildFromEvent($this->event);
        $event_proxy->prohibitMilestoneTrackerModification();

        self::assertNotNull($this->event->getMilestoneTrackerModificationBan());
    }
}
