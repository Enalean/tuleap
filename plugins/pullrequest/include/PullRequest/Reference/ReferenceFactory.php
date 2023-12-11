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

use GitRepositoryFactory;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;

class ReferenceFactory
{
    public function __construct(
        private readonly PullRequestRetriever $pull_request_factory,
        private readonly GitRepositoryFactory $repository_factory,
        private readonly ProjectReferenceRetriever $reference_retriever,
        private readonly HTMLURLBuilder $html_url_builder,
    ) {
    }

    public function getReferenceByPullRequestId($keyword, $pull_request_id): ?\Reference
    {
        return $this->pull_request_factory->getPullRequestById($pull_request_id)->match(
            function (PullRequest $pull_request) use ($keyword) {
                try {
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
                } catch (\GitRepoNotFoundException) {
                    return null;
                }
            },
            static fn() => null
        );
    }
}
