<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class b202403181030_add_project_template_archive_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Create new table project_template_archive for project';
    }

    public function up(): void
    {
        $this->api->createTable(
            'project_template_archive',
            <<<EOS
            CREATE TABLE project_template_archive (
                project_id INT(11) PRIMARY KEY,
                archive_path TEXT
            )
            EOS
        );
    }
}
