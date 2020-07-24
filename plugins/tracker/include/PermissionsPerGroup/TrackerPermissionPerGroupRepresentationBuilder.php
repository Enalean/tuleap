<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

namespace Tuleap\Tracker\PermissionsPerGroup;

require_once __DIR__ . '/../tracker_permissions.php';

use Project;
use Tracker;
use TrackerFactory;

class TrackerPermissionPerGroupRepresentationBuilder
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var TrackerPermissionPerGroupPermissionRepresentationBuilder
     */
    private $permission_representation_builder;

    public function __construct(
        TrackerFactory $tracker_factory,
        TrackerPermissionPerGroupPermissionRepresentationBuilder $permission_representation_builder
    ) {
        $this->tracker_factory                   = $tracker_factory;
        $this->permission_representation_builder = $permission_representation_builder;
    }

    public function build(Project $project, $ugroup_id = null)
    {
        $trackers_list = $this->tracker_factory->getTrackersByGroupId($project->group_id);
        $permissions   = [];

        foreach ($trackers_list as $tracker) {
            $ugroups_permissions = plugin_tracker_permission_get_tracker_ugroups_permissions(
                $tracker->getGroupId(),
                $tracker->getId()
            );

            $permissions_per_group = $this->permission_representation_builder->build(
                $project,
                $ugroups_permissions,
                $ugroup_id
            );

            if (count($permissions_per_group) === 0) {
                continue;
            }

            $permissions[] = new TrackerPermissionPerGroupRepresentation(
                $tracker->getName(),
                $this->getTrackerAdminQuickLink($tracker),
                $permissions_per_group
            );
        }

        return $permissions;
    }

    private function getTrackerAdminQuickLink(Tracker $tracker)
    {
        $query_parameters = http_build_query(
            [
                "tracker" => $tracker->getId(),
                "func"    => "admin-perms-tracker"
            ]
        );

        return "/plugins/tracker/?" . $query_parameters;
    }
}
