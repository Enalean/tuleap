<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202601291500_update_openlist_value_unique_index extends \Tuleap\ForgeUpgrade\Bucket
{
    #[Override]
    public function description(): string
    {
        return 'Drop the existing index to replace it with a new one that is case-sensitive';
    }

    #[Override]
    public function up(): void
    {
        if ($this->api->indexNameExists('tracker_field_openlist_value', 'idx_field_value')) {
            $this->api->dbh->exec('DROP INDEX idx_field_value ON tracker_field_openlist_value');
        }

        $this->api->addIndex(
            'tracker_field_openlist_value',
            'idx_field_value',
            <<<SQL
            ALTER TABLE tracker_field_openlist_value
                ADD COLUMN field_value BINARY(64) GENERATED ALWAYS AS (SHA2(CONCAT(CONVERT(field_id, char), label), 256)) STORED,
                ADD UNIQUE KEY idx_field_value(field_value)
            SQL,
        );
    }
}
