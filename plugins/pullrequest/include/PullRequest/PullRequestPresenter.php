<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

use Tuleap\PullRequest\MergeSetting\MergeSetting;

class PullRequestPresenter
{
    /** @var PullRequestCount */
    private $nb_pull_requests;

    /** @var int */
    public $repository_id;

    /** @var int */
    public $user_id;

    /** @var string */
    public $language;

    /** @var bool */
    public $is_there_at_least_one_pull_request;

    /** @var bool */
    public $is_merge_commit_allowed;

    public function __construct($repository_id, $user_id, $language, PullRequestCount $nb_pull_requests, MergeSetting $merge_setting)
    {
        $this->repository_id                      = $repository_id;
        $this->user_id                            = $user_id;
        $this->language                           = $language;
        $this->nb_pull_requests                   = $nb_pull_requests;
        $this->is_there_at_least_one_pull_request = $nb_pull_requests->isThereAtLeastOnePullRequest();
        $this->is_merge_commit_allowed            = $merge_setting->isMergeCommitAllowed();
    }

    public function getTemplateName()
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
