<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\Reference;

use GitRepository;
use Tuleap\Git\GitPHP\Commit;

class CommitDetailsRetriever
{
    /**
     * @var CommitDetailsCacheDao
     */
    private $dao;

    public function __construct(CommitDetailsCacheDao $dao)
    {
        $this->dao = $dao;
    }

    public function retrieveCommitDetails(GitRepository $repository, Commit $commit): ?CommitDetails
    {
        $commit_details_from_cache = $this->retrieveCommitDetailsFromDb($repository, $commit);

        return $commit_details_from_cache ?: $this->retrieveCommitDetailsFromCommit($commit, $repository);
    }

    private function retrieveCommitDetailsFromDb(GitRepository $repository, Commit $commit): ?CommitDetails
    {
        $row_cache = $this->dao->searchCommitDetails((int) $repository->getId(), $commit->GetHash());
        if (! $row_cache) {
            return null;
        }

        return new CommitDetails(
            $commit->GetHash(),
            $row_cache['title'],
            $row_cache['first_branch'],
            $row_cache['first_tag'],
            $row_cache['author_email'],
            $row_cache['author_name'],
            $row_cache['committer_epoch'],
        );
    }

    private function retrieveCommitDetailsFromCommit(Commit $commit, GitRepository $repository): ?CommitDetails
    {
        $branches     = $commit->GetHeads();
        $first_branch = empty($branches) ? '' : $branches[0]->GetName();

        $tags      = $commit->GetTags();
        $first_tag = empty($tags) ? '' : $tags[0]->GetName();

        $title = $commit->GetTitle();
        if ($title === null) {
            return null;
        }

        $author_email    = $commit->GetAuthorEmail();
        $author_name     = $commit->GetAuthorName();
        $author_epoch    = (int) $commit->GetAuthorEpoch();
        $committer_email = $commit->GetCommitterEmail();
        $committer_name  = $commit->GetCommitterName();
        $committer_epoch = (int) $commit->GetCommitterEpoch();

        $this->dao->saveCommitDetails(
            (int) $repository->getId(),
            $commit->GetHash(),
            $title,
            $author_email,
            $author_name,
            $author_epoch,
            $committer_email,
            $committer_name,
            $committer_epoch,
            $first_branch,
            $first_tag,
        );

        return new CommitDetails(
            $commit->GetHash(),
            $title,
            $first_branch,
            $first_tag,
            $author_email,
            $author_name,
            $committer_epoch,
        );
    }
}
