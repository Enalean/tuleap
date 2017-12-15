<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\Notification;

use Logger;
use WrapperLogger;
use ForgeConfig;
use System_Command;

class AsynchronousSupervisor
{
    const ACCEPTABLE_PROCESS_DELAY = 120;

    const ONE_WEEK_IN_SECONDS = 604800;

    /**
     * @var NotifierDao
     */
    private $dao;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger, NotifierDao $dao)
    {
        $this->logger = new WrapperLogger($logger, __CLASS__);
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
        $last_end_date = $this->dao->getLastEndDate();
        $nb_pending_notifications = $this->dao->searchPendingNotificationsAfter($last_end_date + self::ACCEPTABLE_PROCESS_DELAY);
        if ($nb_pending_notifications > 0) {
            $this->logger->warn("There are ".$nb_pending_notifications." notifications pending, you should check '/usr/share/tuleap/plugins/tracker/bin/notify.php' and it's log file to ensure it's still running.");
        }
    }

    private function purgeOldLogs()
    {
        $this->dao->deleteLogsOlderThan(self::ONE_WEEK_IN_SECONDS);
    }

    public function runNotify()
    {
        $this->logger->debug("Check if backend notifier is running");
        if (ForgeConfig::get('sys_async_emails') !== false && ! $this->isRunning()) {
            $this->logger->info("Start backend notifier");
            try {
                $command = new System_Command();
                $command->exec('/usr/share/tuleap/plugins/tracker/bin/notify.php >/dev/null 2>/dev/null &');
            } catch (\Exception $exception) {
                $this->logger->error("Unable to launch backend notifier: ".$exception->getMessage());
            }
        }
    }

    private function isRunning()
    {
        if (file_exists(AsynchronousNotifier::PID_FILE_PATH)) {
            $pid = (int) trim(file_get_contents(AsynchronousNotifier::PID_FILE_PATH));
            $ret = posix_kill($pid, SIG_DFL);
            return $ret === true;
        }
    }
}
