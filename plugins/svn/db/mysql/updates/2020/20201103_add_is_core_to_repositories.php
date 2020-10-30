<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b20201103_add_is_core_to_repositories extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add column `is_core` to `plugin_svn_repositories`';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        if ($this->db->columnNameExists('plugin_svn_repositories', 'is_core')) {
            return;
        }

        $sql = 'ALTER TABLE plugin_svn_repositories ADD COLUMN is_core BOOL NOT NULL DEFAULT 0 AFTER project_id';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Could add `is_core` to `plugin_svn_repositories`');
        }
    }
}
