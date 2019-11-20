<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class b201911201100_add_table_reviewer_user extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add the table plugin_pullrequest_reviewer_user';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->db->createTable(
            'plugin_pullrequest_reviewer_user',
            'CREATE TABLE IF NOT EXISTS plugin_pullrequest_reviewer_user (
                pull_request_id INT(11) NOT NULL,
                user_id INT(11) UNSIGNED NOT NULL,
                PRIMARY KEY (pull_request_id, user_id)
            );'
        );
    }
}
