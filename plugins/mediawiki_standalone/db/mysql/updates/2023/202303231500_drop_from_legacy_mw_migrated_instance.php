<?php
/**
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
final class b202303231500_drop_from_legacy_mw_migrated_instance extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Remove visibility of legacy MW on migrated instances';
    }

    public function up(): void
    {
        if (! $this->api->tableNameExists('plugin_mediawiki_database')) {
            return;
        }
        $this->api->dbh->exec(
            'DELETE plugin_mediawiki_database.*
            FROM plugin_mediawiki_database
            JOIN plugin_mediawiki_standalone_farm.tuleap_instances ON (plugin_mediawiki_database.project_id = plugin_mediawiki_standalone_farm.tuleap_instances.ti_id)'
        );
    }
}
