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
use Psr\Log\LoggerInterface;
use WrapperLogger;
use Psr\Log\NullLogger;
use ForgeConfig;

class CleanUnusedDao extends DataAccessObject
{
    /**
     * @var string | false
     */
    private $central_database;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $db_deleted = 0;
    private $tables_deleted = 0;

    public function __construct(LoggerInterface $logger, $central_database)
    {
        parent::__construct();
        $this->enableExceptionsOnError();
        $this->central_database = $central_database;

        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger)
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
        return $this->getServicesQuery(0);
    }

    public function getMediawikiDatabasesInUsedServices(?int $limit = null)
    {
        return $this->getServicesQuery(1, $limit);
    }

    private function getServicesQuery($is_used, ?int $limit = null)
    {
        $is_used = $this->da->escapeInt($is_used);
        $sql = "SELECT plugin_mediawiki_database.*
                FROM service
                  JOIN plugin_mediawiki_database ON (project_id = group_id)
                WHERE short_name = 'plugin_mediawiki'
                AND is_used = $is_used";

        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }

        return $this->retrieve($sql);
    }

    private function dropTablesInCentralDatabase($project_id, $dry_run)
    {
        $this->logger->info("Attempt to purge tables in central database for $project_id");
        $project_id = (int) $project_id;
        if ($this->central_database && $project_id > 0) {
            foreach ($this->getTablesToDrop($project_id) as $row) {
                $fullname = $this->central_database . '.' . $row['name'];
                $sql = "DROP TABLE $fullname";
                $this->logger->info("$sql");
                if (! $dry_run) {
                    $this->update($sql);
                    $this->logger->info("$fullname dropped successfully");
                }
                $this->tables_deleted++;
            }
        }
    }

    private function getTablesToDrop($project_id)
    {
        $central_db = $this->da->quoteSmart($this->central_database);
        $prefix     = $this->da->quoteLikeValueSuffix(MediawikiDao::DEDICATED_DATABASE_TABLE_PREFIX . '_' . $project_id);

        $sql = "SELECT TABLE_NAME as name
              FROM INFORMATION_SCHEMA.TABLES
              WHERE TABLE_SCHEMA = $central_db
                AND TABLE_NAME LIKE $prefix";

        return $this->retrieve($sql);
    }

    public function dropDatabase($database, $dry_run)
    {
        $this->logger->info("Attempt to purge database " . $database);
        if (strpos($database, MediawikiDao::DEDICATED_DATABASE_PREFIX) !== false) {
            if ($this->doesDatabaseExist($database)) {
                $sql = 'DROP DATABASE ' . $database;
                $this->logger->info($sql);
                if (! $dry_run) {
                    $this->update($sql);
                    $this->logger->info("DROP completed with success");
                }
                $this->db_deleted++;
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
            $this->update($sql);
            $this->logger->info("plugin_mediawiki_database purged");

            $sql = "DELETE FROM plugin_mediawiki_admin_options WHERE project_id = $project_id";
            $this->update($sql);
            $this->logger->info("plugin_mediawiki_admin_options purged");

            $sql = "DELETE FROM plugin_mediawiki_ugroup_mapping WHERE group_id = $project_id";
            $this->update($sql);
            $this->logger->info("plugin_mediawiki_ugroup_mapping purged");

            $sql = "DELETE FROM plugin_mediawiki_site_restricted_features WHERE project_id = $project_id";
            $this->update($sql);
            $this->logger->info("plugin_mediawiki_site_restricted_features purged");

            $sql = "DELETE FROM plugin_mediawiki_access_control WHERE project_id = $project_id";
            $this->update($sql);
            $this->logger->info("plugin_mediawiki_access_control purged");

            $sql = "DELETE FROM plugin_mediawiki_version WHERE project_id = $project_id";
            $this->update($sql);
            $this->logger->info("plugin_mediawiki_version purged");

            $sql = "DELETE FROM plugin_mediawiki_extension WHERE project_id = $project_id";
            $this->update($sql);
            $this->logger->info("plugin_mediawiki_extension purged");
        }
    }

    public function desactivateService($project_id, $dry_run)
    {
        $this->logger->info("Desactivate service in project");
        $project_id = $this->da->escapeInt($project_id);
        $sql = "UPDATE service SET is_used = 0 WHERE group_id = $project_id AND short_name = 'plugin_mediawiki'";
        if (! $dry_run) {
            $this->update($sql);
            $this->logger->info("Service desactivated");
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

    public function getAllMediawikiBasesNotReferenced()
    {
        $db_name   = ForgeConfig::get('sys_dbname');
        $sql = "SELECT SCHEMA_NAME AS 'name'
                FROM INFORMATION_SCHEMA.SCHEMATA
                  LEFT JOIN $db_name.plugin_mediawiki_database db ON (db.database_name = SCHEMA_NAME)
                WHERE SCHEMA_NAME LIKE 'plugin_mediawiki_%'
                  AND db.project_id IS NULL";
        return $this->retrieve($sql);
    }

    public function isDBEmpty($database_name)
    {
        $database_name = $this->da->quoteSmart($database_name);
        $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = $database_name LIMIT 1";
        return $this->retrieveCount($sql) === 0;
    }

    public function doesDatabaseNameCorrespondToAnActiveProject($database_name)
    {
        $identifier = substr($database_name, strlen(MediawikiDao::DEDICATED_DATABASE_PREFIX));
        $where      = 'groups.unix_group_name = ' . $this->da->quoteSmart($identifier);
        if (is_int($identifier)) {
            $where = 'groups.group_id = ' . $this->da->escapeInt($identifier);
        }
        $sql = "SELECT 1
                FROM groups
                  JOIN service ON (service.group_id = groups.group_id AND service.short_name = 'plugin_mediawiki')
                WHERE groups.status IN ('A', 's')
                    AND service.is_used = 1
                    AND $where";
        return $this->retrieveCount($sql) !== 0;
    }

    public function doesDatabaseHaveContent($database_name)
    {
        $table_name = $database_name . '.' . MediawikiDao::DEDICATED_DATABASE_TABLE_PREFIX . 'page';
        $sql = "SELECT 1 FROM $table_name LIMIT 1";
        $this->retrieveCount($sql) !== 0;
    }
}
