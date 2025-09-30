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
final class b202212140933_add_document_server_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Create table plugin_onlyoffice_document_server';
    }

    public function up(): void
    {
        $this->api->createTable(
            'plugin_onlyoffice_document_server',
            'CREATE TABLE plugin_onlyoffice_document_server(
                    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    url VARCHAR(255) NOT NULL,
                    secret_key TEXT NOT NULL
                ) ENGINE=InnoDB;'
        );

        $this->api->dbh->exec(
            <<<EOS
            INSERT INTO plugin_onlyoffice_document_server (url, secret_key)
            SELECT value, ""
            FROM forgeconfig
            WHERE name = "onlyoffice_document_server_url"
            EOS
        );

        $this->api->dbh->exec(
            <<<EOS
            UPDATE plugin_onlyoffice_document_server
            SET secret_key = (
                SELECT value
                FROM forgeconfig
                WHERE name = "onlyoffice_document_server_secret"
            )
            EOS
        );
    }
}
