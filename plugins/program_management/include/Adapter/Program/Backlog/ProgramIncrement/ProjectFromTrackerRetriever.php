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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\RetrieveFullTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProjectFromTracker;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\TrackerReference;

final class ProjectFromTrackerRetriever implements RetrieveProjectFromTracker
{
    public function __construct(private RetrieveFullTracker $tracker_retriever)
    {
    }

    #[\Override]
    public function fromTrackerReference(TrackerReference $tracker): ProjectReference
    {
        $full_tracker = $this->tracker_retriever->getNonNullTracker($tracker);
        return ProjectProxy::buildFromProject($full_tracker->getProject());
    }
}
