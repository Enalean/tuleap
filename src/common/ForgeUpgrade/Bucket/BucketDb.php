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

namespace Tuleap\ForgeUpgrade\Bucket;

use PDO;
use Psr\Log\LoggerInterface;

/**
 * Wrap access to the DB and provide a set of convenient tools to write
 * DB upgrades
 */
class BucketDb
{
    public PDO $dbh;

    protected LoggerInterface $log;

    public function __construct(PDO $dbh, LoggerInterface $logger)
    {
        $this->dbh = $dbh;
        $this->log = $logger;
    }

    /**
     * Return true if the given table name already exists into the database
     */
    public function tableNameExists(string $tableName): bool
    {
        $sql = 'SHOW TABLES LIKE ' . $this->dbh->quote($tableName);
        $res = $this->dbh->query($sql);

        return $res && $res->fetch() !== false;
    }

    /**
     * Return true if the given column name already exists into the database
     */
    public function columnNameExists(string $tableName, string $columnName): bool
    {
        $sql = "SHOW COLUMNS FROM `$tableName` LIKE " . $this->dbh->quote($columnName);

        $res = $this->dbh->query($sql);

        return $res && $res->fetch() !== false;
    }

    /**
     * Return true if the given index name on the table already exists into the database
     */
    public function indexNameExists(string $tableName, string $index): bool
    {
        $sql = "SHOW INDEX FROM `$tableName` WHERE Key_name LIKE " . $this->dbh->quote($index);

        $res = $this->dbh->query($sql);

        return $res && $res->fetch() !== false;
    }

    /**
     * Return true if a primary key already exists on this table
     */
    public function primaryKeyExists(string $tableName): bool
    {
        $sql = "SHOW INDEXES FROM `$tableName` WHERE Key_name = 'PRIMARY'";

        $res = $this->dbh->query($sql);

        return $res && $res->fetch() !== false;
    }

    /**
     * Create new table if not already exists and report errors.
     *
     * @throws BucketDbException
     */
    public function createTable(string $tableName, string $sql): void
    {
        $this->log->info('Add table ' . $tableName);
        if (! $this->tableNameExists($tableName)) {
            $res = $this->dbh->exec($sql);
            if ($res === false) {
                $info = $this->dbh->errorInfo();
                $msg  = 'An error occured adding table ' . $tableName . ': ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new BucketDbException($msg);
            }
            $this->log->info($tableName . ' successfully added');
        } else {
            $this->log->info($tableName . ' already exists');
        }
    }

    /**
     * Delete table if exists and report errors.
     *
     * @throws BucketDbException
     */
    public function dropTable(string $tableName, string $sql = ''): void
    {
        $this->log->info('Delete table ' . $tableName);
        if ($this->tableNameExists($tableName)) {
            if (! $sql) {
                $sql = 'DROP TABLE `' . $tableName . '`';
            }
            $res = $this->dbh->exec($sql);
            if ($res === false) {
                $info = $this->dbh->errorInfo();
                $msg  = 'An error occured deleting table ' . $tableName . ': ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new BucketDbException($msg);
            }
            $this->log->info($tableName . ' successfully deleted');
        } else {
            $this->log->info($tableName . ' not exists');
        }
    }

    /**
     * Alter table to add index and report errors.
     *
     * @throws BucketDbException
     */
    public function addIndex(string $tableName, string $index, string $sql): void
    {
        $this->log->info('Add index ' . $tableName);
        if (! $this->indexNameExists($tableName, $index)) {
            $res = $this->dbh->exec($sql);
            if ($res === false) {
                $info = $this->dbh->errorInfo();
                $msg  = 'An error occurred adding index to ' . $tableName . ': ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new BucketDbException($msg);
            }
            $this->log->info($index . ' successfully added index');
        } else {
            $this->log->info($index . ' already exists');
        }
    }

    /**
     * Return true if given table has a given property
     * @deprecated
     */
    public function propertyExists(string $tableName, string $schema, string $property): bool
    {
        $sql = 'SELECT table_name FROM ' . $schema . ' WHERE ' . $property . ' AND table_name LIKE ' . $this->dbh->quote(
            $tableName
        );

        $res = $this->dbh->query($sql);

        return $res && $res->fetch() !== false;
    }

    /**
     * Alter table to modify field value and report errors.
     *
     * @throws BucketDbException
     *
     * @deprecated
     */
    public function alterTable(string $tableName, string $schema, string $property, string $sql): void
    {
        $this->log->info('Alter table ' . $tableName);
        $this->dbh->exec($sql);
        $this->log->info($tableName . ' successfully altered table');
    }

    /**
     * Alter table to add primary key and report errors.
     *
     * @throws BucketDbException
     */
    public function addPrimaryKey(string $tableName, string $primaryKey, string $sql): void
    {
        $this->log->info('Add primary key ' . $tableName);
        if (! $this->primaryKeyExists($tableName)) {
            $res = $this->dbh->exec($sql);
            if ($res === false) {
                $info = $this->dbh->errorInfo();
                $msg  = 'An error occured adding primary key to ' . $tableName . ': ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new BucketDbException($msg);
            }
            $this->log->info($primaryKey . ' successfully added pk');
        } else {
            $this->log->info($primaryKey . ' pk already exists');
        }
    }
}
