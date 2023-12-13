<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Instance\Migration;

use ParagonIE\EasyDB\EasyDB;
use Psr\Log\LoggerInterface;
use Tuleap\DB\DataAccessObject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;

final class PrimeLegacyMediawikiDB extends DataAccessObject implements LegacyMediawikiDBPrimer
{
    private const MAPPING_TABLE_BASE_NAME = 'tuleap_user_mapping';

    /**
     * @psalm-return Ok<null>|Err<Fault>
     */
    public function prepareDBForMigration(LoggerInterface $logger, \Project $project, string $db_name, string $db_prefix): Ok|Err
    {
        $project_id = (int) $project->getID();

        return $this->getCurrentDatabaseName($project_id)->okOr(
            Result::err(Fault::fromMessage(sprintf('No current MediaWiki database found for project #%d', $project_id)))
        )->andThen(
            /**
             * @psalm-return Ok<null>|Err<Fault>
             */
            function (string $current_db_name) use ($db_name, $db_prefix, $project_id, $logger): Ok|Err {
                $logger->info('Check db name');
                if ($current_db_name === $db_name) {
                    return Result::ok(null);
                }

                $logger->info('Move to another DB');
                return $this->moveToAnotherDB($current_db_name, $db_name, $db_prefix, $project_id);
            }
        )->andThen(
            /** @psalm-return Ok<null> */
            function () use ($db_prefix, $db_name, $logger): Ok {
                $logger->info('Create user mapping table');
                $mapping_table_name = $db_prefix . self::MAPPING_TABLE_BASE_NAME;
                $this->createUserMappingTable($db_name, $mapping_table_name);

                $logger->info('Fill user mapping table');
                $this->fillUserMappingTable($db_name, $db_prefix, $mapping_table_name);

                $logger->info('DB prepared for migration');
                return Result::ok(null);
            }
        );
    }

    /**
     * @psalm-return Option<string>
     */
    private function getCurrentDatabaseName(int $project_id): Option
    {
        $database_name = $this->getDB()->single('SELECT database_name FROM plugin_mediawiki_database WHERE project_id = ?', [$project_id]);
        if ($database_name === false) {
            return Option::nothing(\Psl\Type\string());
        }
        return Option::fromValue($database_name);
    }

    /**
     * @psalm-return Ok<null>|Err<Fault>
     */
    private function moveToAnotherDB(string $current_db_name, string $expected_db_name, string $db_prefix, int $project_id): Ok|Err
    {
        $this->createDatabase($expected_db_name);
        return $this->moveTablesToAnotherDB($current_db_name, $expected_db_name, $db_prefix)
            ->andThen(
                /**
                 * @psalm-return Ok<null>
                 */
                function () use ($current_db_name, $expected_db_name, $project_id): Ok {
                    $this->getDB()->run(
                        'UPDATE plugin_mediawiki_database SET database_name = ? WHERE project_id = ?',
                        $expected_db_name,
                        $project_id,
                    );
                    $this->getDB()->run('DROP DATABASE ' . $this->getDB()->escapeIdentifier($current_db_name));

                    return Result::ok(null);
                }
            );
    }

    private function createDatabase(string $db_name): void
    {
        $this->getDB()->run(sprintf('CREATE DATABASE IF NOT EXISTS %s DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci', $this->getDB()->escapeIdentifier($db_name)));
    }

    /**
     * @psalm-return Ok<null>|Err<Fault>
     */
    private function moveTablesToAnotherDB(string $current_db_name, string $expected_db_name, string $db_prefix): Ok|Err
    {
        foreach ($this->searchDBTableNames($current_db_name) as $table_name) {
            $table_name_without_prefix = \Psl\Str\after($table_name, 'mw');
            if ($table_name_without_prefix === null) {
                return Result::err(
                    Fault::fromMessage(
                        sprintf('Table %s in database %s does not have the expected format', $table_name, $current_db_name)
                    )
                );
            }

            $this->getDB()->run(
                sprintf(
                    'ALTER TABLE %s.%s RENAME %s.%s',
                    $this->getDB()->escapeIdentifier($current_db_name),
                    $this->getDB()->escapeIdentifier($table_name),
                    $this->getDB()->escapeIdentifier($expected_db_name),
                    $this->getDB()->escapeIdentifier($db_prefix . $table_name_without_prefix)
                )
            );
        }

        return Result::ok(null);
    }

    /**
     * @return string[]
     */
    private function searchDBTableNames(string $db_name): array
    {
        return $this->getDB()->col('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?', 0, $db_name);
    }

    private function createUserMappingTable(string $db_name, string $mapping_table_name): void
    {
        $this->getDB()->run(
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s.%s (
                    `tum_user_id` INT UNSIGNED NOT NULL PRIMARY KEY,
                    `tum_user_name` VARBINARY(255) NOT NULL,
                    INDEX idx_user_name(`tum_user_name`)
                );',
                $this->getDB()->escapeIdentifier($db_name),
                $this->getDB()->escapeIdentifier($mapping_table_name),
            )
        );
    }

    private function fillUserMappingTable(string $db_name, string $db_prefix, string $mapping_table_name): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($db_name, $db_prefix, $mapping_table_name): void {
                $escaped_db_name            = $db->escapeIdentifier($db_name);
                $escaped_mw_user_table_name = $db->escapeIdentifier($db_prefix . 'user');
                $escaped_mapping_table_name = $db->escapeIdentifier($mapping_table_name);

                $db->run(sprintf('DELETE FROM %s.%s', $escaped_db_name, $escaped_mapping_table_name));
                $db->run(
                    sprintf(
                        'INSERT INTO %s.%s(tum_user_id, tum_user_name)
                        SELECT user.user_id, %s.user_name
                        FROM %s.%s
                        JOIN user ON (LOWER(user.user_name) = LOWER(%s.user_name))
                        GROUP BY user.user_id',
                        $escaped_db_name,
                        $escaped_mapping_table_name,
                        $escaped_mw_user_table_name,
                        $escaped_db_name,
                        $escaped_mw_user_table_name,
                        $escaped_mw_user_table_name,
                    )
                );
            }
        );
    }
}
