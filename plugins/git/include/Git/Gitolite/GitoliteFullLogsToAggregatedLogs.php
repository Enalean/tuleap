<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite;

use DateTime;
use DateInterval;
use Psr\Log\LoggerInterface;
use Exception;

class GitoliteFullLogsToAggregatedLogs extends \DataAccessObject
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->enableExceptionsOnError();
    }

    public function convert()
    {
        $start_timestamp = $this->getLastImportedTimestamp();
        do {
            $end_timestamp = $this->getEndDate($start_timestamp);
            $this->logger->info(sprintf("Import between %s and %s", date('c', $start_timestamp), date('c', $end_timestamp)));
            $start_timestamp = $this->updateBetween($start_timestamp, $end_timestamp);
            $this->logger->info('Done, wait for 1 sec');
            sleep(1);
        } while ($end_timestamp < time());
    }

    private function updateBetween($start_timestamp, $end_timestamp)
    {
        $start_timestamp = $this->da->escapeInt($start_timestamp);
        $end_timestamp   = $this->da->escapeInt($end_timestamp);

        $this->da->startTransaction();

        $sql = "INSERT INTO plugin_git_log_read_daily(repository_id, user_id, day, git_read, day_last_access_timestamp)
          SELECT repository_id, user_id, DATE_FORMAT(FROM_UNIXTIME(time), '%Y%m%d') as day, count(*), UNIX_TIMESTAMP(time) as day_last_access_timestamp
          FROM plugin_git_full_history
          WHERE time > $start_timestamp AND time <= $end_timestamp
          GROUP BY repository_id, user_id, day
          ON DUPLICATE KEY UPDATE git_read=git_read+VALUES(git_read)";
        $this->update($sql);

        $sql = "TRUNCATE TABLE plugin_git_full_history_checkpoint";
        $this->update($sql);

        $sql = "INSERT INTO plugin_git_full_history_checkpoint(last_timestamp)
                VALUES ($end_timestamp)";
        $this->update($sql);

        $this->da->commit();
        return $end_timestamp;
    }

    private function getLastImportedTimestamp()
    {
        $sql = 'SELECT last_timestamp FROM plugin_git_full_history_checkpoint';
        $dar = $this->retrieve($sql);
        if ($dar && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            return $row['last_timestamp'];
        }
        $sql = 'SELECT MIN(time) as time FROM plugin_git_full_history';
        $dar = $this->retrieve($sql);
        if ($dar && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            return $row['time'];
        }
        throw new Exception('Nothing to import');
    }

    private function getEndDate($start_timestamp)
    {
        $date_time = new DateTime("@$start_timestamp");
        $date_time->add(new DateInterval('P7D'));
        return $date_time->format('U');
    }
}
