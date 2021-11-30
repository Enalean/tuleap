<?php
/**
 * Copyright (c) Enalean SAS, 2011-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet
 *
 * ForgeUpgrade is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ForgeUpgrade is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ForgeUpgrade. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\ForgeUpgrade;

use PDO;
use PDOStatement;
use RuntimeException;

class ForgeUpgradeDb
{
    public const STATUS_ERROR   = 0;
    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILURE = 2;
    public const STATUS_SKIP    = 3;

    private PDO $dbh;

    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    /**
     * @psalm-param self::STATUS_* $status
     */
    public static function statusLabel(int $status): string
    {
        $labels = [
            self::STATUS_ERROR   => 'error',
            self::STATUS_SUCCESS => 'success',
            self::STATUS_FAILURE => 'failure',
            self::STATUS_SKIP    => 'skipped',
        ];
        return $labels[$status];
    }

    public function logStart(Bucket $bucket): void
    {
        $sth = $this->dbh->prepare('INSERT INTO forge_upgrade_bucket (script, start_date) VALUES (?, NOW())');
        if ($sth) {
            $sth->execute([$bucket->getPath()]);
            $bucket->setId($this->dbh->lastInsertId());
        }
    }

    /**
     * @psalm-param self::STATUS_* $status
     */
    public function logEnd(Bucket $bucket, int $status): bool
    {
        $sth = $this->dbh->prepare('UPDATE forge_upgrade_bucket SET status = ?, end_date = NOW() WHERE id = ?');
        if ($sth) {
            return $sth->execute([$status, $bucket->getId()]);
        }
        return false;
    }

    /**
     * @param bool|array $status
     * @return array|PDOStatement
     */
    public function getAllBuckets($status = false)
    {
        $stmt = '';
        if (is_array($status)) {
            $escapedStatus = array_map([$this->dbh, 'quote'], $status);
            $stmt          = ' WHERE status IN (' . implode(',', $escapedStatus) . ')';
        }
        $result = $this->dbh->query(
            'SELECT * , TIMEDIFF(end_date, start_date) AS execution_delay FROM forge_upgrade_bucket ' . $stmt . ' ORDER BY start_date ASC'
        );
        if ($result === false) {
            return [];
        }
        return $result;
    }

    public function getActivePlugins(): PDOStatement
    {
        $result = $this->dbh->query('SELECT name FROM plugin WHERE available = 1');
        if ($result === false) {
            throw new RuntimeException('Impossible to get the list of active plugins');
        }
        return $result;
    }
}
