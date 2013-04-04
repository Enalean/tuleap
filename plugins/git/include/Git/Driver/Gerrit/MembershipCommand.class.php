<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * I'm responsible of managing propagation of user membership changes (add/remove) to gerrit.
 */
abstract class Git_Driver_Gerrit_MembershipCommand {
    protected $driver;

    public function __construct(Git_Driver_Gerrit $driver) {
        $this->driver = $driver;
    }

    protected abstract function propagateToGerrit(Git_RemoteServer_GerritServer $server, PFUser $user, $group_full_name);

    public function execute(Git_RemoteServer_GerritServer $server, PFUser $user, Project $project, UGroup $ugroup) {
        $group_full_name = $this->getGerritGroupName($project, $ugroup->getNormalizedName());
        $this->propagateToGerrit($server, $user, $group_full_name);
    }

    private function getGerritGroupName(Project $project, $ugroup_name) {
        $project_name    = $project->getUnixName();
        return "$project_name/$ugroup_name";
    }

}
?>
