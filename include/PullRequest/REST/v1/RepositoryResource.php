<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\Factory as PullRequestFactory;
use GitRepository;

class RepositoryResource {

    /** @var Tuleap\PullRequest\Dao */
    private $pull_request_dao;

    /** @var Tuleap\PullRequest\Factory */
    private $pull_request_factory;

    public function __construct() {
        $this->pull_request_dao     = new PullRequestDao();
        $this->pull_request_factory = new PullRequestFactory($this->pull_request_dao);
    }

    public function getPaginatedPullRequests(GitRepository $repository, $limit, $offset) {
        $result = $this->pull_request_dao->getPaginatedPullRequests($repository->getId(), $limit, $offset);

        $total_size = (int) $this->pull_request_dao->foundRows();
        $collection = array();

        foreach ($result as $row) {
            $pull_request                = $this->pull_request_factory->getInstanceFromRow($row);
            $pull_request_representation = new PullRequestRepresentation();
            $pull_request_representation->build($pull_request, $repository);
            $collection[]                = $pull_request_representation;
        }

        $representation = new RepositoryPullRequestRepresentation();
        $representation->build(
            $collection,
            $total_size
        );

        return $representation;
    }
}