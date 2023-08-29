<?php
/**
 * Copyright (c) Enalean, 2012-Present. All rights reserved
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

use Tuleap\SystemEvent\RootDailyStartEvent;

class SystemEvent_ROOT_DAILY extends SystemEvent // phpcs:ignore
{
    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link)
    {
        return '-';
    }

    /**
     * Process stored event
     */
    public function process()
    {
        $logger = BackendLogger::getDefaultLogger();
        $logger->info(self::class . ' Start');

        $warnings = [];

        // Purge system_event table: we only keep one year history in db
        $this->purgeSystemEventsDataOlderThanOneYear();

        $this->runComputeAllDailyStats();
        $current_time = new DateTimeImmutable();
        $this->cleanupDB($current_time);

        try {
            $frs_directory_cleaner = new \Tuleap\FRS\FRSIncomingDirectoryCleaner();
            $frs_directory_cleaner->run();
        } catch (Exception $exception) {
            $warnings[] = $exception->getMessage();
        }

        try {
            $root_daily_event = $this->getEventManager()->dispatch(new RootDailyStartEvent($logger));
            $warnings         = array_merge($warnings, $root_daily_event->getWarnings());

            if (count($warnings) > 0) {
                $this->warning(implode(PHP_EOL, $warnings));
            } else {
                $this->done();
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $logger->info(self::class . ' Completed');
        return true;
    }

    private function purgeSystemEventsDataOlderThanOneYear()
    {
        $dao                 = new SystemEventDao();
        $system_event_purger = new SystemEventPurger($dao);

        return $system_event_purger->purgeSystemEventsDataOlderThanOneYear();
    }

    private function runComputeAllDailyStats(): void
    {
        (new \Tuleap\FRS\FRSMetricsDAO())->executeDailyRun(new DateTimeImmutable());
    }

    private function cleanupDB(DateTimeImmutable $current_time): void
    {
        (new SessionDao())->deleteExpiredSession($current_time->getTimestamp(), \ForgeConfig::getInt('sys_session_lifetime'));
        (new UserDao())->updatePendingExpiredUsersToDeleted($current_time->getTimestamp(), 3600 * 24 * \ForgeConfig::getInt('sys_pending_account_lifetime'));
    }
}
