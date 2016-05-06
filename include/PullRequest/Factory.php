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

namespace Tuleap\PullRequest;

use \GitRepository;
use \PFUser;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Exception\PullRequestNotCreatedException;

class Factory
{

    /**
     * @var PullRequest\Dao
     */
    private $dao;

    public function __construct(Dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return PullRequest
     */
    public function getPullRequestById($id)
    {
        $row = $this->dao->searchByPullRequestId($id)->getRow();
        if ($row === false) {
            throw new PullRequestNotFoundException();
        }

        return $this->getInstanceFromRow($row);
    }

    /**
     * @return PullRequest[]
     */
    public function getPullRequestsBySourceBranch(GitRepository $repository, $branch_name)
    {
        $res = $this->dao->searchBySourceBranch($repository->getId(), $branch_name);
        return $this->getInstancesFromRows($res);
    }

    public function countPullRequestOfRepository(GitRepository $repository)
    {
        $row = $this->dao->countPullRequestOfRepository($repository->getId())->getRow();

        return (int)$row['nb_pull_requests'];
    }

    /**
     * @return PullRequest
     */
    public function getInstanceFromRow(array $row)
    {
        return new PullRequest(
            $row['id'],
            $row['title'],
            $row['description'],
            $row['repository_id'],
            $row['user_id'],
            $row['creation_date'],
            $row['branch_src'],
            $row['sha1_src'],
            $row['branch_dest'],
            $row['sha1_dest'],
            $row['status']
        );
    }

    /**
     * @return PullRequest[]
     */
    public function getInstancesFromRows($rows)
    {
        $prs = array();

        if ($rows) {
            foreach ($rows as $row) {
                $prs[] = $this->getInstanceFromRow($row);
            }
        }

        return $prs;
    }

    /**
     * @return PullRequest
     */
    public function create(PullRequest $pull_request)
    {
        $new_pull_request_id = $this->dao->create(
            $pull_request->getRepositoryId(),
            $pull_request->getTitle(),
            $pull_request->getDescription(),
            $pull_request->getUserId(),
            $pull_request->getCreationDate(),
            $pull_request->getBranchSrc(),
            $pull_request->getSha1Src(),
            $pull_request->getBranchDest(),
            $pull_request->getSha1Dest()
        );

        if (! $new_pull_request_id) {
            throw new PullRequestNotCreatedException();
        }

        $pull_request->setId($new_pull_request_id);

        return $pull_request;
    }

    public function updateSourceRev(PullRequest $pull_request, $new_rev)
    {
        return $this->dao->updateSha1Src($pull_request->getId(), $new_rev);
    }

    public function updateDestRev(PullRequest $pull_request, $new_rev)
    {
        return $this->dao->updateSha1Dest($pull_request->getId(), $new_rev);
    }
}
