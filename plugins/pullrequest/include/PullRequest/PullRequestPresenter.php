<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Tuleap\date\DefaultRelativeDatesDisplayPreferenceRetriever;
use Tuleap\PullRequest\MergeSetting\MergeSetting;

final class PullRequestPresenter
{
    public bool $is_there_at_least_one_pull_request;
    public bool $is_merge_commit_allowed;
    public bool $allow_pullrequest_v2;
    public int $user_id;
    public string $user_avatar_url;
    public string $relative_date_display;
    public string $language;
    public int $repository_id;
    public int $project_id;

    public function __construct(\GitRepository $repository, \PFUser $user, private PullRequestCount $nb_pull_requests, MergeSetting $merge_setting, public bool $is_vue_overview_shown)
    {
        $this->repository_id                      = $repository->getId();
        $this->project_id                         = $repository->getProjectId();
        $this->is_there_at_least_one_pull_request = $nb_pull_requests->isThereAtLeastOnePullRequest();
        $this->is_merge_commit_allowed            = $merge_setting->isMergeCommitAllowed();
        $this->allow_pullrequest_v2               = PullRequestV2FeatureFlag::isPullRequestV2Displayed($repository);
        $this->user_id                            = (int) $user->getId();
        $this->user_avatar_url                    = $user->getAvatarUrl();
        $this->language                           = $user->getShortLocale();
        $this->relative_date_display              = $user->getPreference(\DateHelper::PREFERENCE_NAME) ?: DefaultRelativeDatesDisplayPreferenceRetriever::retrieveDefaultValue();
    }

    public function getTemplateName(): string
    {
        return 'angular-pullrequest';
    }

    public function nb_pull_request_badge() // phpcs:ignore
    {
        $nb_open = $this->nb_pull_requests->getNbOpen();
        if ($nb_open <= 1) {
            return $GLOBALS['Language']->getText('plugin_pullrequest', 'nb_pull_request_badge', [$nb_open]);
        }

        return $GLOBALS['Language']->getText('plugin_pullrequest', 'nb_pull_request_badge_plural', [$nb_open]);
    }
}
