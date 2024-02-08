<?php
/**
 * Copyright (c) Enalean, 2016-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Git\Gitolite;

use DateTime;
use Git;
use GitRepositoryFactory;
use GitRepositoryGitoliteAdmin;
use Psr\Log\LoggerInterface;
use PFUser;
use Tuleap\Git\History\Dao;
use Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator;
use UserDao;
use UserManager;
use GitRepository;

class Gitolite3LogParser
{
    public const REPOSITORY_PATH                       = 'gitolite/repositories/';
    public const GIT_COMMAND                           = 'pre_git';
    public const FILE_NAME                             = 'gitolite-';
    public const FILE_EXTENSION                        = '.log';
    public const EXPECTED_NUMBER_OF_FIELDS_IN_LOG_LINE = 8;

    private const ACCESS_COUNT_CACHE_KEY              = 'access_count';
    private const DAY_LAST_ACCESS_TIMESTAMP_CACHE_KEY = 'day_last_access_timestamp';


    /**
     * @var array
     */
    private $access_cache = [];

    /**
     * @var array
     */
    private $user_last_access_cache = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpUserValidator $user_validator,
        private readonly Dao $history_dao,
        private readonly GitRepositoryFactory $repository_factory,
        private readonly UserManager $user_manager,
        private readonly GitoliteFileLogsDao $file_logs_dao,
        private readonly UserDao $user_dao,
    ) {
    }

    public function parseCurrentAndPreviousMonthLogs($path)
    {
        $last_month_file    = self::FILE_NAME . date("Y-m", strtotime("-1 month")) . self::FILE_EXTENSION;
        $current_month_file = self::FILE_NAME . date('Y-m') . self::FILE_EXTENSION;

        $this->parseLogs($path . $last_month_file);
        $this->parseLogs($path . $current_month_file);
    }

    public function parseLogs($log)
    {
        if (file_exists($log)) {
            $log_file = fopen("$log", "r");
            if (! $log_file) {
                $this->logger->error('Cannot open ' . $log_file);
                throw new CannotAccessToGitoliteLogException();
            } else {
                $last_read_char = $this->file_logs_dao->getLastReadLine($log);
                if (! $last_read_char) {
                    $this->logger->info('Start import of new file: ' . $log);
                    $last_read_char = ['end_line' => 0];
                } else {
                    $this->logger->info('Import file: ' . $log . ' from last position');
                }
                fseek($log_file, $last_read_char['end_line']);
                while (! feof($log_file)) {
                    $log_line = fgetcsv($log_file, 0, "\t");
                    if ($log_line !== false) {
                        $this->parseLine($log_line, $log);
                    }
                }

                $this->storeCacheInDb();
                $this->file_logs_dao->storeLastLine($log, ftell($log_file));
                $this->updateLastAccessDates();
                fclose($log_file);
            }
        }
    }

    private function parseLine(array $line, $filename)
    {
        if (
            count($line) === self::EXPECTED_NUMBER_OF_FIELDS_IN_LOG_LINE &&
                $this->isAReadAccess($line) && $this->isNotASystemUser($line[4])
        ) {
            $this->logger->debug(
                'File ' . $filename . '. Add one Read access for repository ' . $line[3] . ' pattern ' . $line[7] . ' for user ' . $line[4]
            );

            $repository = $this->repository_factory->getFromFullPath(
                self::REPOSITORY_PATH . $line[3] . '.git'
            );

            if (! $repository) {
                $this->logger->warning(
                    "Git repository $line[3] seems deleted. Skipping."
                );

                return;
            }

            $user = $this->user_manager->getUserByUserName($line[4]);
            $day  = DateTime::createFromFormat('Y-m-d.H:i:s', $line[0]);
            if ($day === false) {
                $this->logger->debug('Not able to parse the date ' . $line[0]);
                return;
            }

            if ($user) {
                $user_id = $user->getId();
                $this->cacheUserLastAccessDate($user, $day);
            } else {
                $user_id = 0;
            }

            $this->cacheAccess($repository, $user_id, $day);
        }
    }

    private function resetCaches()
    {
        $this->access_cache = [];
    }

    private function cacheAccess(GitRepository $repository, $user_id, DateTime $day)
    {
        $day_key = $day->format('Ymd');
        if (! isset($this->access_cache[$day_key][$repository->getId()][$user_id])) {
            $this->access_cache[$day_key][$repository->getId()][$user_id] = [self::ACCESS_COUNT_CACHE_KEY => 0];
        }
        $this->access_cache[$day_key][$repository->getId()][$user_id][self::ACCESS_COUNT_CACHE_KEY]++;
        $this->access_cache[$day_key][$repository->getId()][$user_id][self::DAY_LAST_ACCESS_TIMESTAMP_CACHE_KEY] = $day->getTimestamp();
    }

    private function storeCacheInDb()
    {
        $this->history_dao->startTransaction();
        foreach ($this->access_cache as $day => $repositories) {
            foreach ($repositories as $repository_id => $users) {
                foreach ($users as $user_id => $user_access) {
                    $read_access_count = $user_access[self::ACCESS_COUNT_CACHE_KEY];
                    $day_timestamp     = $user_access[self::DAY_LAST_ACCESS_TIMESTAMP_CACHE_KEY];
                    $this->history_dao->addGitReadAccess($day, $repository_id, $user_id, $read_access_count, $day_timestamp);
                }
            }
        }
        $this->history_dao->commit();
        $this->resetCaches();
    }

    private function isAReadAccess(array $line)
    {
        return $line[2] === self::GIT_COMMAND && $line[5] === Git::READ_PERM;
    }

    private function isNotASystemUser($user)
    {
        return $user !== GitRepositoryGitoliteAdmin::USERNAME && ! $this->user_validator->isLoginAnHTTPUserLogin($user);
    }

    private function cacheUserLastAccessDate(PFUser $user, DateTime $date)
    {
        $user_id   = $user->getId();
        $timestamp = $date->getTimestamp();

        if (isset($this->user_last_access_cache[$user_id]) && $this->user_last_access_cache[$user_id] >= $timestamp) {
            return;
        }

        $this->user_last_access_cache[$user_id] = $timestamp;
    }

    private function updateLastAccessDates()
    {
        foreach ($this->user_last_access_cache as $user_id => $timestamp) {
            $this->user_dao->storeLastAccessDate($user_id, $timestamp);
        }
    }
}
