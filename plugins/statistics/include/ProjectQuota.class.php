<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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
 * Management of custom quota by project
 */
class ProjectQuota {

    protected $dao;

    /**
     * Display the list of projects having a custom quota
     *
     * @return ???
     */
    public function displayProjectQuota() {
        $titles = array('Project', 'Quota', 'delete');
        echo html_build_list_table_top($titles);
        $i = 0;
        echo '<form>';
        echo '<tr class="'. util_get_alt_row_color($i++) .'">';
        echo '<td>Project1</td><td>10 GB</td><td><input type="checkbox" /></td>';
        echo '</tr>';
        echo '<tr class="'. util_get_alt_row_color($i++) .'">';
        echo '<td>Project2</td><td>20 GB</td><td><input type="checkbox" /></td>';
        echo '</tr>';
        echo '<tr class="'. util_get_alt_row_color($i++) .'">';
        echo '<td></td><td></td><td><input type="submit" /></td>';
        echo '</tr>';
        echo '</form>';
        echo '</table>';
        echo '<table>';
        echo '<form>';
        echo '<tr><td colspan="2"><b>Set custom quota for a project</b></td></tr>';
        echo '<tr>';
        echo '<td>Project</td><td><input name="project" /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Quota</td><td><input name="quota" /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td></td><td><input type="submit" /></td>';
        echo '</tr>';
        echo '</form>';
        echo '</table>';
    }

    /**
     * Add custom quota for a project
     *
     * @param Project $project Project for which quota will be customized
     *
     * @return ???
     */
    public function addQuota($project) {
        
    }

    /**
     * Delete custom quota for a project
     *
     * @param Project $project Project for which custom quota will be deleted
     *
     * @return ???
     */
    public function deleteCustomQuota($project) {
        
    }

}

?>