<?php
/**
 * Copyright (c) Enalean, 2015 – Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Statistics\AdminHeaderPresenter;
use Tuleap\Statistics\ProjectsOverQuotaPresenter;

/**
 * Management of custom quota by project
 */
class ProjectQuotaHtml
{
    /**
     * ProjectManager instance
     */
    protected $projectManager;

    /**
     * ProjectQuotaManager instance
     */
    protected $projectQuotaManager;

    /** @var UserManager */
    private $user_manager;
    /**
     * @var InstanceBaseURLBuilder
     */
    private $instance_url_builder;
    /**
     * @var Codendi_HTMLPurifier
     */
    private $html_purifier;

    public function __construct(InstanceBaseURLBuilder $instance_url_builder, Codendi_HTMLPurifier $html_purifier)
    {
        $this->dao                  = new Statistics_ProjectQuotaDao();
        $this->projectManager       = ProjectManager::instance();
        $this->projectQuotaManager  = new ProjectQuotaManager();
        $this->csrf                 = new CSRFSynchronizerToken('project_quota.php');
        $this->user_manager         = UserManager::instance();
        $this->instance_url_builder = $instance_url_builder;
        $this->html_purifier        = $html_purifier;
    }

    /**
     * Validate project quota offset param used for display formatting.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return int
     */
    private function validateOffset(HTTPRequest $request)
    {
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
     * @return string|null
     */
    private function validateProjectFilter(HTTPRequest $request)
    {
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
    private function validateOrderByFilter(HTTPRequest $request)
    {
        $validSort = new Valid_String('sort');
        $sortBy    = null;
        $validRequest = [];
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

    public function getListOfProjectQuotaPresenters(HTTPRequest $request)
    {
        $quotas       = [];
        $count        = 25;
        $offset       = $this->validateOffset($request);
        $filter       = $this->validateProjectFilter($request);
        $orderParams  = $this->validateOrderByFilter($request);
        $sortBy       = $orderParams['sort'];
        $orderBy      = $orderParams['order'];
        $list         = $this->getListOfProjectsIds($filter ?? '');
        $purifier     = Codendi_HTMLPurifier::instance();

        $customQuotas = $this->dao->getAllCustomQuota($list, $offset, $count, $sortBy, $orderBy);
        $total_size   = $this->dao->foundRows();
        foreach ($customQuotas as $row) {
            $project      = $this->projectManager->getProject($row[Statistics_ProjectQuotaDao::GROUP_ID]);
            $project_name = (empty($project)) ? '' : $project->getPublicName();
            $user         = $this->user_manager->getUserById($row[Statistics_ProjectQuotaDao::REQUESTER_ID]);

            $quotas[] = [
                'project_id'              => $row[Statistics_ProjectQuotaDao::GROUP_ID],
                'project_name'            => $project_name,
                'user_name'               => UserHelper::instance()->getDisplayNameFromUser($user),
                'quota'                   => sprintf(dgettext('tuleap-statistics', '%1$s GB'), $row[Statistics_ProjectQuotaDao::REQUEST_SIZE]),
                'motivation'              => $row[Statistics_ProjectQuotaDao::EXCEPTION_MOTIVATION],
                'purified_motivation'     => $purifier->purify(
                    $row[Statistics_ProjectQuotaDao::EXCEPTION_MOTIVATION],
                    CODENDI_PURIFIER_BASIC
                ),
                'date'                    => date(
                    $GLOBALS['Language']->getText('system', 'datefmt_short'),
                    $row[Statistics_ProjectQuotaDao::REQUEST_DATE]
                ),
                'purified_delete_confirm' => $purifier->purify(
                    sprintf(dgettext('tuleap-statistics', 'Wow, wait a minute. You are about to delete the quota for <b>%1$s</b> project. Please confirm your action.'), $project_name),
                    CODENDI_PURIFIER_LIGHT
                )
            ];
        }

        $pagination = new \Tuleap\Layout\PaginationPresenter(
            $count,
            $offset,
            count($quotas),
            $total_size,
            '/plugins/statistics/project_quota.php',
            []
        );

        return [
            'pagination' => $pagination,
            'quotas'     => $quotas
        ];
    }

    /**
     * Obtain the list of projects id corresponding to the filter
     *
     * @param string $filter The filter
     *
     * @return array of int (groups ids)
     */
    private function getListOfProjectsIds($filter)
    {
        $list = [];
        if (! $filter) {
            return $list;
        }
        $project = $this->projectManager->getProjectFromAutocompleter($filter);
        if (! $project) {
            return $list;
        }

        $result   = $this->projectManager->getAllProjectsRows(0, 20, false, $project->getUnixNameMixedCase());
        $projects = $result['projects'];
        foreach ($projects as $entry) {
            $list[] = $entry['group_id'];
        }

        return $list;
    }

    /**
     * Handle the HTTP request
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Void
     */
    public function handleRequest(HTTPRequest $request)
    {
        $validAction = new Valid_WhiteList('action', ['add', 'delete']);
        if ($request->valid($validAction)) {
            $action = $request->get('action');
            switch ($action) {
                case 'add':
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
                    $GLOBALS['Response']->redirect('/plugins/statistics/project_quota.php');
                    break;
                case 'delete':
                    $this->csrf->check();
                    $project_id       = $request->get('delete_quota');
                    $valid_project_id = new Valid_UInt();
                    if ($valid_project_id->validate($project_id)) {
                        $project = $this->projectManager->getProject($project_id);
                        if ($project && ! $project->isError()) {
                            $this->projectQuotaManager->deleteCustomQuota($project);
                        }
                    }
                    $GLOBALS['Response']->redirect('/plugins/statistics/project_quota.php');
                    break;
                default:
                    break;
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-statistics', 'Invalid Action'));
        }
    }

    /**
     * Display the list of the projects over disk quota
     *
     * @return Void
     */
    public function displayProjectsOverQuota()
    {
        $exceeding_projects = $this->projectQuotaManager->getProjectsOverQuota();
        if (count($exceeding_projects) > 0) {
            $exceeding_projects = $this->enhanceWithModalValues($exceeding_projects);
        }

        $title     = dgettext('tuleap-statistics', 'Projects exceeding their disk quota');
        $presenter = new ProjectsOverQuotaPresenter($this->getHeaderPresenter($title), $exceeding_projects);
        $renderer  = new AdminPageRenderer();
        $renderer->renderANoFramedPresenter($title, STATISTICS_TEMPLATE_DIR, 'projects-over-quota', $presenter);
    }

    /**
     * @return AdminHeaderPresenter
     */
    private function getHeaderPresenter($title)
    {
        return new AdminHeaderPresenter(
            $title,
            'project_quota'
        );
    }

    private function enhanceWithModalValues(array $exceeding_projects): array
    {
        $new_values['csrf_token']  = new CSRFSynchronizerToken('');
        $enhanced_projects         = [];

        foreach ($exceeding_projects as $project) {
            $new_values['subject_content'] = sprintf(dgettext('tuleap-statistics', '[Disk quota] [Warning] Project %1$s is exceeding allowed disk quota'), $project['project_name']);
            $new_values['body_content']    = sprintf(dgettext('tuleap-statistics', 'Dear project administrator,<br><br>The size of the project <a href="%1$s">%2$s</a> is: "%3$s".<br>We advise you to delete unneeded data in order to decrease the project size growth. <br>(please note that deleting items from a subversion repository has no effect on the disk size, on the contrary, it may increase your server repository size).<br>We advise you to avoid storing binaries into your Subversion repository.<br>For Documents, you can remove oldest versions in order to decrease the project\'s size.<br><br>--<br>Please note that without any actions from you, for the safety of the entire platform, your project may be suspended until we find a solution.'), $this->html_purifier->purify($this->instance_url_builder->build() . '/projects/' . urlencode($project['project_unix_name'])), $this->html_purifier->purify($project['project_name']), $this->html_purifier->purify($project['current_disk_space']));

            $enhanced_projects[] = array_merge($project, $new_values);
        }

        return $enhanced_projects;
    }
}
