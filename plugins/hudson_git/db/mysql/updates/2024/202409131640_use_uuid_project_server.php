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
final class b202409131640_use_uuid_project_server extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Use UUID instead of an auto-incremented ID in plugin_hudson_git_project_server table';
    }

    public function up(): void
    {
        $this->api->addNewUUIDColumnToReplaceAutoIncrementedID('plugin_hudson_git_project_server', 'id', 'uuid');
        $this->api->createAndPopulateNewUUIDColumn(
            'plugin_hudson_git_project_server_job',
            'project_server_uuid',
            function (): void {
                if ($this->api->columnNameExists('plugin_hudson_git_project_server', 'api_id')) {
                    return;
                }
                $sql = 'UPDATE plugin_hudson_git_project_server_job
                    JOIN plugin_hudson_git_project_server ON (plugin_hudson_git_project_server.id = plugin_hudson_git_project_server_job.project_server_id)
                    SET plugin_hudson_git_project_server_job.project_server_uuid = plugin_hudson_git_project_server.uuid';
                $this->api->dbh->exec($sql);
            }
        );
        $this->api->dbh->exec('ALTER TABLE plugin_hudson_git_project_server_job DROP COLUMN project_server_id, RENAME COLUMN project_server_uuid TO project_server_id, ADD INDEX idx_project_server_id(project_server_id)');
        $this->api->dbh->exec('ALTER TABLE plugin_hudson_git_project_server RENAME COLUMN id TO api_id, RENAME COLUMN uuid TO id, DROP PRIMARY KEY, ADD PRIMARY KEY (id), ADD UNIQUE (api_id)');
    }
}
