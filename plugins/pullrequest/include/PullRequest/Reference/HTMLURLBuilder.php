<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Reference;

use GitRepoNotFoundException;
use GitRepository;
use Tuleap\PullRequest\FrontendApps\FeatureFlagSetOldHomepageViewByDefault;
use Tuleap\PullRequest\PullRequest;
use Tuleap\ServerHostname;

class HTMLURLBuilder
{
    /**
     * @var \GitRepositoryFactory
     */
    private $git_repository_factory;

    public function __construct(\GitRepositoryFactory $git_repository_factory)
    {
        $this->git_repository_factory = $git_repository_factory;
    }

    public function getPullRequestOverviewUrl(PullRequest $pull_request): string
    {
        $repository = $this->git_repository_factory->getRepositoryById($pull_request->getRepositoryId());
        if (! $repository) {
            throw new GitRepoNotFoundException();
        }

        $project_id   = $repository->getProject()->getID();
        $query_params = [
            'action'   => 'pull-requests',
            'repo_id'  => $pull_request->getRepositoryId(),
            'group_id' => $project_id,
            'tab'      => 'overview',
        ];

        return '/plugins/git/?'
            . http_build_query($query_params)
            . '#/pull-requests/'
            . urlencode($pull_request->getId())
            . '/overview';
    }

    public function getAbsolutePullRequestOverviewUrl(PullRequest $pull_request): string
    {
        return ServerHostname::HTTPSUrl() . $this->getPullRequestOverviewUrl($pull_request);
    }

    public function getPullRequestDashboardUrl(GitRepository $repository): string
    {
        $project_id = $repository->getProject()->getID();

        $query_params = [
            "action" => "pull-requests",
            "repo_id" => $repository->getId(),
            "group_id" => $project_id,
        ];

        if (! FeatureFlagSetOldHomepageViewByDefault::isActive()) {
            $query_params["tab"] = "homepage";
        }

        return '/plugins/git/?' . http_build_query($query_params);
    }
}
