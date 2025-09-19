<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook;

use Git_Command_Exception;
use Git_Exec;
use GitRepository;
use PFUser;
use Psr\Log\LoggerInterface;

/**
 * Analyze a push a provide a high level object (PushDetails) that knows if push
 * is a branch creation or a tag deletion, etc.
 */
class LogAnalyzer
{
    public const string FAKE_EMPTY_COMMIT = '0000000000000000000000000000000000000000';

    public function __construct(private Git_Exec $exec_repo, private LoggerInterface $logger)
    {
    }

    /**
     * Behaviour extracted from official email hook prep_for_email() function
     * @throws \Git_Command_UnknownObjectTypeException
     */
    public function getPushDetails(
        GitRepository $repository,
        PFUser $user,
        string $oldrev,
        string $newrev,
        string $refname,
    ): PushDetails {
        $change_type   = PushDetails::ACTION_ERROR;
        $revision_list = [];
        $rev_type      = '';
        try {
            if ($oldrev == self::FAKE_EMPTY_COMMIT) {
                $revision_list = $this->exec_repo->revListSinceStart($refname, $newrev);
                $change_type   = PushDetails::ACTION_CREATE;
            } elseif ($newrev == self::FAKE_EMPTY_COMMIT) {
                $change_type = PushDetails::ACTION_DELETE;
            } else {
                $revision_list = $this->exec_repo->revListInChronologicalOrder($oldrev, $newrev);
                $change_type   = PushDetails::ACTION_UPDATE;
            }

            if ($change_type === PushDetails::ACTION_DELETE) {
                $rev_type = $this->exec_repo->getObjectType($oldrev);
            } else {
                $rev_type = $this->exec_repo->getObjectType($newrev);
            }
        } catch (Git_Command_Exception $exception) {
            $this->logger->error(
                self::class . " {$repository->getFullName()} $refname $oldrev $newrev " . $exception->getMessage()
            );
        }

        return new PushDetails(
            $repository,
            $user,
            $refname,
            $change_type,
            $rev_type,
            $revision_list
        );
    }
}
