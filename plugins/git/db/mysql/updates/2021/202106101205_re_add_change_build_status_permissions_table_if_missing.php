<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class b202106101205_re_add_change_build_status_permissions_table_if_missing extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Re-add change build status permission table if it is missing';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_git_change_build_status_permissions (
            repository_id INT(10) UNSIGNED PRIMARY KEY,
            granted_user_groups_ids TEXT NOT NULL
        ) ENGINE=InnoDB';

        $this->db->createTable('plugin_git_change_build_status_permissions', $sql);
    }
}
