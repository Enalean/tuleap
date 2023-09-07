<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
final class b202309061612_add_missing_kanban_service extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add kanban service that was not inherited from template, only when there is agiledashboard and not already another kanban';
    }

    public function up(): void
    {
        $this->api->dbh->exec(
            "INSERT INTO service(`group_id`, `label`, `description`, `short_name`, `link`, `is_active`, `is_used`, `scope`, `rank`)
            SELECT DISTINCT missing_service.group_id, 'label', '', 'plugin_kanban', NULL, missing_service.is_active,
                missing_service.is_used, 'system', missing_service.rank + 2
            FROM service AS missing_service
                 LEFT JOIN (SELECT group_id FROM service WHERE short_name = 'plugin_kanban') AS kanban_present
            ON missing_service.group_id = kanban_present.group_id
            WHERE kanban_present.group_id IS NULL AND missing_service.short_name = 'plugin_agiledashboard'"
        );
    }
}
