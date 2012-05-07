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

require_once 'Statistics_ProjectQuotaDao.class.php';

/**
 * Management of custom quota by project
 */
class ProjectQuota {

    protected $dao;

    /**
     * Constructor of the class
     *
     * @return Void
     */
    public function __construct() {
        $this->dao = new Statistics_ProjectQuotaDao();
    }

    /**
     * Display the list of projects having a custom quota
     *
     * @return String
     */
    public function displayProjectQuota() {
        $output = '';
        $res    = $this->dao->getProjectsCustomQuota();
        if ($res && !$res->isError() && $res->rowCount() > 0) {
            $i      = 0;
            $titles = array($GLOBALS['Language']->getText('global', 'Project'), $GLOBALS['Language']->getText('plugin_statistics', 'quota'), $GLOBALS['Language']->getText('global', 'delete'));
            $output .= html_build_list_table_top($titles);
            $output .= '<form method="post" >';
            foreach ($res as $row) {
                $output .= '<tr class="'. util_get_alt_row_color($i++) .'">';
                $output .= '<td>'.$row['project'].'</td><td>'.$row[Statistics_ProjectQuotaDao::REQUEST_SIZE].' GB</td><td><input type="checkbox" name="delete_quota[]" value="'.$row[Statistics_ProjectQuotaDao::GROUP_ID].'" /></td>';
                $output .= '</tr>';
            }
            $output .= '<tr class="'. util_get_alt_row_color($i++) .'">';
            $output .= '<input type="hidden" name ="action" value="delete" />';
            $output .= '<td></td><td></td><td><input type="submit" /></td>';
            $output .= '</tr>';
            $output .= '</form>';
            $output .= '</table>';
        }
        $output .= '<table>';
        $output .= '<form method="post" >';
        $output .= '<tr><td colspan="2"><b>'.$GLOBALS['Language']->getText('plugin_statistics', 'set_quota').'</b></td></tr>';
        $output .= '<tr>';
        $output .= '<td>'.$GLOBALS['Language']->getText('global', 'Project').'</td><td><input id="project" name="project" /></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<td>'.$GLOBALS['Language']->getText('plugin_statistics', 'quota').' (GB) </td><td><input name="quota" /></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<input type="hidden" name ="action" value="add" />';
        $output .= '<td></td><td><input type="submit" /></td>';
        $output .= '</tr>';
        $output .= '</form>';
        $output .= '</table>';
        $js     = "new ProjectAutoCompleter('project', '".util_get_dir_image_theme()."');";
        $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
        return $output;
    }

    /**
     * Handle the HTTP request
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Void
     */
    public function handleRequest($request) {
        $validAction = new Valid_WhiteList('action', array('add', 'delete'));
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
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_action'));
        }
    }

    /**
     * Add custom quota for a project
     *
     * @param String  $project Project for which quota will be customized
     * @param Integer $quota   Quota to be set for the project
     *
     * @return Void
     */
    public function addQuota($project, $quota) {
        if (empty($project)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_project'));
        } elseif (empty($quota)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_quota'));
        } else {
            $pm = ProjectManager::instance();
            $project = $pm->getProjectFromAutocompleter($project);
            if ($project) {
                if ($this->dao->addException($project->getGroupID(), null, $quota, null)) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_statistics', 'quota_added', array($project->getPublicName(), $quota)));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'add_error'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'no_project'));
            }
        }
    }

    /**
     * Delete custom quota for a project
     *
     * @param Array $projects List of projects for which custom quota will be deleted
     *
     * @return Void
     */
    public function deleteCustomQuota($projects) {
        if (empty($projects)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'nothing_to_delete'));
        } else {
            if ($this->dao->deleteCustomQuota($projects)) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_statistics', 'quota_added', array(join(', ', $projects))));
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'delete_error'));
            }
        }
    }

}

?>