<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
final class b202209091550_recreate_group_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Re-creates the plugin_gitlab_group table';
    }

    public function up(): void
    {
        $this->api->dropTable('plugin_gitlab_group');
        $this->api->createTable(
            'plugin_gitlab_group',
            'CREATE TABLE IF NOT EXISTS plugin_gitlab_group (
                    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    gitlab_group_id INT(11) NOT NULL,
                    project_id INT(11) NOT NULL,
                    `name` VARCHAR(255) NOT NULL,
                    full_path TEXT NOT NULL,
                    web_url VARCHAR(255) NOT NULL,
                    avatar_url TEXT DEFAULT NULL,
                    last_synchronization_date INT(11) NOT NULL,
                    allow_artifact_closure TINYINT(1) NOT NULL DEFAULT 0,
                    create_branch_prefix VARCHAR(255) DEFAULT NULL,
                    UNIQUE(gitlab_group_id, project_id)
                ) ENGINE = InnoDB;'
        );
    }
}
