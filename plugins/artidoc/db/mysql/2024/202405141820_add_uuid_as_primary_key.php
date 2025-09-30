<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
final class b202405141820_add_uuid_as_primary_key extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add UUID as primary key';
    }

    public function up(): void
    {
        $this->api->createAndPopulateNewUUIDColumn(
            'plugin_artidoc_document',
            'id',
            function (): void {
                $uuid_factory = new \Tuleap\DB\DatabaseUUIDV7Factory();
                $rows         = $this->api->dbh->query('SELECT item_id, artifact_id FROM plugin_artidoc_document');
                $stmt         = $this->api->dbh->prepare('UPDATE plugin_artidoc_document SET id = ? WHERE item_id = ? and artifact_id = ?');
                foreach ($rows as $row) {
                    $uuid = $uuid_factory->buildUUIDBytes();
                    $stmt->execute([$uuid, $row['item_id'], $row['artifact_id']]);
                }
            }
        );

        $this->api->dbh->exec('ALTER TABLE plugin_artidoc_document DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->api->addIndex(
            'plugin_artidoc_document',
            'idx_uniq_artifact',
            'ALTER TABLE plugin_artidoc_document ADD UNIQUE idx_uniq_artifact (item_id, artifact_id)',
        );
    }
}
