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

namespace Tuleap\Git\CommitStatus;

class CommitStatusRetriever
{
    /**
     * @var CommitStatusDAO
     */
    private $dao;

    public function __construct(CommitStatusDAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return CommitStatus
     */
    public function getLastCommitStatus(\GitRepository $repository, $commit_reference)
    {
        return $this->getLastCommitStatuses($repository, [$commit_reference])[0];
    }

    /**
     * @return CommitStatus[]
     */
    public function getLastCommitStatuses(\GitRepository $repository, array $commit_references)
    {
        $statuses           = [];
        $commit_status_rows = $this->dao->getLastCommitStatusByRepositoryIdAndCommitReferences(
            $repository->getId(),
            $commit_references
        );
        $commit_status_rows_indexed_by_reference = [];
        foreach ($commit_status_rows as $commit_status_row) {
            $commit_status_rows_indexed_by_reference[$commit_status_row['commit_reference']] = $commit_status_row;
        }
        foreach ($commit_references as $commit_reference) {
            if (! isset($commit_status_rows_indexed_by_reference[$commit_reference])) {
                $statuses[] = new CommitStatusUnknown();
                continue;
            }
            $commit_status_row = $commit_status_rows_indexed_by_reference[$commit_reference];
            $date              = new \DateTimeImmutable('@' . $commit_status_row['date']);
            $statuses[]        = new CommitStatusWithKnownStatus($commit_status_row['status'], $date);
        }

        return $statuses;
    }
}
