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

class CommitStatusCreator
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
     * @throws CommitDoesNotExistException
     * @throws InvalidCommitReferenceException
     */
    public function createCommitStatus(
        \GitRepository $git_repository,
        \Git_Exec $git_executor,
        $commit_reference,
        $status_name
    ) {
        if (! $git_executor->doesObjectExists($commit_reference)) {
            throw new CommitDoesNotExistException($commit_reference);
        }
        if ($git_executor->getObjectType($commit_reference) !== 'commit') {
            throw new InvalidCommitReferenceException("$commit_reference does not reference a commit");
        }

        $commit_status = CommitStatusWithKnownStatus::buildFromStatusName($status_name, new \DateTimeImmutable());

        $this->dao->create(
            $git_repository->getId(),
            $commit_reference,
            $commit_status->getStatusId(),
            $commit_status->getDate()->getTimestamp()
        );
    }
}
