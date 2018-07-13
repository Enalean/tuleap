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
 *
 */

namespace Tuleap\PullRequest;

use Tuleap\Git\Repository\AdditionalInformationRepresentationCache;
use Tuleap\Git\Repository\AdditionalInformationRepresentationRetriever;

class GitRestRouteAdditionalInformations
{

    /**
     * @var Dao
     */
    private $dao;

    private $repository_with_open_pr = [];

    public function __construct(Dao $dao)
    {
        $this->dao = $dao;
    }

    public function createCache(AdditionalInformationRepresentationCache $event)
    {
        foreach ($this->dao->searchRepositoriesWithOpenPullRequests($event->getRepositoryIds()) as $row) {
            $this->repository_with_open_pr[(int) $row['repository_id']] = true;
            $this->repository_with_open_pr[(int) $row['repo_dest_id']] = true;
        }
    }

    public function getOpenPullRequestsCount(AdditionalInformationRepresentationRetriever $event)
    {
        if (isset($this->repository_with_open_pr[(int) $event->getRepository()->getId()])) {
            $opened_pullrequest = $this->dao->searchNbOfOpenedPullRequestsForRepositoryId($event->getRepository()->getId());
            $event->addInformation("opened_pull_requests", $opened_pullrequest);
        }
    }
}
