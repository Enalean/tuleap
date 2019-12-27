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

use GitRepository;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\PullRequest\PullRequest;

class HTMLURLBuilder
{
    /**
     * @var \GitRepositoryFactory
     */
    private $git_repository_factory;
    /**
     * @var InstanceBaseURLBuilder
     */
    private $instance_base_url_builder;

    public function __construct(\GitRepositoryFactory $git_repository_factory, InstanceBaseURLBuilder $instance_base_url_builder)
    {
        $this->git_repository_factory    = $git_repository_factory;
        $this->instance_base_url_builder = $instance_base_url_builder;
    }

    public function getPullRequestOverviewUrl(PullRequest $pull_request): string
    {
        $repository = $this->git_repository_factory->getRepositoryById($pull_request->getRepositoryId());
        $project_id = $repository->getProject()->getID();
        return '/plugins/git/?action=pull-requests&repo_id=' .
            urlencode($pull_request->getRepositoryId()) . '&group_id=' . urlencode($project_id) .
            '#/pull-requests/' . urlencode($pull_request->getId()) . '/overview';
    }

    public function getAbsolutePullRequestOverviewUrl(PullRequest $pull_request): string
    {
        return $this->instance_base_url_builder->build() . $this->getPullRequestOverviewUrl($pull_request);
    }

    public function getPullRequestDashboardUrl(GitRepository $repository)
    {
        $project_id = $repository->getProject()->getID();
        return '/plugins/git/?action=pull-requests&repo_id=' .
            urlencode($repository->getId()) . '&group_id=' . urlencode($project_id);
    }
}
