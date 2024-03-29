<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202103150900_add_labels_table_for_program_increment extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add custom label and sub-label table for program increment';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'CREATE TABLE plugin_program_management_label_program_increment(
                    program_increment_tracker_id INT(11) NOT NULL PRIMARY KEY,
                    label VARCHAR(255) DEFAULT NULL,
                    sub_label VARCHAR(255) DEFAULT NULL
                ) ENGINE = InnoDb;';

        $this->db->createTable('plugin_program_management_label_program_increment', $sql);
    }
}
