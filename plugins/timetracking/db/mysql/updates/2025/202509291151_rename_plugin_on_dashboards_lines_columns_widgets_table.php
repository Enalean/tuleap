<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
final class b202509291151_rename_plugin_on_dashboards_lines_columns_widgets_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Rename plugin timetracking-management-widget to people-timetracking-widget on dashboards_lines_columns_widgets table';
    }

    public function up()
    {
        $this->api->dbh->query(
            <<<EOS
            UPDATE dashboards_lines_columns_widgets
            SET name = 'people-timetracking-widget'
            WHERE name = 'timetracking-management-widget'
            EOS
        );
    }
}
