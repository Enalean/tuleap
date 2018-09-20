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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\REST\v1;

use Git_GitRepositoryUrlManager;
use GitRepository;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\GitPHP\Commit;

class GitCommitRepresentationBuilder
{
    /**
     * @var CommitStatusRetriever
     */
    private $status_retriever;
    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $url_manager;

    public function __construct(CommitStatusRetriever $status_retriever, Git_GitRepositoryUrlManager $url_manager)
    {
        $this->status_retriever = $status_retriever;
        $this->url_manager      = $url_manager;
    }

    public function build(GitRepository $repository, Commit $commit)
    {
        $commit_status = $this->status_retriever->getLastCommitStatus($repository, $commit->GetHash());

        $repository_path = $this->url_manager->getRepositoryBaseUrl($repository);

        $commit_representation = new GitCommitRepresentation();
        $commit_representation->build($repository_path, $commit, $commit_status);

        return $commit_representation;
    }
}
