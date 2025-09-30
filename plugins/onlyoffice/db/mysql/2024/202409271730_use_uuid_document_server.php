<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
final class b202409271730_use_uuid_document_server extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Use UUID instead of an auto-incremented ID in plugin_onlyoffice_document_server table';
    }

    public function up(): void
    {
        $this->api->addNewUUIDColumnToReplaceAutoIncrementedID('plugin_onlyoffice_document_server', 'id', 'uuid');
        $this->api->createAndPopulateNewUUIDColumn(
            'plugin_onlyoffice_document_server_project_restriction',
            'server_uuid',
            function (): void {
                $sql = 'UPDATE plugin_onlyoffice_document_server_project_restriction
                    JOIN plugin_onlyoffice_document_server ON (plugin_onlyoffice_document_server.id = plugin_onlyoffice_document_server_project_restriction.server_id)
                    SET plugin_onlyoffice_document_server_project_restriction.server_uuid = plugin_onlyoffice_document_server.uuid';
                $this->api->dbh->exec($sql);
            }
        );
        $this->api->createAndPopulateNewUUIDColumn(
            'plugin_onlyoffice_save_document_token',
            'server_uuid',
            function (): void {
                $this->api->dbh->exec('DELETE FROM plugin_onlyoffice_save_document_token WHERE server_id = 0');
                $sql = 'UPDATE plugin_onlyoffice_save_document_token
                    JOIN plugin_onlyoffice_document_server ON (plugin_onlyoffice_document_server.id = plugin_onlyoffice_save_document_token.server_id)
                    SET plugin_onlyoffice_save_document_token.server_uuid = plugin_onlyoffice_document_server.uuid';
                $this->api->dbh->exec($sql);
            }
        );
        $this->api->dbh->exec('ALTER TABLE plugin_onlyoffice_save_document_token DROP INDEX idx_document_server_id, DROP COLUMN server_id, RENAME COLUMN server_uuid TO server_id, ADD INDEX idx_document_server_id(document_id, server_id)');
        $this->api->dbh->exec('ALTER TABLE plugin_onlyoffice_document_server_project_restriction DROP PRIMARY KEY, DROP COLUMN server_id, RENAME COLUMN server_uuid TO server_id, ADD PRIMARY KEY (project_id, server_id)');
        $this->api->dbh->exec('ALTER TABLE plugin_onlyoffice_document_server DROP PRIMARY KEY, DROP COLUMN id, RENAME COLUMN uuid TO id, ADD PRIMARY KEY (id)');
    }
}
