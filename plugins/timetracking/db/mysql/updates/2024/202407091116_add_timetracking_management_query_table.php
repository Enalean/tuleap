<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
final class b202407091116_add_timetracking_management_query_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Add timetracking management query table';
    }

    public function up()
    {
        $sql = 'CREATE TABLE plugin_timetracking_management_query (
                id INT(11) AUTO_INCREMENT NOT NULL PRIMARY KEY,
                start_date INT(11),
                end_date INT(11),
                predefined_time_period VARCHAR(255)
                ) ENGINE=InnoDB;';
        $this->api->createTable('plugin_timetracking_management_query', $sql);
    }
}
