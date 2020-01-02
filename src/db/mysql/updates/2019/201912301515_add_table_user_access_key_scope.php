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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b201912301515_add_table_user_access_key_scope extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add user_access_key_scope table.';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->db->createTable(
            'user_access_key_scope',
            'CREATE TABLE user_access_key_scope (
              access_key_id INT(11) NOT NULL,
              scope_key VARCHAR(255) NOT NULL,
              PRIMARY KEY (access_key_id, scope_key)
            )'
        );

        $sql = 'INSERT IGNORE INTO user_access_key_scope(access_key_id, scope_key)
                SELECT id, "write:rest"
                FROM user_access_key';

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to set scope rest:write on existing access keys');
        }
    }
}
