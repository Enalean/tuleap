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
 */

namespace Tuleap\Git\CommitMetadata;

use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\GitPHP\Commit;

class CommitMetadataRetriever
{
    /**
     * @var CommitStatusRetriever
     */
    private $status_retriever;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(CommitStatusRetriever $status_retriever, \UserManager $user_manager)
    {
        $this->status_retriever = $status_retriever;
        $this->user_manager     = $user_manager;
    }

    public function getMetadataByRepositoryAndCommits(\GitRepository $repository, Commit ...$commits)
    {
        $commit_references = [];
        $author_emails     = [];
        foreach ($commits as $commit) {
            $commit_references[] = $commit->GetHash();
            $author_emails[]     = $commit->getAuthorEmail();
        }
        $statuses         = $this->status_retriever->getLastCommitStatuses($repository, $commit_references);
        $authors_by_email = $this->user_manager->getUserCollectionByEmails($author_emails);

        $metadata = [];

        $commit_metadata_iterator = new \MultipleIterator(\MultipleIterator::MIT_NEED_ALL);
        $commit_metadata_iterator->attachIterator(new \ArrayIterator($commits));
        $commit_metadata_iterator->attachIterator(new \ArrayIterator($statuses));

        foreach ($commit_metadata_iterator as list($commit, $status)) {
            $author_email = $commit->getAuthorEmail();
            $author       = $authors_by_email->getUserByEmail($author_email);
            $metadata[]   = new CommitMetadata($status, $author);
        }

        return $metadata;
    }
}
