<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
final class b2023120611315_add_project_dashboards_recently_visited extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return "Add project dashboards recently visited table";
    }

    public function up(): void
    {
        $this->api->createTable(
            "project_dashboards_recently_visited",
            <<<EOS
            CREATE TABLE project_dashboards_recently_visited (
                user_id INT(11) NOT NULL,
                dashboard_id INT(11) UNSIGNED NOT NULL,
                created_on INT(11) UNSIGNED NOT NULL,
                PRIMARY KEY(user_id, dashboard_id),
                INDEX idx_dashboard(dashboard_id),
                INDEX idx_user_visit_time(user_id, created_on)
            ) ENGINE=InnoDB
            EOS
        );
    }
}
