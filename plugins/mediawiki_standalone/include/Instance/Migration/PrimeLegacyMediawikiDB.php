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
use Tuleap\DB\DataAccessObject;

final class PrimeLegacyMediawikiDB extends DataAccessObject implements LegacyMediawikiDBPrimer
{
    private const MAPPING_TABLE_BASE_NAME = 'tuleap_user_mapping';

    public function prepareDBForMigration(string $db_name, string $db_prefix): void
    {
        $mapping_table_name = $db_prefix . self::MAPPING_TABLE_BASE_NAME;
        $this->createUserMappingTable($db_name, $mapping_table_name);
        $this->fillUserMappingTable($db_name, $db_prefix, $mapping_table_name);
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
                        JOIN user ON (LOWER(user.user_name) = LOWER(%s.user_name))',
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
