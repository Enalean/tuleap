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

namespace Tuleap\Mediawiki\Maintenance;

use DataAccessObject;
use MediawikiDao;
use Logger;
use WrapperLogger;
use Log_NoopLogger;

class CleanUnusedDao extends DataAccessObject
{
    /**
     * @var string | false
     */
    private $central_database;

    /**
     * @var Logger
     */
    private $logger;

    private $db_deleted = 0;
    private $tables_deleted = 0;

    public function __construct(Logger $logger, $central_database)
    {
        parent::__construct();
        $this->enableExceptionsOnError();
        $this->central_database = $central_database;

        $this->logger = new Log_NoopLogger();
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = new WrapperLogger($logger, 'DB');
    }

    public function purge(array $row, $dry_run = true)
    {
        if ($this->isCentralDatabase($row['database_name'])) {
            $this->dropTablesInCentralDatabase($row['project_id'], $dry_run);
        } else {
            $this->dropDatabase($row['database_name'], $dry_run);
        }
        $this->dereferenceDatabase($row['project_id'], $dry_run);

        $this->logger->info("{$this->db_deleted} database(s) deleted");
        $this->logger->info("{$this->tables_deleted} table(s) deleted");
    }

    private function isCentralDatabase($database)
    {
        return $database === $this->central_database;
    }

    public function getDeletionCandidates()
    {
        $sql = "SELECT plugin_mediawiki_database.*
          FROM plugin_mediawiki_database
             JOIN groups ON (group_id = project_id)
          WHERE groups.status = 'D'";
        return $this->retrieve($sql);
    }

    public function getMediawikiDatabaseInUnusedServices()
    {
        $sql = "SELECT plugin_mediawiki_database.*
                FROM service
                  JOIN plugin_mediawiki_database ON (project_id = group_id)
                WHERE short_name = 'plugin_mediawiki'
                  AND is_used = 0";
        return $this->retrieve($sql);
    }

    private function dropTablesInCentralDatabase($project_id, $dry_run)
    {
        $this->logger->info("Attempt to purge tables in central database for $project_id");
        $project_id = (int) $project_id;
        if ($this->central_database && $project_id > 0) {
            foreach ($this->getTablesToDrop($project_id) as $row) {
                $fullname = $this->central_database.'.'.$row['name'];
                $sql = "DROP TABLE $fullname";
                $this->logger->info("$sql");
                if (! $dry_run) {
                    if ($this->update($sql)) {
                        $this->tables_deleted++;
                        $this->logger->info("$fullname dropped successfully");
                    } else {
                        $this->logger->error("DROP failed");
                        throw new \Exception("An error occured while dropping table $fullname: ".$this->da->getErrorMessage());
                    }
                } else {
                    $this->tables_deleted++;
                }
            }
        }
    }

    private function getTablesToDrop($project_id)
    {
        $central_db = $this->da->quoteSmart($this->central_database);
        $prefix     = $this->da->quoteLikeValueSuffix(MediawikiDao::DEDICATED_DATABASE_TABLE_PREFIX.'_'.$project_id);

        $sql = "SELECT TABLE_NAME as name
              FROM INFORMATION_SCHEMA.TABLES
              WHERE TABLE_SCHEMA = $central_db
                AND TABLE_NAME LIKE $prefix";

        return $this->retrieve($sql);
    }

    private function dropDatabase($database, $dry_run)
    {
        $this->logger->info("Attempt to purge database ".$database);
        if (strpos($database, MediawikiDao::DEDICATED_DATABASE_PREFIX) !== false) {
            if ($this->doesDatabaseExist($database)) {
                $sql = 'DROP DATABASE '.$database;
                $this->logger->info($sql);
                if (! $dry_run) {
                    if ($this->update($sql)) {
                        $this->db_deleted++;
                        $this->logger->info("DROP completed with success");
                    } else {
                        $this->logger->info("DROP failed");
                        throw new \Exception("DROP on $database failed: ".$this->da->getErrorMessage());
                    }
                } else {
                    $this->db_deleted++;
                }
            }
        }
        $this->logger->info("End of $database drop");
    }

    private function doesDatabaseExist($database)
    {
        $database = $this->da->quoteSmart($database);
        $sql = "SELECT SCHEMA_NAME AS 'name'
          FROM INFORMATION_SCHEMA.SCHEMATA
          WHERE SCHEMA_NAME = $database";
        return $this->retrieveCount($sql) !== 0;
    }

    private function dereferenceDatabase($project_id, $dry_run)
    {
        $this->logger->info("Remove project from plugin_mediawiki_database");
        $project_id = (int) $project_id;
        if (! $dry_run) {
            $sql = "DELETE FROM plugin_mediawiki_database WHERE project_id = $project_id";
            if ($this->update($sql)) {
                $this->logger->info("Database no longer referenced");
            } else {
                $this->logger->error("Delete failed");
                throw new \Exception("An error occured while de-referencing project $project_id: ".$this->da->getErrorMessage());
            }
        }
    }

    public function getDeletedDatabasesCount()
    {
        return $this->db_deleted;
    }

    public function getDeletedTablesCount()
    {
        return $this->tables_deleted;
    }
}
