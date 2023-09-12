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
final class b202309121000_disable_backlog_service extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Disable Backlog service for projects using Program service.
            Backlog (previously Agile Dashboard) was inherited from template and was enabled to give access to Kanban.
            Kanban Service will now give access to Kanban instead.';
    }

    public function up(): void
    {
        $this->api->dbh->exec(
            "UPDATE service AS backlog_service
            JOIN service AS program_present
                ON (program_present.short_name = 'plugin_program_management' AND program_present.is_used = '1' AND
                    program_present.group_id = backlog_service.group_id)
            SET backlog_service.is_used = '0'
            WHERE backlog_service.short_name = 'plugin_agiledashboard'"
        );
    }
}
