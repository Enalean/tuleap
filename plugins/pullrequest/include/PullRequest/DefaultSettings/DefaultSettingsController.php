<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\DefaultSettings;

use HTTPRequest;
use ProjectHistoryDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\PullRequest\MergeSetting\MergeSettingDAO;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class DefaultSettingsController implements DispatchableWithRequest
{
    const HISTORY_FIELD_NAME = 'pullrequest-default-settings';
    /**
     * @var MergeSettingDAO
     */
    private $merge_setting_dao;
    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;

    public function __construct(MergeSettingDAO $merge_setting_dao, ProjectHistoryDao $project_history_dao)
    {
        $this->merge_setting_dao   = $merge_setting_dao;
        $this->project_history_dao = $project_history_dao;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $request->getProject();
        if (! $project->usesService(\gitPlugin::SERVICE_SHORTNAME)) {
            throw new \Tuleap\Request\NotFoundException(dgettext("tuleap-git", "Git service is disabled."));
        }

        \Tuleap\Project\ServiceInstrumentation::increment('git');

        if (! $request->getCurrentUser()->isAdmin($project->getID())) {
            throw new ForbiddenException();
        }

        $is_merge_commit_allowed = (int) $request->get('is_merge_commit_allowed');

        $this->merge_setting_dao->saveDefaultSettings($project->getId(), $is_merge_commit_allowed);
        $layout->addFeedback(\Feedback::INFO, dgettext("tuleap-pullrequest", "Default pull requests settings updated"));

        $this->project_history_dao->groupAddHistory(
            self::HISTORY_FIELD_NAME,
            $is_merge_commit_allowed,
            $project->getID()
        );

        $layout->redirect(
            GIT_BASE_URL . '/?' . http_build_query(
                [
                    'action'   => 'admin-default-settings',
                    'group_id' => $project->getID(),
                    'pane'     => PullRequestPane::NAME
                ]
            )
        );
    }
}
