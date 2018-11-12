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

namespace GitPHP\Shortlog;

use Tuleap\Git\CommitMetadata\CommitMetadataRetriever;
use Tuleap\Git\GitPHP\Commit;

class ShortlogPresenterBuilder
{
    /**
     * @var CommitMetadataRetriever
     */
    private $commit_metadata_retriever;

    public function __construct(CommitMetadataRetriever $commit_metadata_retriever)
    {
        $this->commit_metadata_retriever = $commit_metadata_retriever;
    }

    /**
     * @return ShortlogPresenter
     */
    public function getShortlogPresenter(\GitRepository $repository, Commit ...$commits)
    {
        $metadata = $this->commit_metadata_retriever->getMetadataByRepositoryAndCommits($repository, ...$commits);

        $commit_presenter_iterator = new \MultipleIterator(\MultipleIterator::MIT_NEED_ALL);
        $commit_presenter_iterator->attachIterator(new \ArrayIterator($commits));
        $commit_presenter_iterator->attachIterator(new \ArrayIterator($metadata));

        $commit_presenters = [];

        foreach ($commit_presenter_iterator as list($commit, $metadata)) {
            $commit_presenter    = new ShortlogCommitPresenter($commit, $metadata);
            $commit_presenters[] = $commit_presenter;
        }

        return new ShortlogPresenter($commit_presenters);
    }
}
