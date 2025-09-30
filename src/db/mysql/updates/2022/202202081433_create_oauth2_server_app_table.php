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
final class b202202081433_create_oauth2_server_app_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add oauth2_server_app table';
    }

    public function up(): void
    {
        $sql = 'CREATE TABLE oauth2_server_app(
                 id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                 project_id INT(11),
                 name VARCHAR(255) NOT NULL,
                 redirect_endpoint TEXT NOT NULL,
                 verifier VARCHAR(255) NOT NULL,
                 use_pkce BOOLEAN NOT NULL,
                 app_type VARCHAR(255) NOT NULL,
                 INDEX idx_project_id(project_id)
            ) ENGINE=InnoDB;';

        $this->api->createTable('oauth2_server_app', $sql);
    }
}
