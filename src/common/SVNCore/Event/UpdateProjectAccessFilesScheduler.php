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

namespace Tuleap\SVNCore\Event;

use Project;
use SystemEvent;
use SystemEventManager;

class UpdateProjectAccessFilesScheduler
{
    public function __construct(private SystemEventManager $system_event_manager)
    {
    }

    public function scheduleUpdateOfProjectAccessFiles(Project $project): void
    {
        if (
            $this->system_event_manager->areThereMultipleEventsQueuedMatchingFirstParameter(
                SystemEvent::TYPE_SVN_UPDATE_PROJECT_ACCESS_FILES,
                $project->getID()
            )
        ) {
            return;
        }

        if (
            $this->system_event_manager->areThereMultipleEventsQueuedMatchingFirstParameter(
                SystemEvent::TYPE_UGROUP_MODIFY,
                $project->getID()
            )
        ) {
            return;
        }

        $this->system_event_manager->createEvent(
            SystemEvent::TYPE_SVN_UPDATE_PROJECT_ACCESS_FILES,
            $project->getID(),
            SystemEvent::PRIORITY_MEDIUM
        );
    }
}
