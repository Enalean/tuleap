<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Psr\Log\LoggerInterface;
use WrapperLogger;
use ForgeConfig;

class AsynchronousSupervisor
{
    public const ACCEPTABLE_PROCESS_DELAY = 120;

    public const ONE_WEEK_IN_SECONDS = 604800;

    /**
     * @var ActionsRunnerDao
     */
    private $dao;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, ActionsRunnerDao $dao)
    {
        $this->logger = new WrapperLogger($logger, self::class);
        $this->dao    = $dao;
    }

    public function runSystemCheck()
    {
        if (ForgeConfig::get('sys_async_emails') !== false) {
            $this->warnWhenToMuchDelay();
            $this->purgeOldLogs();
        }
    }

    private function warnWhenToMuchDelay()
    {
        $last_end_date     = $this->dao->getLastEndDate();
        $nb_pending_events = $this->dao->searchPostCreationEventsAfter($last_end_date + self::ACCEPTABLE_PROCESS_DELAY);
        if ($nb_pending_events > 0) {
            $this->logger->warning('There are ' . $nb_pending_events . " post creation events waiting to be processed, you should check '/usr/share/tuleap/src/utils/worker.php' and it's log file to ensure it's still running.");
        }
    }

    private function purgeOldLogs()
    {
        $this->dao->deleteLogsOlderThan(self::ONE_WEEK_IN_SECONDS);
    }
}
