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

namespace Tuleap\PullRequest\DefaultSettings;

use DateTimeImmutable;
use GitPlugin;
use HTTPRequest;
use ProjectHistoryDao;
use Tuleap\Git\Permissions\VerifyUserIsGitAdministrator;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\ServiceInstrumentation;
use Tuleap\PullRequest\MergeSetting\MergeSettingDAO;
use Tuleap\PullRequest\ProvideCSRFTokenSynchronizer;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class DefaultSettingsController implements DispatchableWithRequest
{
    public const HISTORY_FIELD_NAME = 'pullrequest-default-settings';

    public function __construct(
        private readonly MergeSettingDAO $merge_setting_dao,
        private readonly ProjectHistoryDao $project_history_dao,
        private readonly VerifyUserIsGitAdministrator $git_permission_manager,
        private readonly ProvideCSRFTokenSynchronizer $csrf_token_provider,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $request->getProject();
        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext('tuleap-git', 'Git service is disabled.'));
        }

        ServiceInstrumentation::increment('git');

        $current_user = $request->getCurrentUser();
        if (
            ! $current_user->isAdmin($project->getID()) && ! $this->git_permission_manager->userIsGitAdmin(
                $current_user,
                $project
            )
        ) {
            throw new ForbiddenException();
        }

        $pane_url = DefaultSettingsUrlBuilder::build($project);
        $this->csrf_token_provider->getCSRFToken($project)->check();

        $is_merge_commit_allowed = (string) $request->get('is_merge_commit_allowed');

        $this->merge_setting_dao->saveDefaultSettings($project->getId(), $is_merge_commit_allowed);
        $layout->addFeedback(\Feedback::INFO, dgettext('tuleap-pullrequest', 'Default pull requests settings updated'));

        $this->project_history_dao->addHistory(
            $project,
            $current_user,
            isset($_SERVER['REQUEST_TIME']) ?  new DateTimeImmutable('@' . $_SERVER['REQUEST_TIME']) : new DateTimeImmutable(),
            self::HISTORY_FIELD_NAME,
            $is_merge_commit_allowed,
        );

        $layout->redirect($pane_url);
    }
}
