<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class b2024003071430_add_project_to_project_file_upload_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add project_id to project_file_upload table';
    }

    public function up(): void
    {
        $this->api->dropTable('project_file_upload');

        $this->api->dbh->exec(
            <<<SQL
            CREATE TABLE project_file_upload
            (
                id INT PRIMARY KEY AUTO_INCREMENT,
                file_name VARCHAR(255) NOT NULL DEFAULT '',
                file_size BIGINT NOT NULL DEFAULT 0,
                user_id INT NOT NULL,
                expiration_date INT UNSIGNED NOT NULL,
                project_id INT NOT NULL
            ) ENGINE = InnoDB;
            SQL
        );
    }
}
