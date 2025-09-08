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
use Tuleap\ProgramManagement\Adapter\Team\RootPlanning\MilestoneTrackerUpdateProhibited;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Domain\Events\RootPlanningEditionEvent;
use Tuleap\ProgramManagement\Domain\Workspace\ProjectIdentifier;

final class RootPlanningEditionEventProxy implements RootPlanningEditionEvent
{
    private function __construct(private CoreEvent $event, private ProjectIdentifier $project_identifier)
    {
    }

    public static function buildFromEvent(CoreEvent $event): self
    {
        return new self($event, ProjectProxy::buildFromProject($event->getProject()));
    }

    #[\Override]
    public function getProjectIdentifier(): ProjectIdentifier
    {
        return $this->project_identifier;
    }

    #[\Override]
    public function prohibitMilestoneTrackerModification(): void
    {
        $this->event->prohibitMilestoneTrackerModification(new MilestoneTrackerUpdateProhibited());
    }
}
