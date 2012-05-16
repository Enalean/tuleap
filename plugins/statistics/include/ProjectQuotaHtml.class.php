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

require_once 'ProjectQuotaManager.class.php';

/**
 * Management of custom quota by project
 */
class ProjectQuotaHtml {

    public    $pm;
	protected $pqm;

    /**
     * Constructor of the class
     *
     * @return Void
     */
    public function __construct() {
	    $this->pqm = ProjectQuotaManager::instance();
        $this->pm  = ProjectManager::instance();
    }

    /**
     * Display the list of projects having a custom quota
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return String
     */
    public function displayProjectQuota($request) {
        $output = '';
        $valid  = new Valid('offset');
        $valid->setErrorMessage('Invalid offset submitted. Force it to 0 (zero).');
        $valid->addRule(new Rule_Int());
        $valid->addRule(new Rule_GreaterOrEqual(0));
        if($request->valid($valid)) {
            $offset = $request->get('offset');
        } else {
            $offset = 0;
        }

        $validFilter        = new Valid_String('project_filter');
        $filter             = null;
        $projectFilterParam = null;
        if ($request->valid($validFilter)) {
            $filter = $request->get('project_filter');
        }
        $validSort = new Valid_String('sort');
        $sortBy    = null;
        if ($request->valid($validSort)) {
            $sortBy  = $request->get('sort');
            $validOrderBy = new Valid_String('order');
            if ($request->valid($validOrderBy)) {
                if ($request->get('order') == "ASC" || $request->get('order') == "DESC") {
                    $orderBy = $request->get('order');
                } else {
                    $orderBy = null;
                }
            }
        }

        $list = array();
        if ($filter) {
            $result   = $this->pm->returnAllProjects(0, 20, false, $filter);
            $projects = $result['projects'];
            foreach ($projects as $entry) {
                $list[] = $entry['group_id'];
            }
            if (empty($list)) {
                $output .= '<div id="feedback"><ul class="feedback_warning"><li>'.$GLOBALS['Language']->getText('plugin_statistics', 'no_search_result').'</li></ul></div>';
            }
            $projectFilterParam = '&amp;project_filter='.$filter;
        }
        $output      .= '<form method="get" >';
        $output      .= $GLOBALS['Language']->getText('plugin_statistics', 'search_projects').'<input name="project_filter" /><input type="submit" />';
        $output      .= '</form>';
        $count        = 50;
        $res          = $this->pqm->getAllCustomQuota($list, $offset, $count, $sortBy, $orderBy);
        $foundRowsRes = $this->pqm->getAllCustomQuota($list);
        $foundRows    = $foundRowsRes->rowCount();
        // Prepare Navigation bar
        $prevHref = '&lt;Previous';
        if ($offset > 0) {
            $prevOffset = $offset - $count;
            if ($prevOffset < 0) {
                $prevOffset = 0;
            }
            $prevHref = '<a href="?sort='.$sortBy.'&amp;order='.$orderBy.$projectFilterParam.'&amp;offset='.$prevOffset.'">'.$prevHref.'</a>';
        }
        $nextHref = 'Next&gt;';
        $nextOffset = $offset + $count;
        if ($nextOffset >= $foundRows) {
            $nextOffset = null;
        } else {
            $nextHref = '<a href="?sort='.$sortBy.'&amp;order='.$orderBy.$projectFilterParam.'&amp;offset='.$nextOffset.'">'.$nextHref.'</a>';
        }
        ($orderBy == "ASC")? $orderBy = "DESC":$orderBy = "ASC";

        if ($res && !$res->isError() && $res->rowCount() > 0) {
            $i        = 0;
            $titles   = array($GLOBALS['Language']->getText('global', 'Project'), $GLOBALS['Language']->getText('plugin_statistics', 'requester'), '<a href="?sort=quota&amp;order='.$orderBy.$projectFilterParam.'&amp;offset='.$offset.'">'.$GLOBALS['Language']->getText('plugin_statistics', 'quota').'</a>', $GLOBALS['Language']->getText('plugin_statistics', 'motivation'), '<a href="?sort=date&amp;order='.$orderBy.$projectFilterParam.'&amp;offset='.$offset.'">'.$GLOBALS['Language']->getText('plugin_statistics', 'date').'</a>', $GLOBALS['Language']->getText('global', 'delete'));
            $output   .= html_build_list_table_top($titles);
            $output   .= '<form method="post" >';
            $purifier  = Codendi_HTMLPurifier::instance();
            foreach ($res as $row) {
                $project     = $this->pm->getProject($row[Statistics_ProjectQuotaDao::GROUP_ID]);
                $projectName = '';
                if ($project) {
                    $projectName = $project->getPublicName();
                }
                $um       = UserManager::instance();
                $user     = $um->getUserById($row[Statistics_ProjectQuotaDao::REQUESTER_ID]);
                $userName = '';
                if ($user) {
                    $username = $user->getUserName();
                }
                $output  .= '<tr class="'. util_get_alt_row_color($i++) .'">';
                $output  .= '<td><a href="project_stat.php?group_id='.$row[Statistics_ProjectQuotaDao::GROUP_ID].'" >'.$projectName.'</a></td><td>'.$username.'</td><td>'.$row[Statistics_ProjectQuotaDao::REQUEST_SIZE].' GB</td><td><pre>'.$purifier->purify($row[Statistics_ProjectQuotaDao::EXCEPTION_MOTIVATION], CODENDI_PURIFIER_BASIC, $row[Statistics_ProjectQuotaDao::GROUP_ID]).'</pre></td><td>'.strftime("%d %b %Y", $row[Statistics_ProjectQuotaDao::REQUEST_DATE]).'</td><td><input type="checkbox" name="delete_quota[]" value="'.$row[Statistics_ProjectQuotaDao::GROUP_ID].'" /></td>';
                $output  .= '</tr>';
            }
            $output .= '<tr class="'. util_get_alt_row_color($i++) .'">';
            $output .= '<input type="hidden" name ="action" value="delete" />';
            $output .= '<td></td><td></td><td></td><td></td><td></td><td><input type="submit" /></td>';
            $output .= '</tr>';
            $output .= '</form>';
            $output .= '<tr><td>'.$prevHref.'</td><td></td><td></td><td></td><td></td><td>'.$nextHref.'</td></tr>';
            $output .= '</table><br>';
        } else {
            $output .= $GLOBALS['Language']->getText('plugin_statistics', 'no_projects');
        }
        $output .= $this->renderNewCustomQuotaForm();
        return $output;
    }

    /**
     * Render form to set custom quota for a given project
     *
     * @return String
     */
    public function renderNewCustomQuotaForm() {
        $output  = '';
        $output .= '<table>';
        $output .= '<form method="post" >';
        $output .= '<tr><td colspan="2"><b>'.$GLOBALS['Language']->getText('plugin_statistics', 'set_quota').'</b></td></tr>';
        $output .= '<tr>';
        $output .= '<td>'.$GLOBALS['Language']->getText('global', 'Project').' <span class="highlight">*</span></td><td><input id="project" name="project" /></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<td>'.$GLOBALS['Language']->getText('plugin_statistics', 'requester').'</td><td><input id="requester" name="requester" /></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<td>'.$GLOBALS['Language']->getText('plugin_statistics', 'quota').' (GB) <span class="highlight">*</span></td><td><input name="quota" /></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<td>'.$GLOBALS['Language']->getText('plugin_statistics', 'motivation').'</td><td><textarea name="motivation" rows="5" cols="50" ></textarea></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<input type="hidden" name ="action" value="add" />';
        $output .= '<td></td><td><input type="submit" /></td>';
        $output .= '</tr>';
        $output .= '</form>';
        $output .= '</table>';
        $output .= '<p><span class="highlight">'.$GLOBALS['Language']->getText('plugin_docman', 'new_mandatory_help').'</span></p>';
        $js      = "new ProjectAutoCompleter('project', '".util_get_dir_image_theme()."');";
        $js     .= "new UserAutoCompleter('requester', '".util_get_dir_image_theme()."');";
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
        if ($request->valid($validAction)) {
            $action = $request->get('action');
            switch ($action) {
                case 'add' :
                    $validProject = new Valid_String('project');
                    $validProject->required();
                    $project = null;
                    if ($request->valid($validProject)) {
                        $project = $request->get('project');
                    }
                    $validRequester = new Valid_String('requester');
                    $validRequester->required();
                    $requester = null;
                    if ($request->valid($validRequester)) {
                        $requester = $request->get('requester');
                    }
                    $validQuota = new Valid_UInt('quota');
                    $validQuota->required();
                    $quota = null;
                    if ($request->valid($validQuota)) {
                        $quota   = $request->get('quota');
                    }
                    $validMotivation = new Valid_Text('motivation');
                    $validMotivation->required();
                    $motivation = null;
                    if ($request->valid($validMotivation)) {
                        $motivation = $request->get('motivation');
                    }
                    $this->pqm->addQuota($project, $requester, $quota, $motivation);
                    break;
                case 'delete' :
                    $list       = $request->get('delete_quota');
                    $projects   = array();
                    $validProjectId = new Valid_UInt();
                    foreach ($list as $projectId) {
                        if ($validProjectId->validate($projectId)) {
                            $project = $this->pm->getProject($projectId);
                            if ($project) {
                                $projects[$project->getId()] = $project->getPublicName();
                            }
                        }
                    }
                    $this->pqm->deleteCustomQuota($projects);
                    break;
                default :
                    break;
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_action'));
        }
    }
}

?>