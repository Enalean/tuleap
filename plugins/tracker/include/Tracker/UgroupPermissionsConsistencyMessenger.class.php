<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * I provide feedback to user about ugroup permissions consistency
 */
class Tracker_UgroupPermissionsConsistencyMessenger
{

    public function ugroupsAreTheSame($ugroup_names)
    {
        $ugroup_names = $this->formatUGroupNames($ugroup_names);
        echo '<div class="alert alert-info">';
        echo $GLOBALS['Language']->getText('plugin_tracker', 'info_same_ugroups', $ugroup_names);
        echo '</div>';
    }

    public function ugroupsMissing($missing_ugroup_names)
    {
        $missing_ugroup_names = $this->formatUGroupNames($missing_ugroup_names);
        echo '<div class="alert alert-warning">';
        echo $GLOBALS['Language']->getText('plugin_tracker', 'missing_ugroups', $missing_ugroup_names);
        echo '</div>';
    }

    public function allIsWell()
    {
    }

    private function formatUGroupNames($ugroup_names)
    {
        return '<ul><li>' . implode('</li><li>', $ugroup_names) . '</li></ul>';
    }
}
