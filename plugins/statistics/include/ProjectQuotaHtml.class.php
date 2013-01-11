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

require_once('ProjectQuotaManager.class.php');
require_once('common/include/CSRFSynchronizerToken.class.php');

/**
 * Management of custom quota by project
 */
class ProjectQuotaHtml {

    /**
     * ProjectManager instance
     */
    protected $projectManager;

    /**
     * ProjectQuotaManager instance
     */
    protected $projectQuotaManager;

    /**
     * Constructor of the class
     *
     * @return Void
     */
    public function __construct() {
        $this->projectManager      = ProjectManager::instance();
        $this->projectQuotaManager = new ProjectQuotaManager();
        $this->csrf                = new CSRFSynchronizerToken('project_quota.php');
    }

    /**
     * Validate project quota offset param used for display formatting.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    private function validateOffset(HTTPRequest $request) {
        $valid = new Valid('offset');
        $valid->setErrorMessage('Invalid offset submitted. Force it to 0 (zero).');
        $valid->addRule(new Rule_Int());
        $valid->addRule(new Rule_GreaterOrEqual(0));
        if ($request->valid($valid)) {
            $offset = $request->get('offset');
        } else {
            $offset = 0;
        }
        return $offset;
    }

    /**
     * Validate project quota filtering param used for display formatting.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return String
     */
    private function validateProjectFilter(HTTPRequest $request) {
        $validFilter = new Valid_String('project_filter');
        $filter      = null;
        if ($request->valid($validFilter)) {
            $filter = $request->get('project_filter');
        }
        return $filter;
    }

    /**
     * Validate project quota ordering params used for display formatting.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Array
     */
    private function validateOrderByFilter(HTTPRequest $request) {
        $validSort = new Valid_String('sort');
        $sortBy    = null;
        $validRequest = array();
        if ($request->valid($validSort)) {
            $sortBy  = $request->get('sort');
            $validRequest['sort'] = $sortBy;
            $validOrderBy = new Valid_String('order');
            if ($request->valid($validOrderBy)) {
                if ($request->get('order') == "ASC" || $request->get('order') == "DESC") {
                    $orderBy = $request->get('order');
                } else {
                    $orderBy = null;
                }
                $validRequest['order'] = $orderBy;
            }
        }
        return $validRequest;
    }

    /**
     * Toggler used to sort records in a descending/ascending order.
     *
     * @param String $order Current order sens
     *
     * @return String
     */
    private function toggleOrderBy($order) {
        if ($order == "ASC") {
            $order = "DESC";
        } else {
            $order = "ASC";
        }
        return $order;
    }

    /**
     * Display the list of projects having a custom quota
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return String
     */
    public function displayProjectQuota(HTTPRequest $request) {
        $output      = '';
        $count       = 50;
        $offset      = $this->validateOffset($request);
        $filter      = $this->validateProjectFilter($request);
        $orderParams = $this->validateOrderByFilter($request);
        $sortBy      = $orderParams['sort'];
        $orderBy     = $orderParams['order'];
        $list        = $this->getListOfProjectsIds($filter);

        $projectFilterParam = '';
        if ($filter) {
            if (empty($list)) {
                $output .= $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_statistics', 'no_search_result'));
            }
            $projectFilterParam = '&amp;project_filter='.$filter;
        }

        $resultExist  = false;
        $customQuotas = $this->projectQuotaManager->getAllCustomQuota($list, $offset, $count, $sortBy, $orderBy);
        if ($customQuotas && !$customQuotas->isError() && $customQuotas->rowCount() > 0) {
            $resultExist = true;
        } else {
            if ($filter) {
                $output .= $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_statistics', 'no_search_result'));
            }
            $customQuotas = $this->projectQuotaManager->getAllCustomQuota(array(), $offset, $count, $sortBy, $orderBy);
            if ($customQuotas && !$customQuotas->isError() && $customQuotas->rowCount() > 0) {
                $resultExist = true;
            }
        }
        if ($resultExist) {
            $output .= $this->fetchFilterForm();
            $output .= $this->fetchCustomQuotaTable($customQuotas, $offset, $count, $sortBy, $orderBy, $projectFilterParam, $list);
            $output .= '<br />';
        } else {
            $output .= '<p><em>'. $GLOBALS['Language']->getText('plugin_statistics', 'no_projects', $this->projectQuotaManager->getDefaultQuota()) .'</em></p>';
        }

        $output .= $this->renderNewCustomQuotaForm();
        return $output;
    }

    /**
     * Get the HTML of the filter form
     *
     * @return string html
     */
    private function fetchFilterForm() {
        $output  = '';
        $output .= '<form method="get">';
        $output .= $GLOBALS['Language']->getText('plugin_statistics', 'search_projects').' ';
        $output .= '<input name="project_filter" /><input type="submit" />';
        $output .= '</form>';
        return $output;
    }

    /**
     * Get the html output of the table of projects having custom quota
     *
     * @param Iterator $customQuotas       Database result of the projects custom quota
     * @param Integer  $offset             Pagination offset
     * @param Integer  $count              Items to display by page
     * @param String   $sortBy             Property used for the sort
     * @param String   $orderBy            Ascending or descending
     * @param String   $projectFilterParam Project filter
     * @param Array    $list               List of project Id's
     *
     * @return String html
     */
    private function fetchCustomQuotaTable(Iterator $customQuotas, $offset, $count, $sortBy, $orderBy, $projectFilterParam, $list) {
        $paginationParams = $this->getPagination($offset, $count, $sortBy, $orderBy, $projectFilterParam, $customQuotas);
        $nextHref = $paginationParams['nextHref'];
        $prevHref = $paginationParams['prevHref'];
        $orderBy  = $this->toggleOrderBy($orderBy);

        $output   = '';
        $i        = 0;
        $purifier = Codendi_HTMLPurifier::instance();
        $um       = UserManager::instance();
        $titles   = array($GLOBALS['Language']->getText('global', 'Project'), $GLOBALS['Language']->getText('plugin_statistics', 'requester'), '<a href="?sort=quota&amp;order='.$orderBy.$projectFilterParam.'&amp;offset='.$offset.'">'.$GLOBALS['Language']->getText('plugin_statistics', 'quota').'</a>', $GLOBALS['Language']->getText('plugin_statistics', 'motivation'), '<a href="?sort=date&amp;order='.$orderBy.$projectFilterParam.'&amp;offset='.$offset.'">'.$GLOBALS['Language']->getText('plugin_statistics', 'date').'</a>', $GLOBALS['Language']->getText('global', 'delete'));
        $output  .= '<form method="post">';
        $output  .= html_build_list_table_top($titles);
        foreach ($customQuotas as $row) {
            $project     = $this->projectManager->getProject($row[Statistics_ProjectQuotaDao::GROUP_ID]);
            $projectName = (empty($project)) ? '' : $project->getPublicName();
            $user        = $um->getUserById($row[Statistics_ProjectQuotaDao::REQUESTER_ID]);
            $username    = (empty($user)) ? '' : $user->getUserName();
            
            $output .= '<tr class="'. util_get_alt_row_color($i++) .'">';
            $output .= '<td><a href="project_stat.php?group_id='.$row[Statistics_ProjectQuotaDao::GROUP_ID].'" >'.$projectName.'</a></td>';
            $output .= '<td>'.$username.'</td><td>'.$row[Statistics_ProjectQuotaDao::REQUEST_SIZE].' GB</td>';
            $output .= '<td><pre>'.$purifier->purify($row[Statistics_ProjectQuotaDao::EXCEPTION_MOTIVATION], CODENDI_PURIFIER_BASIC, $row[Statistics_ProjectQuotaDao::GROUP_ID]).'</pre></td>';
            $output .= '<td>'.strftime("%d %b %Y", $row[Statistics_ProjectQuotaDao::REQUEST_DATE]).'</td><td><input type="checkbox" name="delete_quota[]" value="'.$row[Statistics_ProjectQuotaDao::GROUP_ID].'" /></td>';
            $output .= '</tr>';
        }
        $output .= '<tr class="'. util_get_alt_row_color($i++) .'">';
        $output .= $this->csrf->fetchHTMLInput();
        $output .= '<input type="hidden" name ="action" value="delete" />';
        $output .= '<td colspan="5" ><td><input type="submit" /></td>';
        $output .= '</tr>';
        $output .= '<tr><td>'.$prevHref.'</td><td colspan="4" ></td><td>'.$nextHref.'</td></tr>';
        $output .= '</table>';
        $output .= '</form>';
        return $output;
    }

    /**
     * Obtain the list of projects id corresponding to the filter
     *
     * @param string $filter The filter
     *
     * @return array of int (groups ids)
     */
    private function getListOfProjectsIds($filter) {
        $list = array();
        if ($filter) {
            $result   = $this->projectManager->getAllProjectsRows(0, 20, false, $filter);
            $projects = $result['projects'];
            foreach ($projects as $entry) {
                $list[] = $entry['group_id'];
            }
        }
        return $list;
    }

    /**
     * Render pagination for project quota display
     *
     * @param int    $offset             From where the result will be displayed.
     * @param int    $count              How many results are returned.
     * @param String $sortBy             Order result set according to this parameter
     * @param String $orderBy            Specifiy if the result set sort is ascending or descending
     * @param String $projectFilterParam Search filter
     * @param Array  $list               List of projects Id corresponding to a given filter
     *
     * @return Array
     */
    private function getPagination($offset, $count, $sortBy, $orderBy, $projectFilterParam, $list) {
        $params       = array();
        $foundRows    = $list->rowCount();
        $prevHref     = '&lt;Previous';
        if ($offset > 0) {
            $prevOffset = $offset - $count;
            if ($prevOffset < 0) {
                $prevOffset = 0;
            }
            $prevHref = '<a href="?sort='.$sortBy.'&amp;order='.$orderBy.$projectFilterParam.'&amp;offset='.$prevOffset.'">'.$prevHref.'</a>';
        }
        $params['prevHref'] = $prevHref;
        $nextHref           = 'Next&gt;';
        $nextOffset         = $offset + $count;
        if ($nextOffset >= $foundRows) {
            $nextOffset = null;
        } else {
            $nextHref = '<a href="?sort='.$sortBy.'&amp;order='.$orderBy.$projectFilterParam.'&amp;offset='.$nextOffset.'">'.$nextHref.'</a>';
        }
        $params['nextHref'] = $nextHref;
        return $params;
    }

    /**
     * Render form to set custom quota for a given project
     *
     * @return String
     */
    private function renderNewCustomQuotaForm() {
        $max_quota = (int)$this->projectQuotaManager->getMaximumQuota();
        $output  = '';
        $output .= '<table>';
        $output .= '<form method="post" >';
        $output .= $this->csrf->fetchHTMLInput();
        $output .= '<tr valign="top"><td colspan="2"><b>'.$GLOBALS['Language']->getText('plugin_statistics', 'set_quota').'</b></td></tr>';
        $output .= '<tr valign="top">';
        $output .= '<td>'.$GLOBALS['Language']->getText('global', 'Project').' <span class="highlight">*</span></td><td><input id="project" name="project" /></td>';
        $output .= '</tr>';
        $output .= '<tr valign="top">';
        $output .= '<td>'.$GLOBALS['Language']->getText('plugin_statistics', 'requester').'</td><td><input id="requester" name="requester" /></td>';
        $output .= '</tr>';
        $output .= '<tr valign="top">';
        $output .= '<td>'.$GLOBALS['Language']->getText('plugin_statistics', 'quota').' (GB) <span class="highlight">*</span></td>';
        $output .= '<td><input name="quota" type="number" min="0" max="'. $max_quota .'"/><p class="help-block">'. $GLOBALS['Language']->getText('plugin_statistics', 'max_quota', $max_quota) .'</p></td>';
        $output .= '</tr>';
        $output .= '<tr valign="top">';
        $output .= '<td>'.$GLOBALS['Language']->getText('plugin_statistics', 'motivation').'</td><td><textarea name="motivation" rows="5" cols="50" ></textarea></td>';
        $output .= '</tr>';
        $output .= '<tr valign="top">';
        $output .= '<input type="hidden" name ="action" value="add" />';
        $output .= '<td></td><td><input type="submit" /></td>';
        $output .= '</tr>';
        $output .= '</form>';
        $output .= '</table>';
        $output .= '<p><span class="highlight">'.$GLOBALS['Language']->getText('plugin_docman', 'new_mandatory_help').'</span></p>';
        return $output;
    }

    /**
     * Handle the HTTP request
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Void
     */
    public function handleRequest(HTTPRequest $request) {
        $validAction = new Valid_WhiteList('action', array('add', 'delete'));
        if ($request->valid($validAction)) {
            $action = $request->get('action');
            switch ($action) {
                case 'add' :
                    $this->csrf->check();
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
                        $quota = $request->get('quota');
                    }
                    $validMotivation = new Valid_Text('motivation');
                    $validMotivation->required();
                    $motivation = null;
                    if ($request->valid($validMotivation)) {
                        $motivation = $request->get('motivation');
                    }
                    $this->projectQuotaManager->addQuota($project, $requester, $quota, $motivation);
                    break;
                case 'delete' :
                    $this->csrf->check();
                    $list = $request->get('delete_quota');
                    if (!empty($list)) {
                        $projects       = array();
                        $validProjectId = new Valid_UInt();
                        foreach ($list as $projectId) {
                            if ($validProjectId->validate($projectId)) {
                                $project = $this->projectManager->getProject($projectId);
                                if ($project) {
                                    $projects[$project->getId()] = $project->getPublicName();
                                }
                            }
                        }
                        $this->projectQuotaManager->deleteCustomQuota($projects);
                    }
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