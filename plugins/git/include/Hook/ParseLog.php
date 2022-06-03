<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
use Psr\Log\LoggerInterface;

class ParseLog
{
    public function __construct(
        private LogPushes $log_pushes,
        private CrossReferencesExtractor $extract_cross_ref,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(PushDetails $push_details): void
    {
        $this->log_pushes->executeForRepository($push_details);

        if ($push_details->getRefnameType() === PushDetails::TYPE_ANNOTATED_TAG) {
            try {
                $this->extract_cross_ref->extractTagReference($push_details);
            } catch (Git_Command_Exception $exception) {
                $this->logger->error(
                    self::class . ": cannot extract references for {$push_details->getRepository()->getFullPath()} {$push_details->getRefname()} tag: $exception",
                    ['exception' => $exception]
                );
            }
        }

        foreach ($push_details->getRevisionList() as $commit) {
            try {
                $this->extract_cross_ref->extractCommitReference($push_details, $commit);
            } catch (Git_Command_Exception $exception) {
                $this->logger->error(
                    self::class . ": cannot extract references for {$push_details->getRepository()->getFullPath()} {$push_details->getRefname()} $commit: $exception",
                    ['exception' => $exception]
                );
            }
        }
    }
}
