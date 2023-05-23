<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\PullRequest\Factory;
use GitRepositoryFactory;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;

class ReferenceFactory
{
    /**
     * @var ProjectReferenceRetriever
     */
    private $reference_retriever;

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var Factory
     */
    private $pull_request_factory;
    /**
     * @var HTMLURLBuilder
     */
    private $html_url_builder;

    public function __construct(
        Factory $pull_request_factory,
        GitRepositoryFactory $repository_factory,
        ProjectReferenceRetriever $reference_retriever,
        HTMLURLBuilder $html_url_builder,
    ) {
        $this->pull_request_factory = $pull_request_factory;
        $this->repository_factory   = $repository_factory;
        $this->reference_retriever  = $reference_retriever;
        $this->html_url_builder     = $html_url_builder;
    }

    public function getReferenceByPullRequestId($keyword, $pull_request_id): ?\Reference
    {
        try {
            $pull_request  = $this->pull_request_factory->getPullRequestById($pull_request_id);
            $repository_id = $pull_request->getRepositoryId();
            $repository    = $this->repository_factory->getRepositoryById($repository_id);

            if (! $repository) {
                return null;
            }

            $project_id = $repository->getProjectId();

            if ($this->reference_retriever->doesProjectReferenceWithKeywordExists($keyword, $project_id)) {
                return null;
            }

            $html_url = $this->html_url_builder->getPullRequestOverviewUrl($pull_request);

            return new Reference($keyword, $html_url, $project_id);
        } catch (PullRequestNotFoundException | \GitRepoNotFoundException $ex) {
            return null;
        }
    }
}
