<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Tracker_SystemEventManager extends SystemEventManager
{

    /** @var  SystemEventManager */
    private $system_event_manager;

    public function __construct(SystemEventManager $system_event_manager)
    {
        $this->system_event_manager = $system_event_manager;
    }

    public function queueTV3Migration(PFUser $user, Project $project, $tracker_id, $name, $description, $short_name)
    {
        $this->system_event_manager->createEvent(
            SystemEvent_TRACKER_V3_MIGRATION::NAME,
            $short_name . SystemEvent::PARAMETER_SEPARATOR .
            $name . SystemEvent::PARAMETER_SEPARATOR .
            $description . SystemEvent::PARAMETER_SEPARATOR .
            $user->getUnixName() . SystemEvent::PARAMETER_SEPARATOR .
            $project->getGroupId() . SystemEvent::PARAMETER_SEPARATOR .
            $tracker_id,
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function isThereAMigrationQueuedForTracker(Tracker $tracker)
    {
        return $this->system_event_manager->isThereAnEventAlreadyOnGoingMatchingFirstParameter(SystemEvent_TRACKER_V3_MIGRATION::NAME, $tracker->getItemName());
    }

    public function isThereAMigrationQueuedForProject(Project $project)
    {
        return $this->system_event_manager->isThereAnEventAlreadyOnGoingMatchingParameter(SystemEvent_TRACKER_V3_MIGRATION::NAME, $project->getGroupId());
    }

    public function getTypes()
    {
        return array(
            SystemEvent_TRACKER_V3_MIGRATION::NAME,
        );
    }
}
