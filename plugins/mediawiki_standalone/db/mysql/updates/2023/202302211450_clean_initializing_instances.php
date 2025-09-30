<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202302211450_clean_initializing_instances extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Remove wrongly init mediawiki instances';
    }

    public function up(): void
    {
        $this->api->dbh->beginTransaction();
        $result           = $this->api->dbh->query(<<<SQL
        SELECT service_id
        FROM service
            JOIN plugin_mediawiki_standalone_farm.tuleap_instances ON (ti_id = service.group_id AND short_name = 'plugin_mediawiki_standalone')
        WHERE ti_status = 'initializing'
          AND ti_directory IS NULL;
        SQL);
        $delete_statement = $this->api->dbh->prepare('DELETE FROM service WHERE service_id = ?');
        foreach ($result as $row) {
            $delete_statement->execute([$row['service_id']]);
        }

        $this->api->dbh->query(<<<SQL
        DELETE FROM plugin_mediawiki_standalone_farm.tuleap_instances WHERE ti_status = 'initializing' and ti_directory IS NULL
        SQL);
        $this->api->dbh->commit();
    }
}
