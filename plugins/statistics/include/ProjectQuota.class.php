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
     * @return String
     */
    public function displayProjectQuota() {
        $titles = array($GLOBALS['Language']->getText('global', 'Project'), $GLOBALS['Language']->getText('plugin_statistics', 'quota'), $GLOBALS['Language']->getText('global', 'delete'));
        $output = '';
        $output .= html_build_list_table_top($titles);
        $i = 0;
        $output .= '<form>';
        $output .= '<tr class="'. util_get_alt_row_color($i++) .'">';
        $output .= '<td>Project1</td><td>10 GB</td><td><input type="checkbox" name="delete_quota[]" value="Project1" /></td>';
        $output .= '</tr>';
        $output .= '<tr class="'. util_get_alt_row_color($i++) .'">';
        $output .= '<td>Project2</td><td>20 GB</td><td><input type="checkbox" name="delete_quota[]" value="Project2" /></td>';
        $output .= '</tr>';
        $output .= '<tr class="'. util_get_alt_row_color($i++) .'">';
        $output .= '<input type="hidden" name ="action" value="delete" />';
        $output .= '<td></td><td></td><td><input type="submit" /></td>';
        $output .= '</tr>';
        $output .= '</form>';
        $output .= '</table>';
        $output .= '<table>';
        $output .= '<form>';
        $output .= '<tr><td colspan="2"><b>'.$GLOBALS['Language']->getText('plugin_statistics', 'set_quota').'</b></td></tr>';
        $output .= '<tr>';
        $output .= '<td>'.$GLOBALS['Language']->getText('global', 'Project').'</td><td><input name="project" /></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<td>'.$GLOBALS['Language']->getText('plugin_statistics', 'quota').'</td><td><input name="quota" /></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<input type="hidden" name ="action" value="add" />';
        $output .= '<td></td><td><input type="submit" /></td>';
        $output .= '</tr>';
        $output .= '</form>';
        $output .= '</table>';
        return $output;
    }

    /**
     * Handle the HTTP request
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return ???
     */
    public function handleRequest($request) {
        // TODO: i18n in feddback messages
        $validAction = new Valid_WhiteList('action', array('add', 'delete'));
        $validAction->required();
        if($request->valid($validAction)) {
            $action = $request->get('action');
            switch ($action) {
                case 'add' :
                    $validProject = new Valid_String('project');
                    $validProject->required();
                    if($request->valid($validProject)) {
                        $project = $request->get('project');
                    }
                    $validQuota = new Valid_UInt('quota');
                    $validQuota->required();
                    if($request->valid($validQuota)) {
                        $quota   = $request->get('quota');
                    }
                    $this->addQuota($project, $quota);
                    break;
                case 'delete' :
                    // TODO: prepare the list of projects
                    $projects = $request->get('delete_quota');
                    $this->deleteCustomQuota($projects);
                    break;
                default :
                    break;
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', 'Invalid action');
        }
    }

    /**
     * Add custom quota for a project
     *
     * @param Project $project Project for which quota will be customized
     * @param Integer $quota   Quota to be set for the project
     *
     * @return ???
     */
    public function addQuota($project, $quota) {
        if (empty($project)) {
            $GLOBALS['Response']->addFeedback('error', 'No project');
        } elseif (empty($quota)) {
            $GLOBALS['Response']->addFeedback('error', 'No quota');
        } else {
            $GLOBALS['Response']->addFeedback('info', 'Quota for project "'.$project.'" is now '.$quota.' GB');
        }
    }

    /**
     * Delete custom quota for a project
     *
     * @param Array $projects List of projects for which custom quota will be deleted
     *
     * @return ???
     */
    public function deleteCustomQuota($projects) {
        if (empty($projects)) {
            $GLOBALS['Response']->addFeedback('error', 'Nothing to delete');
        } else {
            $GLOBALS['Response']->addFeedback('info', 'Custom quota deleted for '.join(', ', $projects));
        }
    }

}

?>