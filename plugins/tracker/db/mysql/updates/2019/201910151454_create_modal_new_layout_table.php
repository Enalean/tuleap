<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b201910151454_create_modal_new_layout_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Create plugin_tracker_new_layout_modal_user table.';
    }

    public function up(): void
    {
        $this->createTable();
        $this->addAllExistingUserIds();
    }

    private function createTable(): void
    {
        $this->api->createTable(
            'plugin_tracker_new_layout_modal_user',
            'CREATE TABLE IF NOT EXISTS plugin_tracker_new_layout_modal_user (
                    user_id INT(11) PRIMARY KEY
                ) ENGINE=InnoDB'
        );
    }

    private function addAllExistingUserIds(): void
    {
        $sql = 'INSERT INTO plugin_tracker_new_layout_modal_user (user_id)
                SELECT DISTINCT user_id
                FROM user
                WHERE user_id >= 100 AND user_id NOT IN (SELECT user_id FROM plugin_tracker_new_layout_modal_user)';

        $this->api->dbh->exec($sql);
    }
}
