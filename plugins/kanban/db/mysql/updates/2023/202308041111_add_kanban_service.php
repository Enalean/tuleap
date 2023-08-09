<?php
/*
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
 *
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202308041111_add_kanban_service extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add kanban service where there is agiledashboard';
    }

    public function up(): void
    {
        $this->api->dbh->query(<<<SQL
        INSERT INTO service(`group_id`, `label`, `description`, `short_name`, `link`, `is_active`, `is_used`, `scope`, `rank`)
        SELECT DISTINCT service.group_id,'label','','plugin_kanban',NULL,service.is_active,service.is_used,'system',service.rank+2
        FROM service
        WHERE short_name = 'plugin_agiledashboard'
        SQL);
    }
}
