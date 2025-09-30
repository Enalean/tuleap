<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
final class b202212201015_create_server_project_restriction_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Create table plugin_onlyoffice_document_server_project_restriction';
    }

    public function up(): void
    {
        $this->api->createTable(
            'plugin_onlyoffice_document_server_project_restriction',
            'CREATE TABLE plugin_onlyoffice_document_server_project_restriction(
                    project_id INT(11) NOT NULL,
                    server_id INT(11) NOT NULL,
                    PRIMARY KEY (project_id, server_id),
                    UNIQUE idx_project_id(project_id)
                 ) ENGINE=InnoDB;'
        );
        if ($this->api->columnNameExists('plugin_onlyoffice_document_server', 'is_project_restricted')) {
            return;
        }

        $this->api->dbh->exec('ALTER TABLE plugin_onlyoffice_document_server ADD COLUMN is_project_restricted BOOLEAN NOT NULL DEFAULT FALSE');
    }
}
