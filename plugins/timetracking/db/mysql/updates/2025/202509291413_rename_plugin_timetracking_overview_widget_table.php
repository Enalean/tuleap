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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class b202509291413_rename_plugin_timetracking_overview_widget_table extends \Tuleap\ForgeUpgrade\Bucket
{
    #[Override]
    public function description(): string
    {
        return 'Rename plugin_timetracking_overview_widget table to plugin_timetracking_project_widget';
    }

    #[Override]
    public function up(): void
    {
        $this->api->dbh->query(
            <<<EOS
            ALTER TABLE plugin_timetracking_overview_widget
                RENAME TO plugin_timetracking_project_widget
            EOS
        );
    }
}
