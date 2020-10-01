<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
final class b202010011135_add_created_user_id_on_invitations_table extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add created_user_id on invitations table';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = "ALTER TABLE invitations ADD COLUMN created_user_id INT(11) NULL";
        $res = $this->db->dbh->query($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding created_user_id column: ' . implode(', ', $this->db->dbh->errorInfo()));
        }

        $sql = "ALTER TABLE invitations ADD INDEX idx_created(created_user_id, status, to_email(20))";
        $this->db->addIndex('invitations', 'idx_created', $sql);
    }
}
