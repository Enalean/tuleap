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
final class b202401250900_add_has_default_permissions_to_repositories extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add column `has_default_permissions` to `plugin_svn_repositories`';
    }

    public function up(): void
    {
        if ($this->api->columnNameExists('plugin_svn_repositories', 'has_default_permissions')) {
            return;
        }

        $sql = 'ALTER TABLE plugin_svn_repositories ADD COLUMN has_default_permissions BOOL NOT NULL DEFAULT 1 AFTER is_core';

        $res = $this->api->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('Could not add `has_default_permissions` to `plugin_svn_repositories`');
        }
    }
}
