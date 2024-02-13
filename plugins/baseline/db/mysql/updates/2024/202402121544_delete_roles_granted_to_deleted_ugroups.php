<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
final class b202402121544_delete_roles_granted_to_deleted_ugroups extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Delete roles granted to deleted user groups';
    }

    public function up(): void
    {
        $this->api->dbh->query(<<<SQL
            DELETE plugin_baseline_role_assignment
            FROM plugin_baseline_role_assignment
                     LEFT JOIN ugroup ON (ugroup.ugroup_id = plugin_baseline_role_assignment.user_group_id)
            WHERE plugin_baseline_role_assignment.user_group_id >= 100
              AND ugroup.ugroup_id IS NULL;
        SQL);
    }
}
