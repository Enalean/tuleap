<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use GitRepository;
use PFUser;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Exception\PullRequestNotCreatedException;
use pullrequestPlugin;
use ReferenceManager;

class Factory
{
    public function __construct(private readonly Dao $dao, private readonly ReferenceManager $reference_manager)
    {
    }

    /**
     * @return PullRequest
     * @throws PullRequestNotFoundException
     */
    public function getPullRequestById($id)
    {
        $row = $this->dao->searchByPullRequestId($id);
        if (empty($row)) {
            throw new PullRequestNotFoundException();
        }

        return $this->getInstanceFromRow($row);
    }

    /**
     * @return PullRequest[]
     */
    public function getOpenedBySourceBranch(GitRepository $repository, $branch_name)
    {
        $res = $this->dao->searchOpenedBySourceBranch($repository->getId(), $branch_name);
        return $this->getInstancesFromRows($res);
    }

    public function getOpenedByDestinationBranch(GitRepository $dest_repository, $branch_name)
    {
        $res = $this->dao->searchOpenedByDestinationBranch($dest_repository->getId(), $branch_name);
        return $this->getInstancesFromRows($res);
    }

    public function getPullRequestCount(GitRepository $repository)
    {
        $nb_open   = 0;
        $nb_closed = 0;
        foreach ($this->dao->searchNbOfPullRequestsByStatusForRepositoryId($repository->getId()) as $row) {
            switch ($row['status']) {
                case PullRequest::STATUS_ABANDONED:
                case PullRequest::STATUS_MERGED:
                    $nb_closed += $row['nb'];
                    break;
                default:
                    $nb_open += $row['nb'];
            }
        }

        return new PullRequestCount($nb_open, $nb_closed);
    }

    public function hasOpenBrokenPullRequests(GitRepository $repository): bool
    {
        return $this->dao->hasRepositoryOpenPullRequestsWithBrokenGitReferences($repository->getId());
    }

    public function getInstanceFromRow(array $row): PullRequest
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
            $row['repo_dest_id'],
            $row['branch_dest'],
            $row['sha1_dest'],
            $row["description_format"],
            $row['status'],
            $row['merge_status'],
        );
    }

    /**
     * @return PullRequest[]
     */
    public function getInstancesFromRows($rows)
    {
        $prs = [];

        if ($rows) {
            foreach ($rows as $row) {
                $prs[] = $this->getInstanceFromRow($row);
            }
        }

        return $prs;
    }

    public function create(PFUser $user, PullRequest $pull_request, int $project_id): PullRequest
    {
        try {
            $new_pull_request_id = $this->dao->create(
                $pull_request->getRepositoryId(),
                $pull_request->getTitle(),
                $pull_request->getDescription(),
                $pull_request->getUserId(),
                $pull_request->getCreationDate(),
                $pull_request->getBranchSrc(),
                $pull_request->getSha1Src(),
                $pull_request->getRepoDestId(),
                $pull_request->getBranchDest(),
                $pull_request->getSha1Dest(),
                $pull_request->getMergeStatus(),
                $pull_request->getDescriptionFormat()
            );
        } catch (\Exception $ex) {
            throw new PullRequestNotCreatedException();
        }

        $pull_request = $pull_request->createWithNewID($new_pull_request_id);

        $this->reference_manager->extractCrossRef(
            $pull_request->getTitle(),
            $new_pull_request_id,
            pullrequestPlugin::REFERENCE_NATURE,
            $project_id,
            $user->getId(),
            pullrequestPlugin::PULLREQUEST_REFERENCE_KEYWORD
        );

        $this->reference_manager->extractCrossRef(
            $pull_request->getDescription(),
            $new_pull_request_id,
            pullrequestPlugin::REFERENCE_NATURE,
            $project_id,
            $user->getId(),
            pullrequestPlugin::PULLREQUEST_REFERENCE_KEYWORD
        );

        return $pull_request;
    }

    public function updateSourceRev(PullRequest $pull_request, $new_rev)
    {
        $this->dao->updateSha1Src($pull_request->getId(), $new_rev);
    }

    public function updateDestRev(PullRequest $pull_request, $new_rev)
    {
        $this->dao->updateSha1Dest($pull_request->getId(), $new_rev);
    }

    public function updateMergeStatus(PullRequest $pull_request, $merge_status)
    {
        $this->dao->updateMergeStatus($pull_request->getId(), $merge_status);
    }

    public function updateTitle(PFUser $user, PullRequest $pull_request, int $project_id, string $new_title): void
    {
        $pull_request_id = $pull_request->getId();

        $this->dao->updateTitle($pull_request_id, $new_title);

        $this->reference_manager->extractCrossRef(
            $new_title,
            $pull_request_id,
            pullrequestPlugin::REFERENCE_NATURE,
            $project_id,
            $user->getId(),
            pullrequestPlugin::PULLREQUEST_REFERENCE_KEYWORD
        );
    }

    public function updateDescription(PFUser $user, PullRequest $pull_request, int $project_id, string $new_description, string $format): void
    {
        $pull_request_id = $pull_request->getId();

        $this->dao->updateDescription($pull_request_id, $new_description, $format);

        $this->reference_manager->extractCrossRef(
            $new_description,
            $pull_request_id,
            pullrequestPlugin::REFERENCE_NATURE,
            $project_id,
            $user->getId(),
            pullrequestPlugin::PULLREQUEST_REFERENCE_KEYWORD
        );
    }
}
