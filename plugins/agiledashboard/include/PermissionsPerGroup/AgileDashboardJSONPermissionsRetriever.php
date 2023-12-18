<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\PermissionsPerGroup;

use AgileDashboardPlugin;
use PFUser;
use Project;

class AgileDashboardJSONPermissionsRetriever
{
    public function __construct(
        private readonly AgileDashboardPermissionsRepresentationBuilder $representation_builder,
    ) {
    }

    public function retrieve(Project $project, PFUser $user, $selected_ugroup_id = null)
    {
        if (! $project->usesService(AgileDashboardPlugin::PLUGIN_SHORTNAME)) {
            $GLOBALS['Response']->send400JSONErrors(
                [
                    'error' => dgettext('tuleap-agiledashboard', "Service Backlog is disabled."),
                ]
            );
        }

        $permission_representation = $this->representation_builder->build(
            $project,
            $user,
            $selected_ugroup_id
        );

        $GLOBALS['Response']->sendJSON(
            $permission_representation
        );
    }
}
