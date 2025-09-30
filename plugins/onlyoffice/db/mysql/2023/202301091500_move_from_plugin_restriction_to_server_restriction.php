<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202301091500_move_from_plugin_restriction_to_server_restriction extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Migrate from plugin level project restriction to server level';
    }

    public function up(): void
    {
        $this->api->dbh->exec('
            UPDATE plugin_onlyoffice_document_server SET plugin_onlyoffice_document_server.is_project_restricted = (
                SELECT prj_restricted FROM plugin WHERE name = "onlyoffice"
            )');
        $this->api->dbh->exec('
            REPLACE INTO plugin_onlyoffice_document_server_project_restriction(project_id, server_id)
            SELECT project_plugin.project_id, plugin_onlyoffice_document_server.id
            FROM project_plugin
            JOIN plugin ON (plugin.id = project_plugin.plugin_id)
            JOIN plugin_onlyoffice_document_server
            WHERE plugin.name = "onlyoffice"
        ');
    }
}
