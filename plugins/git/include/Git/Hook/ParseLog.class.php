<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Git_Hook_ParseLog
{
    /** @var Git_Hook_ExtractCrossReferences */
    private $extract_cross_ref;

    /** @var Git_Hook_LogPushes */
    private $log_pushes;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(Git_Hook_LogPushes $log_pushes, Git_Hook_ExtractCrossReferences $extract_cross_ref, \Psr\Log\LoggerInterface $logger)
    {
        $this->log_pushes        = $log_pushes;
        $this->extract_cross_ref = $extract_cross_ref;
        $this->logger            = $logger;
    }

    public function execute(Git_Hook_PushDetails $push_details)
    {
        $this->log_pushes->executeForRepository($push_details);

        foreach ($push_details->getRevisionList() as $commit) {
            try {
                $this->extract_cross_ref->execute($push_details, $commit);
            } catch (Git_Command_Exception $exception) {
                $this->logger->error(self::class . ": cannot extract references for {$push_details->getRepository()->getFullPath()} {$push_details->getRefname()} $commit: $exception");
            }
        }
    }
}
