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

use Symfony\Component\Process\Process;
use Tuleap\SystemEvent\RootDailyStartEvent;

class SystemEvent_ROOT_DAILY extends SystemEvent // phpcs:ignore
{
    private const DAY_OF_WEEKLY_STATS = 'Monday';

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

        // Re-dumping ssh keys should be done only once a day as:
        // - It's I/O intensive
        // - It's stress gitolite backend
        // - SSH keys should already be dumped via EDIT_SSH_KEY event
        $backend_system = Backend::instance('System');
        assert($backend_system instanceof BackendSystem);
        $backend_system->dumpSSHKeys();

        // User home sanity check should be done only once a day as
        // it is slooow (due to libnss-mysql)
        $this->userHomeSanityCheck($backend_system);

        // Purge system_event table: we only keep one year history in db
        $this->purgeSystemEventsDataOlderThanOneYear();

        $warnings = array();

        $this->runComputeAllDailyStats($logger, $warnings);

        $this->runWeeklyStats($logger, $warnings);

        try {
            $frs_directory_cleaner = new \Tuleap\FRS\FRSIncomingDirectoryCleaner();
            $frs_directory_cleaner->run();
        } catch (Exception $exception) {
            $warnings[] = $exception->getMessage();
        }

        try {
            $root_daily_event = $this->getEventManager()->dispatch(new RootDailyStartEvent($logger));
            assert($root_daily_event instanceof RootDailyStartEvent);
            $warnings = array_merge($warnings, $root_daily_event->getWarnings());

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

    private function userHomeSanityCheck(BackendSystem $backend_system)
    {
        $dao   = new UserDao();
        $users = $dao
            ->searchByStatus(array(PFUser::STATUS_ACTIVE, PFUser::STATUS_RESTRICTED))
            ->instanciateWith(array(UserManager::instance(), 'getUserInstanceFromRow'));

        foreach ($users as $user) {
            $backend_system->userHomeSanityCheck($user);
        }
    }

    private function purgeSystemEventsDataOlderThanOneYear()
    {
        $dao                 = new SystemEventDao();
        $system_event_purger = new SystemEventPurger($dao);

        return $system_event_purger->purgeSystemEventsDataOlderThanOneYear();
    }

    private function runComputeAllDailyStats(\Psr\Log\LoggerInterface $logger, array &$warnings)
    {
        $process = new Process([__DIR__ . '/../../../utils/compute_all_daily_stats.sh']);
        $this->runCommand($process, $logger, $warnings);
    }

    /**
     * run the weekly stats for projects. Run it on Monday morning so that
     * it computes the stats for the week before
     */
    private function runWeeklyStats(\Psr\Log\LoggerInterface $logger, array &$warnings)
    {
        $now = new DateTimeImmutable();
        if ($now->format('l') === self::DAY_OF_WEEKLY_STATS) {
            $process = new Process(['./db_project_weekly_metric.pl'], __DIR__ . '/../../../utils/underworld-root');
            $this->runCommand($process, $logger, $warnings);
        }
    }

    private function runCommand(Process $process, \Psr\Log\LoggerInterface $logger, array &$warnings): void
    {
        $process->setTimeout(null);
        $process->run();
        if (! $process->isSuccessful()) {
            $warnings[] = $process->getCommandLine() . ' ran with errors, check ' . ForgeConfig::get('codendi_log');
            $logger->error(sprintf("%s %s errors. Stdout:\n%s\nStderr:\n%s", self::class, $process->getCommandLine(), $process->getOutput(), $process->getErrorOutput()));
        } else {
            $logger->debug(sprintf("%s %s Stdout:\n%s\nStderr:\n%s", self::class, $process->getCommandLine(), $process->getOutput(), $process->getErrorOutput()));
        }
    }
}
