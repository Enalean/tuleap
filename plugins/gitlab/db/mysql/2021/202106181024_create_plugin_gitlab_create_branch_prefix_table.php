<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202106181024_create_plugin_gitlab_create_branch_prefix_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Creates the table plugin_gitlab_repository_integration_create_branch_prefix';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS plugin_gitlab_repository_integration_create_branch_prefix (
                integration_id INT(11) NOT NULL PRIMARY KEY,
                create_branch_prefix VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB;
        ';

        $this->db->createTable('plugin_gitlab_repository_integration_create_branch_prefix', $sql);
    }
}
