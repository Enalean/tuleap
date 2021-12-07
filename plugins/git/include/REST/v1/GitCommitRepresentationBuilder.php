<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\REST\v1;

use Git_GitRepositoryUrlManager;
use GitRepository;
use Tuleap\Git\CommitMetadata\CommitMetadata;
use Tuleap\Git\CommitMetadata\CommitMetadataRetriever;
use Tuleap\Git\CommitStatus\CommitStatusUnknown;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\User\REST\MinimalUserRepresentation;

class GitCommitRepresentationBuilder
{
    /**
     * @var CommitMetadataRetriever
     */
    private $metadata_retriever;
    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $url_manager;

    public function __construct(CommitMetadataRetriever $metadata_retriever, Git_GitRepositoryUrlManager $url_manager)
    {
        $this->metadata_retriever = $metadata_retriever;
        $this->url_manager        = $url_manager;
    }

    public function buildCollection(GitRepository $repository, Commit ...$commits): GitCommitRepresentationCollection
    {
        $metadata = $this->metadata_retriever->getMetadataByRepositoryAndCommits($repository, ...$commits);

        $commit_representation_iterator = new \MultipleIterator(\MultipleIterator::MIT_NEED_ALL);
        $commit_representation_iterator->attachIterator(new \ArrayIterator($commits));
        $commit_representation_iterator->attachIterator(new \ArrayIterator($metadata));

        $representations = [];

        foreach ($commit_representation_iterator as list($commit, $metadata)) {
            $message = implode("\n", $commit->GetComment());

            $verification = new GitCommitVerificationRepresentation();
            $verification->build($commit->getSignature());

            $commit_representation = $this->buildGitCommitRepresentation(
                $commit,
                $message,
                $verification,
                $metadata,
                $repository
            );
            $representations[]     = $commit_representation;
        }

        return new GitCommitRepresentationCollection(...$representations);
    }

    public function build(GitRepository $repository, Commit $commit): GitCommitRepresentation
    {
        $metadata = $this->metadata_retriever->getMetadataByRepositoryAndCommits($repository, $commit);

        $message = implode("\n", $commit->GetComment());

        $verification = new GitCommitVerificationRepresentation();
        $verification->build($commit->getSignature());

        return $this->buildGitCommitRepresentation(
            $commit,
            $message,
            $verification,
            $metadata[0],
            $repository
        );
    }

    private function buildGitCommitRepresentation(
        Commit $commit,
        string $message,
        GitCommitVerificationRepresentation $verification,
        CommitMetadata $metadata,
        GitRepository $repository,
    ): GitCommitRepresentation {
        return new GitCommitRepresentation(
            $commit->GetHash(),
            (string) $commit->GetTitle(),
            $message,
            $commit->GetAuthorName(),
            $commit->getAuthorEmail(),
            $commit->GetAuthorEpoch(),
            $commit->GetCommitterEpoch(),
            $verification,
            $this->buildAuthorMetadata($metadata),
            $this->buildCommitStatusMetadata($metadata),
            $this->url_manager->getRepositoryBaseUrl($repository)
        );
    }

    private function buildAuthorMetadata(CommitMetadata $metadata): ?MinimalUserRepresentation
    {
        $author = $metadata->getAuthor();

        if ($author !== null) {
            $author_representation = MinimalUserRepresentation::build($author);
            $author                = $author_representation;
        }
        return $author;
    }

    private function buildCommitStatusMetadata(CommitMetadata $metadata): ?GitCommitStatusRepresentation
    {
        $commit_status          = null;
        $metadata_commit_status = $metadata->getCommitStatus();
        if ($metadata_commit_status->getStatusName() !== CommitStatusUnknown::NAME) {
            $commit_status = new GitCommitStatusRepresentation();
            $commit_status->build($metadata_commit_status);
        }
        return $commit_status;
    }
}
